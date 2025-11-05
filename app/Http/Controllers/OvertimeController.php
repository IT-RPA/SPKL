<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\OvertimeDetail;
use App\Models\OvertimeApproval;
use App\Models\OvertimePlanning; // ✅ TAMBAHAN
use App\Models\Employee;
use App\Models\Department;
use App\Models\FlowJob;
use App\Models\ProcessType; // ✅ TAMBAHAN
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OvertimeController extends Controller
{
    public function index()
    {
        $requests = OvertimeRequest::with(['requester', 'department', 'details.employee', 'planning']) // ✅ TAMBAHAN: load planning
            ->where('requester_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $hasIncompleteRequest = OvertimeRequest::where('requester_id', Auth::id())
            ->where('status', 'approved')
            ->exists();

        return view('overtime.index', compact('requests', 'hasIncompleteRequest'));
    }

    public function create()
    {
        $hasIncompleteRequest = OvertimeRequest::where('requester_id', Auth::id())
            ->where('status', 'approved')
            ->exists();

        if ($hasIncompleteRequest) {
            return redirect()->route('overtime.index')
                ->with('error', 'Anda tidak dapat membuat pengajuan baru karena masih ada pengajuan yang perlu diselesaikan realisasinya.');
        }

        $currentUser = Auth::user();

        $currentEmployee = Employee::with(['department', 'jobLevel'])
            ->where('email', $currentUser->email)
            ->where('is_active', true)
            ->first();

        if (!$currentEmployee) {
            $currentEmployee = Employee::with(['department', 'jobLevel'])
                ->where('name', 'LIKE', '%' . $currentUser->name . '%')
                ->where('is_active', true)
                ->first();
        }

        if (!$currentEmployee) {
            return redirect()->route('overtime.index')
                ->with('error', 'Data karyawan tidak ditemukan untuk akun Anda.');
        }

        $departments = Department::where('id', $currentEmployee->department_id)
            ->where('is_active', true)
            ->get();

        $employees = $this->getEligibleEmployeesForDetail($currentEmployee);

        $eligibleRequesters = Employee::with(['department', 'jobLevel'])
            ->where('id', $currentEmployee->id)
            ->where('is_active', true)
            ->get();

        $currentEmployeeData = $currentEmployee;

        $processTypes = ProcessType::where('is_active', true)->orderBy('code')->get();

        return view('overtime.create', compact('employees', 'departments', 'currentEmployeeData', 'eligibleRequesters', 'processTypes'));
    }

    // ✅ FUNGSI BARU: Check available planning untuk tanggal & department tertentu
    public function checkAvailablePlanning(Request $request)
    {
        $date = $request->date;
        $departmentId = $request->department_id;

        \Log::info("=== CHECK AVAILABLE PLANNING ===");
        \Log::info("Date: {$date}, Department: {$departmentId}");

        // Cari planning yang valid untuk tanggal & department ini
        $plannings = OvertimePlanning::with(['department', 'creator'])
            ->where('department_id', $departmentId)
            ->where('planned_date', $date)
            ->where('status', 'approved')
            ->where('remaining_employees', '>', 0)
            ->get();

        \Log::info("Found " . $plannings->count() . " available plannings");

        if ($plannings->isEmpty()) {
            return response()->json([
                'has_planning' => false,
                'message' => 'Tidak ada planning untuk tanggal ini'
            ]);
        }

        // Jika ada multiple planning (shift berbeda), kembalikan semua
        $planningsData = $plannings->map(function ($planning) {
            return [
                'id' => $planning->id,
                'planning_number' => $planning->planning_number,
                'planned_start_time' => $planning->planned_start_time,
                'planned_end_time' => $planning->planned_end_time,
                'remaining_employees' => $planning->remaining_employees,
                'max_employees' => $planning->max_employees,
                'work_description' => $planning->work_description,
            ];
        });

        return response()->json([
            'has_planning' => true,
            'plannings' => $planningsData,
            'count' => $plannings->count()
        ]);
    }

    private function getEligibleEmployeesForDetail($currentEmployee)
    {
        $requesterLevelOrder = $currentEmployee->jobLevel->level_order ?? 999;

        \Log::info("=== ELIGIBLE EMPLOYEES DEBUG ===");
        \Log::info("Requester: {$currentEmployee->name}");
        \Log::info("Requester Level: {$currentEmployee->jobLevel->name} (Order: {$requesterLevelOrder})");

        $eligibleEmployees = Employee::with(['department', 'jobLevel'])
            ->where('department_id', $currentEmployee->department_id)
            ->where('is_active', true)
            ->whereHas('jobLevel', function ($query) use ($requesterLevelOrder) {
                $query->where('level_order', '>=', $requesterLevelOrder);
            })
            ->orderBy('job_level_id', 'asc')
            ->get();

        \Log::info("Found " . $eligibleEmployees->count() . " eligible employees");
        \Log::info("=== END ELIGIBLE EMPLOYEES DEBUG ===");

        return $eligibleEmployees;
    }

    public function store(Request $request)
    {
        $currentUser = Auth::user();

        $selectedEmployee = Employee::with(['jobLevel', 'department'])
            ->find($request->employee_id);

        if (!$selectedEmployee) {
            return redirect()->route('overtime.create')
                ->with('error', 'Data karyawan tidak ditemukan.');
        }

        $currentEmployee = Employee::where('email', $currentUser->email)
            ->orWhere('name', 'LIKE', '%' . $currentUser->name . '%')
            ->where('is_active', true)
            ->first();

        if (!$currentEmployee || $selectedEmployee->id != $currentEmployee->id) {
            return redirect()->route('overtime.create')
                ->with('error', 'Anda hanya dapat mengajukan lembur untuk diri sendiri.');
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'overtime_category' => 'required|in:planned,unplanned', // ✅ TAMBAHAN
            'planning_id' => 'required_if:overtime_category,planned|nullable|exists:overtime_plannings,id', // ✅ TAMBAHAN
            'details' => 'required|array|min:1',
            'details.*.employee_id' => 'required|exists:employees,id',
            'details.*.start_time' => 'required',
            'details.*.end_time' => 'required',
            'details.*.work_priority' => 'required',
            'details.*.process_type_id' => 'required|exists:process_types,id',
            'details.*.overtime_type' => 'required|in:quantitative,qualitative',
            'details.*.qty_plan' => 'required_if:details.*.overtime_type,quantitative|nullable|integer|min:1',
        ]);

        $this->validateDetailEmployees($request->details, $currentEmployee);

        if ($selectedEmployee->department_id != $request->department_id) {
            return redirect()->route('overtime.create')
                ->with('error', 'Departemen tidak sesuai dengan data karyawan.');
        }
        if ($request->overtime_category === 'planned' && $request->planning_id) {
            $planning = OvertimePlanning::find($request->planning_id);

            if (!$planning) {
                return redirect()->route('overtime.create')
                    ->with('error', 'Planning tidak ditemukan.')
                    ->withInput();
            }

            // Validasi status planning
            if ($planning->status !== 'approved') {
                return redirect()->route('overtime.create')
                    ->with('error', 'Planning belum diapprove atau sudah ditolak.')
                    ->withInput();
            }

            // ✅ GANTI BAGIAN INI
            $planningDate = \Carbon\Carbon::parse($planning->planned_date);
            $overtimeDate = \Carbon\Carbon::parse($request->date);

            // Boleh ajukan sebelum atau sama dengan tanggal planning
            if ($overtimeDate->gt($planningDate)) {
                return redirect()->route('overtime.create')
                    ->with('error', 'Tanggal overtime tidak boleh melewati tanggal planning (' . $planning->planned_date . ')')
                    ->withInput();
            }

            // Validasi planning tidak expired
            if ($planning->status === 'expired') {
                return redirect()->route('overtime.create')
                    ->with('error', 'Planning ini sudah expired.')
                    ->withInput();
            }

            // Validasi quota
            $employeeCount = count($request->details);
            if ($employeeCount > $planning->remaining_employees) {
                return redirect()->route('overtime.create')
                    ->with('error', "Jumlah karyawan ({$employeeCount}) melebihi sisa kuota planning ({$planning->remaining_employees})")
                    ->withInput();
            }
        }

        DB::transaction(function () use ($request, $selectedEmployee) {
            $overtimeRequest = OvertimeRequest::create([
                'request_number' => OvertimeRequest::generateRequestNumber(),
                'requester_id' => Auth::id(),
                'requester_employee_id' => $selectedEmployee->id,
                'requester_level' => $selectedEmployee->jobLevel->code,
                'date' => $request->date,
                'department_id' => $request->department_id,
                'overtime_category' => $request->overtime_category, // ✅ TAMBAHAN
                'planning_id' => $request->planning_id, // ✅ TAMBAHAN
            ]);

            foreach ($request->details as $detail) {
                OvertimeDetail::create([
                    'overtime_request_id' => $overtimeRequest->id,
                    'employee_id' => $detail['employee_id'],
                    'start_time' => $detail['start_time'],
                    'end_time' => $detail['end_time'],
                    'work_priority' => $detail['work_priority'],
                    'process_type_id' => $detail['process_type_id'],
                    'overtime_type' => $detail['overtime_type'],
                    'qty_plan' => $detail['overtime_type'] === 'quantitative' ? $detail['qty_plan'] : null,
                    'qty_actual' => null,
                    'percentage_realization' => null,
                    'can_input_percentage' => false,
                    'notes' => $detail['notes'] ?? null,
                ]);
            }

            // ✅ UPDATE USAGE PLANNING (jika kategori = planned)
            if ($request->overtime_category === 'planned' && $request->planning_id) {
                $planning = OvertimePlanning::find($request->planning_id);
                if ($planning) {
                    $employeeCount = count($request->details);
                    $planning->incrementUsage($employeeCount);

                    \Log::info("Planning usage updated: {$planning->planning_number}, Used: {$planning->used_employees}, Remaining: {$planning->remaining_employees}");
                }
            }

            // ✅ PERBAIKAN: Filter flow jobs berdasarkan kategori
            $this->createApprovalRecords($overtimeRequest, $selectedEmployee, $request->overtime_category);
            $overtimeRequest->updateStatusAndColor();
        });

        return redirect()->route('overtime.index')->with('success', 'Pengajuan lembur berhasil dibuat');
    }

    private function validateDetailEmployees($details, $currentEmployee)
    {
        $eligibleEmployees = $this->getEligibleEmployeesForDetail($currentEmployee);
        $eligibleEmployeeIds = $eligibleEmployees->pluck('id')->toArray();

        foreach ($details as $index => $detail) {
            if (!in_array($detail['employee_id'], $eligibleEmployeeIds)) {
                $invalidEmployee = Employee::with('jobLevel')->find($detail['employee_id']);

                throw new \Exception(
                    "Karyawan '{$invalidEmployee->name}' ({$invalidEmployee->jobLevel->name}) tidak dapat diajukan lembur oleh {$currentEmployee->jobLevel->name}. " .
                        "Hanya dapat mengajukan untuk karyawan satu level atau di bawah level Anda."
                );
            }
        }
    }

    public function getEmployeesByDepartment(Request $request)
    {
        $currentUser = Auth::user();

        $currentEmployee = Employee::with(['department', 'jobLevel'])
            ->where('email', $currentUser->email)
            ->where('is_active', true)
            ->first();

        if (!$currentEmployee) {
            return response()->json(['error' => 'Current employee not found'], 404);
        }

        $employees = $this->getEligibleEmployeesForDetail($currentEmployee);
        $employees = $employees->where('department_id', $request->department_id);

        return response()->json($employees->values());
    }

    public function updatePercentage(Request $request, OvertimeRequest $overtime)
    {
        if (!$overtime->canInputPercentage(Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki wewenang untuk mengisi persentase realisasi.'
            ], 403);
        }

        $request->validate([
            'details' => 'required|array',
            'details.*.percentage_realization' => 'required|numeric|min:0|max:100',
        ]);

        try {
            foreach ($request->details as $detailId => $data) {
                $detail = OvertimeDetail::find($detailId);
                if (
                    $detail &&
                    $detail->overtime_request_id == $overtime->id &&
                    $detail->isQualitative() &&
                    $detail->canInputPercentageNow()
                ) {

                    $detail->update([
                        'percentage_realization' => $data['percentage_realization']
                    ]);
                }
            }

            $overtime->checkAndUpdateStatusAfterDataInput();

            return response()->json([
                'success' => true,
                'message' => 'Persentase realisasi berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error updating percentage: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(OvertimeRequest $overtime)
    {
        $overtime = OvertimeRequest::with([
            'requester',
            'requesterEmployee.jobLevel',
            'department',
            'planning', // ✅ TAMBAHAN
            'details.employee.jobLevel',
            'approvals.approverEmployee.jobLevel'
        ])->find($overtime->id);

        $canInputActual = $overtime->canInputActual();
        $canEditTime = $overtime->canEditTime(Auth::id());
        $canInputPercentage = $overtime->canInputPercentage(Auth::id());

        return view('overtime.show', compact('overtime', 'canInputActual', 'canEditTime', 'canInputPercentage'));
    }

    public function updateActual(Request $request, OvertimeRequest $overtime)
    {
        if (!$overtime->canInputActual()) {
            return redirect()->back()->with('error', 'Tidak dapat mengupdate qty actual. Pengajuan belum selesai diapprove.');
        }

        $request->validate([
            'details' => 'required|array',
            'details.*.qty_actual' => 'nullable|integer|min:0',
        ]);

        foreach ($request->details as $detailId => $data) {
            $detail = OvertimeDetail::find($detailId);
            if ($detail && $detail->overtime_request_id == $overtime->id) {
                $detail->update(['qty_actual' => $data['qty_actual']]);
            }
        }

        $overtime->checkAndUpdateStatusAfterDataInput();

        return redirect()->route('overtime.show', $overtime)->with('success', 'Qty Actual berhasil diupdate');
    }

    // ✅ PERBAIKAN: Tambahkan parameter $overtimeCategory
    private function createApprovalRecords(OvertimeRequest $request, $requesterEmployee, $overtimeCategory = 'unplanned')
    {
        \Log::info("=== CREATE APPROVAL RECORDS DEBUG ===");
        \Log::info("Request ID: {$request->id}, Category: {$overtimeCategory}");

        // ✅ Filter flow jobs berdasarkan kategori overtime
        $flowJobs = FlowJob::with('jobLevel')
            ->where('department_id', $request->department_id)
            ->where('is_active', true)
            ->where(function ($query) use ($overtimeCategory) {
                $query->where('applies_to', $overtimeCategory)
                    ->orWhere('applies_to', 'both');
            })
            ->orderBy('step_order')
            ->get();

        \Log::info("Found " . $flowJobs->count() . " flow jobs for category: {$overtimeCategory}");

        $requesterFlowJob = $flowJobs->where('job_level_id', $requesterEmployee->job_level_id)->first();

        if (!$requesterFlowJob) {
            \Log::error("Flow job tidak ditemukan untuk level jabatan pengaju");
            throw new \Exception('Flow job tidak ditemukan untuk level jabatan pengaju');
        }

        $nextFlowJobs = $flowJobs->where('step_order', '>', $requesterFlowJob->step_order);

        \Log::info("Found " . $nextFlowJobs->count() . " next flow jobs");

        foreach ($nextFlowJobs as $flowJob) {
            $approver = $this->findApproverForFlowJob($flowJob, $request->department_id);

            if ($approver) {
                try {
                    OvertimeApproval::create([
                        'overtime_request_id' => $request->id,
                        'approver_employee_id' => $approver->id,
                        'approver_level' => $flowJob->jobLevel->code,
                        'step_order' => $flowJob->step_order,
                        'step_name' => $flowJob->step_name,
                        'status' => 'pending',
                    ]);

                    \Log::info("✅ Created approval for {$flowJob->step_name} - Approver: {$approver->name}");
                } catch (\Exception $e) {
                    \Log::error("❌ Failed to create approval: " . $e->getMessage());
                }
            } else {
                \Log::error("❌ Approver not found for: {$flowJob->step_name}");
            }
        }

        \Log::info("=== END CREATE APPROVAL RECORDS DEBUG ===");
    }

    // ✅ FUNGSI BARU: Find approver helper
    private function findApproverForFlowJob($flowJob, $departmentId)
    {
        $jobLevelCode = $flowJob->jobLevel->code;

        switch ($jobLevelCode) {
            case 'DIV':
            case 'SUBDIV':
            case 'HRD':
                return Employee::with('jobLevel')
                    ->where('job_level_id', $flowJob->job_level_id)
                    ->where('is_active', true)
                    ->first();

            case 'DEPT':
            case 'ASDEPT':
            case 'SUBDEPT':
            case 'SECT':
                return Employee::with('jobLevel')
                    ->where('department_id', $departmentId)
                    ->where('job_level_id', $flowJob->job_level_id)
                    ->where('is_active', true)
                    ->first();

            default:
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

                return $approver;
        }
    }

    public function checkOvertimeEligibility(Request $request)
    {
        $employee = Employee::with('jobLevel')->find($request->employee_id);
        $departmentId = $request->department_id;

        if (!$employee) {
            return response()->json(['eligible' => false, 'message' => 'Employee not found']);
        }

        $flowJob = FlowJob::where('department_id', $departmentId)
            ->where('job_level_id', $employee->job_level_id)
            ->where('is_active', true)
            ->first();

        $eligible = !is_null($flowJob);

        return response()->json([
            'eligible' => $eligible,
            'employee_name' => $employee->name,
            'job_level' => $employee->jobLevel->name ?? 'Unknown',
            'show_success' => $eligible,
        ]);
    }

    public function updateTime(Request $request, OvertimeRequest $overtime)
    {
        if (!$overtime->canEditTime(Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki wewenang untuk mengubah jam lembur ini.'
            ], 403);
        }

        $request->validate([
            'details' => 'required|array',
            'details.*.start_time' => 'required',
            'details.*.end_time' => 'required',
        ]);

        try {
            foreach ($request->details as $detailId => $data) {
                $detail = OvertimeDetail::find($detailId);
                if ($detail && $detail->overtime_request_id == $overtime->id) {
                    $detail->update([
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                    ]);

                    $detail = $detail->fresh();
                }
            }

            $overtime->touch();

            return response()->json([
                'success' => true,
                'message' => 'Jam lembur berhasil diupdate'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error updating time: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        $overtime = OvertimeRequest::with(['details.employee', 'department'])
            ->findOrFail($id);

        if ($overtime->requester_id !== Auth::id()) {
            return redirect()->route('overtime.index')->with('error', 'Anda tidak berwenang mengedit pengajuan ini.');
        }

        $currentUser = Auth::user();
        $currentEmployee = Employee::with(['department', 'jobLevel'])
            ->where('email', $currentUser->email)
            ->where('is_active', true)
            ->first();

        $employees = $this->getEligibleEmployeesForDetail($currentEmployee);
        $departments = Department::where('is_active', true)->get();

        return view('overtime.edit', compact('overtime', 'employees', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $overtime = OvertimeRequest::with('details')->findOrFail($id);

        if ($overtime->requester_id !== Auth::id()) {
            return redirect()->route('overtime.index')->with('error', 'Anda tidak berwenang mengupdate pengajuan ini.');
        }

        $request->validate([
            'date' => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'details' => 'required|array|min:1',
            'details.*.employee_id' => 'required|exists:employees,id',
            'details.*.start_time' => 'required',
            'details.*.end_time' => 'required',
            'details.*.work_priority' => 'required',
            'details.*.process_type_id' => 'required|exists:process_types,id',
        ]);

        $currentUser = Auth::user();
        $currentEmployee = Employee::with(['department', 'jobLevel'])
            ->where('email', $currentUser->email)
            ->where('is_active', true)
            ->first();

        if ($currentEmployee) {
            $this->validateDetailEmployees($request->details, $currentEmployee);
        }

        DB::transaction(function () use ($request, $overtime) {
            $overtime->update([
                'date' => $request->date,
                'department_id' => $request->department_id,
            ]);

            $overtime->details()->delete();

            foreach ($request->details as $detail) {
                OvertimeDetail::create([
                    'overtime_request_id' => $overtime->id,
                    'employee_id' => $detail['employee_id'],
                    'start_time' => $detail['start_time'],
                    'end_time' => $detail['end_time'],
                    'work_priority' => $detail['work_priority'],
                    'process_type_id' => $detail['process_type_id'],
                    'qty_plan' => $detail['qty_plan'] ?? null,
                    'notes' => $detail['notes'] ?? null,
                ]);
            }

            $overtime->approvals()->update([
                'status' => 'pending',
                'notes' => null,
                'approved_at' => null,
            ]);

            $overtime->updateStatusAndColor();
        });

        return redirect()->route('overtime.index')->with('success', 'Pengajuan lembur berhasil diupdate');
    }

    public function destroy($id)
    {
        $overtime = OvertimeRequest::findOrFail($id);

        if ($overtime->requester_id !== Auth::id()) {
            return redirect()->route('overtime.index')->with('error', 'Anda tidak berwenang menghapus pengajuan ini.');
        }

        DB::transaction(function () use ($overtime) {
            // ✅ TAMBAHAN: Kembalikan kuota planning jika kategori = planned
            if ($overtime->overtime_category === 'planned' && $overtime->planning_id) {
                $planning = OvertimePlanning::find($overtime->planning_id);
                if ($planning) {
                    $employeeCount = $overtime->details()->count();
                    $planning->decrementUsage($employeeCount);

                    \Log::info("Planning usage restored: {$planning->planning_number}, Used: {$planning->used_employees}, Remaining: {$planning->remaining_employees}");
                }
            }

            $overtime->details()->delete();
            $overtime->approvals()->delete();
            $overtime->delete();
        });

        return redirect()->route('overtime.index')->with('success', 'Pengajuan lembur berhasil dihapus');
    }
}
