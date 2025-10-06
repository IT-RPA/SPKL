<?php

namespace App\Http\Controllers;

use App\Models\OvertimePlanning;
use App\Models\OvertimePlanningApproval;
use App\Models\Employee;
use App\Models\Department;
use App\Models\FlowJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlanningOvertimeController extends Controller
{
    /**
     * Display listing of planning overtime
     */
    public function index()
    {
        $currentUser = Auth::user();
        
        // Cari employee berdasarkan user login
        $currentEmployee = Employee::where('email', $currentUser->email)->first();
        
        if (!$currentEmployee) {
            return redirect()->route('dashboard')
                ->with('error', 'Data karyawan tidak ditemukan.');
        }

        // Ambil planning sesuai department employee
        $plannings = OvertimePlanning::with(['department', 'creator', 'approvals.approverEmployee'])
            ->where('department_id', $currentEmployee->department_id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('planning.index', compact('plannings', 'currentEmployee'));
    }

    /**
     * Show form untuk create planning
     */
    public function create()
    {
        $currentUser = Auth::user();
        
        $currentEmployee = Employee::with(['department', 'jobLevel'])
            ->where('email', $currentUser->email)
            ->where('is_active', true)
            ->first();
        
        if (!$currentEmployee) {
            return redirect()->route('planning.index')
                ->with('error', 'Data karyawan tidak ditemukan.');
        }

        // ✅ VALIDASI: Hanya Dept Head ke atas yang boleh buat planning
        $minLevelOrder = 40; // Misalnya Dept Head level_order = 40
        if ($currentEmployee->jobLevel->level_order > $minLevelOrder) {
            return redirect()->route('planning.index')
                ->with('error', 'Hanya Department Head ke atas yang dapat membuat planning lembur.');
        }

        // Ambil department milik employee
        $departments = Department::where('id', $currentEmployee->department_id)
            ->where('is_active', true)
            ->get();

        return view('planning.create', compact('currentEmployee', 'departments'));
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

        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'planned_date' => 'required|date|after_or_equal:today',
            'max_employees' => 'required|integer|min:1|max:100',
            'planned_start_time' => 'required',
            'planned_end_time' => 'required|after:planned_start_time',
            'work_description' => 'required|string|max:1000',
            'reason' => 'required|string|max:1000',
        ], [
            'planned_date.after_or_equal' => 'Tanggal planning tidak boleh di masa lalu',
            'planned_end_time.after' => 'Jam selesai harus lebih besar dari jam mulai',
            'max_employees.max' => 'Maksimal kuota adalah 100 orang',
        ]);

        // Validasi department
        if ($currentEmployee->department_id != $request->department_id) {
            return redirect()->route('planning.create')
                ->with('error', 'Anda hanya dapat membuat planning untuk departemen Anda sendiri.')
                ->withInput();
        }

        DB::transaction(function () use ($request, $currentEmployee) {
            // Generate planning number
            $planningNumber = OvertimePlanning::generatePlanningNumber($request->planned_date);
            
            // Create planning
            $planning = OvertimePlanning::create([
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
            $this->createPlanningApprovalFlow($planning, $currentEmployee);
            
            // Update status
            $planning->updateStatusBasedOnApprovals();
        });

        return redirect()->route('planning.index')
            ->with('success', 'Planning lembur berhasil dibuat dan menunggu approval.');
    }

    /**
     * Show detail planning
     */
    public function show(OvertimePlanning $planning)
    {
        // Load relationships
        $planning->load([
            'department',
            'creator.jobLevel',
            'approvals.approverEmployee.jobLevel',
            'overtimeRequests.details.employee'
        ]);

        $currentUser = Auth::user();
        $currentEmployee = Employee::where('email', $currentUser->email)->first();

        // Check apakah user ini salah satu approver
        $currentApproval = null;
        if ($currentEmployee) {
            $currentApproval = $planning->approvals()
                ->where('approver_employee_id', $currentEmployee->id)
                ->first();
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

        // Validasi approver
        if ($approval->approver_employee_id !== $currentEmployee->id) {
            return redirect()->back()->with('error', 'Anda tidak berwenang untuk approval ini');
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

        if ($approval->approver_employee_id !== $currentEmployee->id) {
            return redirect()->back()->with('error', 'Anda tidak berwenang untuk approval ini');
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
     * Create approval flow untuk planning
     */
    private function createPlanningApprovalFlow(OvertimePlanning $planning, Employee $requester)
    {
        \Log::info("=== CREATE PLANNING APPROVAL FLOW DEBUG ===");
        \Log::info("Planning ID: {$planning->id}, Department: {$planning->department_id}");
        \Log::info("Requester: {$requester->name}, Job Level: {$requester->jobLevel->code}");

        // Ambil flow job untuk planning (applies_to = 'planned' atau 'both')
        $flowJobs = FlowJob::with('jobLevel')
            ->where('department_id', $planning->department_id)
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
        
        \Log::info("=== END CREATE PLANNING APPROVAL FLOW DEBUG ===");
    }

    /**
     * Find approver based on flow job
     */
    private function findApproverForFlowJob(FlowJob $flowJob, $departmentId)
    {
        $jobLevelCode = $flowJob->jobLevel->code;
        $approver = null;

        switch ($jobLevelCode) {
            case 'DIV':
            case 'SUBDIV':
            case 'HRD':
                // Level tinggi: cari global
                $approver = Employee::with('jobLevel')
                    ->where('job_level_id', $flowJob->job_level_id)
                    ->where('is_active', true)
                    ->first();
                break;
                
            case 'DEPT':
            case 'SUBDEPT':
            case 'SECT':
                // Level departemen: cari di department yang sama
                $approver = Employee::with('jobLevel')
                    ->where('department_id', $departmentId)
                    ->where('job_level_id', $flowJob->job_level_id)
                    ->where('is_active', true)
                    ->first();
                break;
                
            default:
                // Coba di department dulu, kalau gak ada cari global
                $approver = Employee::with('jobLevel')
                    ->where('department_id', $departmentId)
                    ->where('job_level_id', $flowJob->job_level_id)
                    ->where('is_active', true)
                    ->first();
                    
                if (!$approver) {
                    $approver = Employee::with('jobLevel')
                        ->where('job_level_id', $flowJob->job_level_id)
                        ->where('is_active', true)
                        ->first();
                }
                break;
        }

        return $approver;
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