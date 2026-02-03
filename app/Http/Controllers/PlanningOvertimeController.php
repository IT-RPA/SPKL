<?php

namespace App\Http\Controllers;

use App\Models\OvertimePlanning;
use App\Models\OvertimePlanningApproval;
use App\Models\Employee;
use App\Models\Department;
use App\Models\FlowJob;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\FonnteHelper;

class PlanningOvertimeController extends Controller
{
    /**
     * Display listing of planning overtime
     */
    public function index()
    {
        $currentUser = Auth::user();

        // Cari employee berdasarkan user login
        $currentEmployee = Employee::with('jobLevel')->where('email', $currentUser->email)->first();

        if (!$currentEmployee) {
            return redirect()->route('dashboard')
                ->with('error', 'Data karyawan tidak ditemukan.');
        }

        // ✅ PERBAIKAN: Level tinggi (Sub Div, Div, HRD, Admin) bisa lihat semua planning
        $query = OvertimePlanning::with(['department', 'creator', 'approvals.approverEmployee'])
            ->orderBy('created_at', 'desc');

        // ✅ LOGIC AKSES BARU
        if (
            $currentUser->level_jabatan === "Administrator" ||
            $currentUser->name === "Fachri Ismawan" ||
            $currentUser->name === "Muhammad Natsir Irawan" ||
            $currentUser->name === "Hery Sumardiyanto"
        ) {
            // 1. Administrator & Special Users: Melihat SEMUA data (Bypass Filter)
        } elseif ($currentUser->level_jabatan == 'Department Head') {
            // 2. Department Head: Melihat data SATU DEPARTEMEN
            $query->where('department_id', $currentEmployee->department_id);
        } else {
            // 3. Role Lainnya: Department sendiri OR Pending Approval
            $query->where(function ($q) use ($currentEmployee, $currentUser) {
                // Condition 1: Same Department
                $q->where('department_id', $currentEmployee->department_id)
                    // Condition 2: Pending Approval for this user's level
                    ->orWhereHas('approvals', function ($subQ) use ($currentUser) {
                        $subQ->where('approver_level', $currentUser->level_jabatan)
                            ->where('status', 'pending');
                    });
            });
        }

        $plannings = $query->paginate(10);

        return view('planning.index', compact('plannings', 'currentEmployee'));
    }

    /**
     * Show form untuk create planning
     */
    public function create()
    {
        $currentUser = Auth::user();

        // Guard tambahan: hanya Department Head dan Administrator yang boleh akses form create
        if (!in_array($currentUser->level_jabatan, ['Department Head', 'Administrator'])) {
            abort(403, 'Anda tidak memiliki akses membuat Planning.');
        }

        $currentEmployee = Employee::with(['department', 'jobLevel'])
            ->where('email', $currentUser->email)
            ->where('is_active', true)
            ->first();
        $plants = Plant::get();

        if (!$currentEmployee) {
            return redirect()->route('planning.index')
                ->with('error', 'Data karyawan tidak ditemukan.');
        }

        // ✅ ADMIN: bisa pilih semua department | Non-Admin: hanya department sendiri
        $isAdmin = optional($currentUser->role)->name === 'admin';
        $departments = $isAdmin
            ? Department::where('is_active', true)->orderBy('name')->get()
            : Department::where('id', $currentEmployee->department_id)
                ->where('is_active', true)
                ->get();

        // ✅ Ambil approval levels untuk dropdown admin (per department)
        $approvalLevelsByDept = [];
        if ($isAdmin) {
            foreach ($departments as $dept) {
                foreach ($plants as $plant) {
                    $flowJobs = FlowJob::with('jobLevel', 'approverEmployee')
                        ->where('department_id', $dept->id)
                        ->where(function ($q) use ($plant) {
                            $q->where('plant_id', $plant->id)
                                ->orWhereNull('plant_id');
                        })
                        ->where('is_active', true)
                        ->whereIn('applies_to', ['planned', 'both'])
                        ->where('step_order', '>', 0)
                        ->orderBy('step_order')
                        ->get();

                    $approvalLevelsByDept[$dept->id][$plant->id] = $flowJobs->map(function ($flow) {
                        return [
                            'job_level_id' => $flow->job_level_id,
                            'step_order' => $flow->step_order,
                            'level_name' => $flow->jobLevel->name,
                            'approver_name' => $flow->approverEmployee->name ?? 'Belum ada approver',
                        ];
                    });
                }
            }
        }

        return view('planning.create', compact('currentEmployee', 'departments', 'isAdmin', 'approvalLevelsByDept', 'plants'));
    }

    /**
     * Store planning baru
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        $currentEmployee = Employee::with(['jobLevel', 'department'])
            ->where('email', $currentUser->email)
            ->where('is_active', true)
            ->first();

        if (!$currentEmployee) {
            return redirect()->route('planning.index')
                ->with('error', 'Data karyawan tidak ditemukan.');
        }

        // ✅ VALIDASI: Hanya Administrator dan Department Head yang dapat membuat planning
        if (!in_array($currentUser->level_jabatan, ['Department Head', 'Administrator'])) {
            abort(403, 'Anda tidak memiliki akses membuat Planning.');
        }

        $isAdmin = optional($currentUser->role)->name === 'admin';

        $validationRules = [
            'plant_id' => 'required|exists:plants,id',
            'department_id' => 'required|exists:departments,id',
            'planned_date' => 'required|date|after_or_equal:today',
            'max_employees' => 'required|integer|min:1|max:100',
            'planned_start_time' => 'required',
            'planned_end_time' => 'required|after:planned_start_time',
            'work_description' => 'required|string|max:1000',
            'reason' => 'required|string|max:1000',
        ];

        // ✅ Admin wajib pilih start approval level
        if ($isAdmin) {
            $validationRules['start_approval_level_id'] = 'required|exists:job_levels,id';
        }

        $request->validate($validationRules, [
            'planned_date.after_or_equal' => 'Tanggal planning tidak boleh di masa lalu',
            'planned_end_time.after' => 'Jam selesai harus lebih besar dari jam mulai',
            'max_employees.max' => 'Maksimal kuota adalah 100 orang',
            'start_approval_level_id.required' => 'Silakan pilih mulai approval dari level mana',
        ]);

        // ✅ VALIDASI: Admin boleh untuk semua dept, non-admin hanya dept sendiri
        if (!$isAdmin && $currentEmployee->department_id != $request->department_id) {
            return redirect()->route('planning.create')
                ->with('error', 'Anda hanya dapat membuat planning untuk departemen Anda sendiri.')
                ->withInput();
        }

        DB::transaction(function () use ($request, $currentEmployee, $isAdmin) {
            // Generate planning number
            $planningNumber = OvertimePlanning::generatePlanningNumber($request->planned_date);

            // Create planning
            $planning = OvertimePlanning::create([
                'plant_id' => $request->plant_id,
                'planning_number' => $planningNumber,
                'department_id' => $request->department_id,
                'planned_date' => $request->planned_date,
                'max_employees' => $request->max_employees,
                'planned_start_time' => $request->planned_start_time,
                'planned_end_time' => $request->planned_end_time,
                'work_description' => $request->work_description,
                'reason' => $request->reason,
                'used_employees' => 0,
                'remaining_employees' => $request->max_employees,
                'status' => 'pending',
                'created_by' => $currentEmployee->id,
            ]);

            \Log::info("Planning created: {$planning->planning_number} by {$currentEmployee->name}");

            // Create approval flow
            if ($isAdmin) {
                $this->createPlanningApprovalFlowForAdmin($planning, $request->start_approval_level_id);
            } else {
                $this->createPlanningApprovalFlow($planning, $currentEmployee);
            }

            // Update status
            $planning->updateStatusBasedOnApprovals();

            // ----------------------------------------------------
            // WA NOTIFICATION LOGIC (CREATE/STORE)
            // ----------------------------------------------------
            try {
                $planning->refresh();
                // Cari step selanjutnya yang PENDING (Step 1)
                $nextApproval = $planning->approvals()
                    ->where('status', 'pending')
                    ->orderBy('step_order', 'asc')
                    ->with('approverEmployee')
                    ->first();

                if ($nextApproval && $nextApproval->approverEmployee) {
                    // Cari User associated with Approver Employee
                    $nextUser = \App\Models\User::where('employee_id', $nextApproval->approverEmployee->employee_id)->first();

                    if ($nextUser && $nextUser->phone) {
                        $nextUser->notify(new \App\Notifications\PlanningApprovalNotification($planning));
                        \Log::info("Sent Initial PlanningApprovalNotification to User ID {$nextUser->id}");
                    }
                }
            } catch (\Exception $e) {
                \Log::error("WA Notification Error (Store): " . $e->getMessage());
            }
        });

        return redirect()->route('planning.index')
            ->with('success', 'Planning lembur berhasil dibuat dan menunggu approval.');
    }

    /**
     * Show detail planning
     */
    public function show(OvertimePlanning $planning)
    {
        // ✅ PERBAIKAN: Load semua relationship dengan proper ordering
        $planning->load([
            'department',
            'creator.jobLevel',
            'approvals' => function ($query) {
                $query->orderBy('step_order', 'asc');
            },
            'approvals.approverEmployee.jobLevel',
            'overtimeRequests.details.employee'
        ]);

        $currentUser = Auth::user();
        $currentEmployee = Employee::where('email', $currentUser->email)->first();

        // Check apakah user ini salah satu approver (BY LEVEL JABATAN - FLEXIBLE MATCH)
        $currentApproval = null;
        if ($currentUser->level_jabatan) {
            $userLevel = strtolower(trim($currentUser->level_jabatan));
            $empLevelName = $currentEmployee ? strtolower(trim($currentEmployee->jobLevel->name ?? '')) : '';
            $empLevelCode = $currentEmployee ? strtolower(trim($currentEmployee->jobLevel->code ?? '')) : '';

            // Helper function untuk matching level
            $isLevelMatch = function ($approverLevel) use ($userLevel, $empLevelName, $empLevelCode) {
                $appLevel = strtolower(trim($approverLevel));
                return $appLevel === $userLevel ||
                    ($empLevelName && $appLevel === $empLevelName) ||
                    ($empLevelCode && $appLevel === $empLevelCode);
            };

            // 1. Cari yang PENDING (agar tombol muncul)
            $currentApproval = $planning->approvals->first(function ($app) use ($isLevelMatch) {
                return $isLevelMatch($app->approver_level) && $app->status === 'pending';
            });

            // 2. Jika tidak ada yang pending, cari history approval level ini
            if (!$currentApproval) {
                $currentApproval = $planning->approvals->first(function ($app) use ($isLevelMatch) {
                    return $isLevelMatch($app->approver_level);
                });
            }
        }

        return view('planning.show', compact('planning', 'currentApproval', 'currentEmployee'));
    }

    /**
     * Approve planning
     */
    public function approve(Request $request, OvertimePlanningApproval $approval)
    {
        $currentEmployee = Employee::where('email', Auth::user()->email)->first();

        if (!$currentEmployee) {
            return redirect()->back()->with('error', 'Data karyawan tidak ditemukan');
        }

        // Validasi approver (Flexible Match)
        $userLevel = strtolower(trim(Auth::user()->level_jabatan));
        $empLevelName = $currentEmployee ? strtolower(trim($currentEmployee->jobLevel->name ?? '')) : '';
        $empLevelCode = $currentEmployee ? strtolower(trim($currentEmployee->jobLevel->code ?? '')) : '';
        $approverLevel = strtolower(trim($approval->approver_level));

        $isLevelMatch = ($approverLevel === $userLevel) ||
            ($empLevelName && $approverLevel === $empLevelName) ||
            ($empLevelCode && $approverLevel === $empLevelCode);

        if (!$isLevelMatch) {
            return redirect()->back()->with('error', 'Anda tidak berwenang untuk approval ini (Level mismatch)');
        }

        // Validasi giliran approve
        if (!$this->canUserApproveNow($approval)) {
            return redirect()->back()
                ->with('error', 'Belum giliran Anda untuk approve. Masih ada approval sebelumnya yang belum disetujui.');
        }

        DB::transaction(function () use ($approval, $request) {
            $approval->update([
                'status' => 'approved',
                'approved_at' => now(),
                'notes' => $request->notes ?? 'Disetujui',
            ]);

            \Log::info("Planning approval approved - ID: {$approval->id}, Planning: {$approval->planning->planning_number}, User: " . Auth::user()->name);

            // Update status planning
            $approval->planning->updateStatusBasedOnApprovals();

            // ----------------------------------------------------
            // WA NOTIFICATION LOGIC (APPROVE)
            // ----------------------------------------------------
            try {
                $planning = $approval->planning->fresh(); // Reload to get latest status

                // Cek apakah planning sudah kelar (approved/active)
                if (in_array($planning->status, ['approved', 'active'])) {
                    // Kirim ke pembuat planning (Creator)
                    $creatorEmployee = $planning->creator; // Employee model
                    if ($creatorEmployee) {
                        $creatorUser = \App\Models\User::where('employee_id', $creatorEmployee->employee_id)->first();

                        if ($creatorUser && $creatorUser->phone) {
                            $msg = "*Planning Lembur Disetujui Semua Level*\n\n";
                            $msg .= "No: {$planning->planning_number}\n";
                            $msg .= "Planning telah selesai disetujui.";

                            FonnteHelper::send($creatorUser->phone, $msg);
                        }
                    }

                } else {
                    // Jika belum kelar, cari step selanjutnya yang PENDING
                    $nextApproval = $planning->approvals()
                        ->where('status', 'pending')
                        ->orderBy('step_order', 'asc')
                        ->with('approverEmployee')
                        ->first();

                    if ($nextApproval && $nextApproval->approverEmployee) {
                        // Cari User associated with Approver Employee
                        $nextUser = \App\Models\User::where('employee_id', $nextApproval->approverEmployee->employee_id)->first();

                        if ($nextUser && $nextUser->phone) {
                            // Send custom Notification class
                            $nextUser->notify(new \App\Notifications\PlanningApprovalNotification($planning));
                            \Log::info("Sent PlanningApprovalNotification to User ID {$nextUser->id}");
                        } else {
                            \Log::warning("Cannot send notification: User not found or no phone for Employee ID {$nextApproval->approverEmployee->employee_id}");
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error("WA Notification Error (Approve): " . $e->getMessage());
            }
        });

        return redirect()->back()->with('success', 'Planning lembur berhasil disetujui');
    }

    /**
     * Reject planning
     */
    public function reject(Request $request, OvertimePlanningApproval $approval)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $currentEmployee = Employee::where('email', Auth::user()->email)->first();

        if (!$currentEmployee) {
            return redirect()->back()->with('error', 'Data karyawan tidak ditemukan');
        }

        // Validasi approver (Flexible Match)
        $userLevel = strtolower(trim(Auth::user()->level_jabatan));
        $empLevelName = $currentEmployee ? strtolower(trim($currentEmployee->jobLevel->name ?? '')) : '';
        $empLevelCode = $currentEmployee ? strtolower(trim($currentEmployee->jobLevel->code ?? '')) : '';
        $approverLevel = strtolower(trim($approval->approver_level));

        $isLevelMatch = $approverLevel !== '' && (
            ($approverLevel === $userLevel) ||
            ($empLevelName && $approverLevel === $empLevelName) ||
            ($empLevelCode && $approverLevel === $empLevelCode)
        );

        if (!$isLevelMatch) {
            return redirect()->back()->with('error', 'Anda tidak berwenang untuk approval ini (Level mismatch)');
        }

        if (!$this->canUserApproveNow($approval)) {
            return redirect()->back()
                ->with('error', 'Belum giliran Anda untuk menolak. Masih ada approval sebelumnya yang belum disetujui.');
        }

        DB::transaction(function () use ($approval, $request) {
            $approval->update([
                'status' => 'rejected',
                'approved_at' => now(),
                'notes' => $request->reason,
            ]);

            // ----------------------------------------------------
            // WA NOTIFICATION LOGIC (REJECT)
            // ----------------------------------------------------
            try {
                $planning = $approval->planning;
                // Kirim ke pembuat planning
                $creatorEmployee = $planning->creator;

                if ($creatorEmployee) {
                    $creatorUser = \App\Models\User::where('employee_id', $creatorEmployee->employee_id)->first();

                    if ($creatorUser && $creatorUser->phone) {
                        $msg = "*Planning Lembur Ditolak*\n\n";
                        $msg .= "No: {$planning->planning_number}\n";
                        $msg .= "Ditolak oleh: " . Auth::user()->name . "\n\n";
                        $msg .= "Catatan:\n{$request->reason}";

                        FonnteHelper::send($creatorUser->phone, $msg);
                    }
                }
            } catch (\Exception $e) {
                \Log::error("WA Notification Error (Reject): " . $e->getMessage());
            }

            // Auto-reject approval selanjutnya
            $pendingApprovals = OvertimePlanningApproval::where('planning_id', $approval->planning_id)
                ->where('step_order', '>', $approval->step_order)
                ->where('status', 'pending')
                ->get();

            foreach ($pendingApprovals as $pendingApproval) {
                $pendingApproval->update([
                    'status' => 'rejected',
                    'notes' => 'Dibatalkan karena ada rejection di step sebelumnya',
                    'approved_at' => now()
                ]);
            }

            \Log::info("Planning approval rejected - ID: {$approval->id}, Planning: {$approval->planning->planning_number}, Reason: {$request->reason}");

            $approval->planning->updateStatusBasedOnApprovals();
        });

        return redirect()->back()->with('success', 'Planning lembur berhasil ditolak');
    }

    /**
     * Create approval flow untuk planning (ADMIN - Flexible Start)
     */
    private function createPlanningApprovalFlowForAdmin(OvertimePlanning $planning, $startApprovalLevelId)
    {
        \Log::info("=== CREATE PLANNING APPROVAL FLOW FOR ADMIN ===");
        \Log::info("Planning ID: {$planning->id}, Department: {$planning->department_id}");
        \Log::info("Start Approval Level ID: {$startApprovalLevelId}");

        // Ambil flow job untuk planning (applies_to = 'planned' atau 'both')
        $flowJobs = FlowJob::with('jobLevel')
            ->where('department_id', $planning->department_id)
            ->where(function ($query) use ($planning) {
                $query->where('plant_id', $planning->plant_id)
                    ->orWhereNull('plant_id');
            })
            ->where('is_active', true)
            ->whereIn('applies_to', ['planned', 'both'])
            ->orderBy('step_order')
            ->get();

        \Log::info("Found " . $flowJobs->count() . " flow jobs for planning");

        // Cari step_order dari level yang dipilih admin
        $startFlowJob = $flowJobs->where('job_level_id', $startApprovalLevelId)->first();

        if (!$startFlowJob) {
            \Log::error("Flow job tidak ditemukan untuk level yang dipilih admin");
            throw new \Exception('Flow job tidak ditemukan untuk level approval yang dipilih');
        }

        \Log::info("Start from: {$startFlowJob->step_name}, Step Order: {$startFlowJob->step_order}");

        // ✅ FIX: Gunakan >= untuk INCLUDE level yang dipilih sebagai approver pertama
        $nextFlowJobs = $flowJobs->where('step_order', '>=', $startFlowJob->step_order);

        \Log::info("Creating " . $nextFlowJobs->count() . " approval steps (starting from {$startFlowJob->step_name})");

        foreach ($nextFlowJobs as $flowJob) {
            $approver = $this->findApproverForFlowJob($flowJob, $planning->department_id);

            if ($approver) {
                try {
                    $approvalRecord = OvertimePlanningApproval::create([
                        'planning_id' => $planning->id,
                        'approver_employee_id' => $approver->id,
                        'approver_level' => $flowJob->jobLevel->code,
                        'step_order' => $flowJob->step_order,
                        'step_name' => $flowJob->step_name,
                        'status' => 'pending',
                    ]);

                    \Log::info("✅ Created approval ID {$approvalRecord->id} for {$flowJob->step_name} - Approver: {$approver->name}");
                } catch (\Exception $e) {
                    \Log::error("❌ Failed to create approval for {$flowJob->step_name}: " . $e->getMessage());
                }
            } else {
                \Log::warning("⚠️ Approver not found for step: {$flowJob->step_name}, Job Level: {$flowJob->jobLevel->code}");
            }
        }

        \Log::info("=== END CREATE PLANNING APPROVAL FLOW FOR ADMIN ===");
    }

    /**
     * Create approval flow untuk planning (NON-ADMIN - Auto by Position)
     */
    private function createPlanningApprovalFlow(OvertimePlanning $planning, Employee $requester)
    {
        \Log::info("=== CREATE PLANNING APPROVAL FLOW (NON-ADMIN) ===");
        \Log::info("Planning ID: {$planning->id}, Department: {$planning->department_id}");
        \Log::info("Requester: {$requester->name}, Job Level: {$requester->jobLevel->code}");

        // Ambil flow job untuk planning (applies_to = 'planned' atau 'both')
        $flowJobs = FlowJob::with('jobLevel')
            ->where('department_id', $planning->department_id)
            ->where(function ($query) use ($planning) {
                $query->where('plant_id', $planning->plant_id)
                    ->orWhereNull('plant_id');
            })
            ->where('is_active', true)
            ->whereIn('applies_to', ['planned', 'both'])
            ->orderBy('step_order')
            ->get();

        \Log::info("Found " . $flowJobs->count() . " flow jobs for planning");

        // Cari posisi requester dalam flow
        $requesterFlowJob = $flowJobs->where('job_level_id', $requester->job_level_id)->first();

        if (!$requesterFlowJob) {
            \Log::error("Flow job tidak ditemukan untuk level jabatan pengaju: {$requester->jobLevel->code}");
            throw new \Exception('Flow job tidak ditemukan untuk level jabatan pengaju');
        }

        \Log::info("Requester Flow Job: {$requesterFlowJob->step_name}, Step Order: {$requesterFlowJob->step_order}");

        // Buat approval untuk step selanjutnya
        $nextFlowJobs = $flowJobs->where('step_order', '>', $requesterFlowJob->step_order);

        \Log::info("Found " . $nextFlowJobs->count() . " next flow jobs");

        foreach ($nextFlowJobs as $flowJob) {
            $approver = $this->findApproverForFlowJob($flowJob, $planning->department_id);

            if ($approver) {
                try {
                    $approvalRecord = OvertimePlanningApproval::create([
                        'planning_id' => $planning->id,
                        'approver_employee_id' => $approver->id,
                        'approver_level' => $flowJob->jobLevel->code,
                        'step_order' => $flowJob->step_order,
                        'step_name' => $flowJob->step_name,
                        'status' => 'pending',
                    ]);

                    \Log::info("✅ Created approval ID {$approvalRecord->id} for {$flowJob->step_name} - Approver: {$approver->name}");
                } catch (\Exception $e) {
                    \Log::error("❌ Failed to create approval for {$flowJob->step_name}: " . $e->getMessage());
                }
            } else {
                \Log::error("❌ Approver not found for step: {$flowJob->step_name}, Job Level: {$flowJob->jobLevel->code}");
            }
        }

        \Log::info("=== END CREATE PLANNING APPROVAL FLOW (NON-ADMIN) ===");
    }

    /**
     * Find approver based on flow job
     */
    private function findApproverForFlowJob(FlowJob $flowJob, $departmentId)
    {
        $jobLevelCode = $flowJob->jobLevel->id;

        // definisi level mana saja yang bersifat lokal
        $localLevels = [10, 11]; // misal: Forman dan Section Head

        $query = Employee::where('id', $flowJob->approver_employee_id);

        if (in_array($flowJob->job_level_id, $localLevels)) {
            // cari di plant yang sama (lokal)
            $query->where('plant_id', $flowJob->plant_id);
        }
        // jika tidak termasuk lokal, berarti global (tanpa filter plant)

        return $query->first();
    }

    /**
     * Check apakah user bisa approve sekarang
     */
    private function canUserApproveNow(OvertimePlanningApproval $approval)
    {
        if ($approval->status !== 'pending') {
            return false;
        }

        // Cek apakah ada approval sebelumnya yang masih pending
        $previousPendingApproval = OvertimePlanningApproval::where('planning_id', $approval->planning_id)
            ->where('step_order', '<', $approval->step_order)
            ->where('status', 'pending')
            ->exists();

        return !$previousPendingApproval;
    }
}
