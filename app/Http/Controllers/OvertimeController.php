<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\OvertimeDetail;
use App\Models\OvertimeApproval;
use App\Models\Employee;
use App\Models\Department;
use App\Models\FlowJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OvertimeController extends Controller
{
    public function index()
    {
        $requests = OvertimeRequest::with(['requester', 'department', 'details.employee'])
            ->where('requester_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // ✅ TAMBAHAN: Cek apakah ada pengajuan yang perlu input data
        $hasIncompleteRequest = OvertimeRequest::where('requester_id', Auth::id())
            ->where('status', 'approved') // Status 'approved' = Perlu Input Data
            ->exists();

        return view('overtime.index', compact('requests', 'hasIncompleteRequest'));
    }

    public function create()
    {
        // ✅ VALIDASI AWAL: Cek pengajuan yang belum selesai input data
        $hasIncompleteRequest = OvertimeRequest::where('requester_id', Auth::id())
            ->where('status', 'approved') // Status 'approved' = Perlu Input Data
            ->exists();

        if ($hasIncompleteRequest) {
            return redirect()->route('overtime.index')
                ->with('error', 'Anda tidak dapat membuat pengajuan baru karena masih ada pengajuan yang perlu diselesaikan realisasinya. Harap lengkapi input qty actual/percentage terlebih dahulu.');
        }

        $currentUser = Auth::user();
        
        // ✅ Cari employee berdasarkan user yang login
        $currentEmployee = Employee::with(['department', 'jobLevel'])
            ->where('email', $currentUser->email)
            ->where('is_active', true)
            ->first();
        
        // Jika tidak ditemukan berdasarkan email, coba berdasarkan nama
        if (!$currentEmployee) {
            $currentEmployee = Employee::with(['department', 'jobLevel'])
                ->where('name', 'LIKE', '%' . $currentUser->name . '%')
                ->where('is_active', true)
                ->first();
        }
        
        if (!$currentEmployee) {
            return redirect()->route('overtime.index')
                ->with('error', 'Data karyawan tidak ditemukan untuk akun Anda. Hubungi admin untuk mapping data karyawan.');
        }

        // ✅ Hanya tampilkan departemen milik user yang login
        $departments = Department::where('id', $currentEmployee->department_id)
            ->where('is_active', true)
            ->get();
        
        // ✅ PERBAIKAN UTAMA: Filter employees berdasarkan hierarki jabatan
        $employees = $this->getEligibleEmployeesForDetail($currentEmployee);
        
        // ✅ Untuk dropdown pengaju, hanya user yang login saja
        $eligibleRequesters = Employee::with(['department', 'jobLevel'])
            ->where('id', $currentEmployee->id)
            ->where('is_active', true)
            ->get();
        
        $currentEmployeeData = $currentEmployee;
        
        return view('overtime.create', compact('employees', 'departments', 'currentEmployeeData', 'eligibleRequesters'));
    }

    /**
     * ✅ FUNGSI BARU: Mendapatkan employee yang eligible untuk detail lembur
     * berdasarkan hierarki jabatan pengaju
     */
    private function getEligibleEmployeesForDetail($currentEmployee)
    {
        // Ambil level order pengaju
        $requesterLevelOrder = $currentEmployee->jobLevel->level_order ?? 999;
        
        \Log::info("=== ELIGIBLE EMPLOYEES DEBUG ===");
        \Log::info("Requester: {$currentEmployee->name}");
        \Log::info("Requester Level: {$currentEmployee->jobLevel->name} (Order: {$requesterLevelOrder})");
        \Log::info("Requester Department: {$currentEmployee->department->name}");
        
        // Filter employees yang bisa diajukan lembur:
        // 1. Dalam departemen yang sama
        // 2. Level order SAMA ATAU LEBIH TINGGI (angka lebih besar = level lebih rendah)
        // 3. Aktif
        $eligibleEmployees = Employee::with(['department', 'jobLevel'])
            ->where('department_id', $currentEmployee->department_id)
            ->where('is_active', true)
            ->whereHas('jobLevel', function($query) use ($requesterLevelOrder) {
                $query->where('level_order', '>=', $requesterLevelOrder);
            })
            ->orderBy('job_level_id', 'asc')
            ->get();
        
        \Log::info("Found " . $eligibleEmployees->count() . " eligible employees:");
        foreach ($eligibleEmployees as $emp) {
            \Log::info("- {$emp->name} ({$emp->jobLevel->name}, Order: {$emp->jobLevel->level_order})");
        }
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
            'details' => 'required|array|min:1',
            'details.*.employee_id' => 'required|exists:employees,id',
            'details.*.start_time' => 'required',
            'details.*.end_time' => 'required',
            'details.*.work_priority' => 'required',
            'details.*.work_process' => 'required',
            'details.*.overtime_type' => 'required|in:quantitative,qualitative',
            'details.*.qty_plan' => 'required_if:details.*.overtime_type,quantitative|nullable|integer|min:1',
        ]);

        // ✅ VALIDASI TAMBAHAN: Pastikan semua karyawan di detail eligible
        $this->validateDetailEmployees($request->details, $currentEmployee);

        if ($selectedEmployee->department_id != $request->department_id) {
            return redirect()->route('overtime.create')
                ->with('error', 'Departemen tidak sesuai dengan data karyawan.');
        }

        DB::transaction(function () use ($request, $selectedEmployee) {
            $overtimeRequest = OvertimeRequest::create([
                'request_number' => OvertimeRequest::generateRequestNumber(),
                'requester_id' => Auth::id(),
                'requester_employee_id' => $selectedEmployee->id,
                'requester_level' => $selectedEmployee->jobLevel->code,
                'date' => $request->date,
                'department_id' => $request->department_id,
            ]);

            foreach ($request->details as $detail) {
                OvertimeDetail::create([
                    'overtime_request_id' => $overtimeRequest->id,
                    'employee_id' => $detail['employee_id'],
                    'start_time' => $detail['start_time'],
                    'end_time' => $detail['end_time'],
                    'work_priority' => $detail['work_priority'],
                    'work_process' => $detail['work_process'],
                    'overtime_type' => $detail['overtime_type'],
                    'qty_plan' => $detail['overtime_type'] === 'quantitative' ? $detail['qty_plan'] : null,
                    'qty_actual' => null,
                    'percentage_realization' => null,
                    'can_input_percentage' => false,
                    'notes' => $detail['notes'] ?? null,
                ]);
            }

            $this->createApprovalRecords($overtimeRequest, $selectedEmployee);
            $overtimeRequest->updateStatusAndColor();
        });

        return redirect()->route('overtime.index')->with('success', 'Pengajuan lembur berhasil dibuat');
    }

    /**
     * ✅ FUNGSI BARU: Validasi bahwa semua employee di detail eligible
     */
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

    // Function untuk get employees berdasarkan department via AJAX (DIPERBAIKI)
    public function getEmployeesByDepartment(Request $request)
    {
        $currentUser = Auth::user();
        
        // Cari current employee
        $currentEmployee = Employee::with(['department', 'jobLevel'])
            ->where('email', $currentUser->email)
            ->where('is_active', true)
            ->first();
        
        if (!$currentEmployee) {
            return response()->json(['error' => 'Current employee not found'], 404);
        }
        
        // ✅ PERBAIKAN: Return hanya employees yang eligible berdasarkan hierarki
        $employees = $this->getEligibleEmployeesForDetail($currentEmployee);
        
        // Filter berdasarkan department yang diminta (double check)
        $employees = $employees->where('department_id', $request->department_id);
        
        return response()->json($employees->values());
    }

    public function updatePercentage(Request $request, OvertimeRequest $overtime)
    {
        // Validasi permission
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

        \Log::info("=== UPDATE PERCENTAGE DEBUG ===");
        \Log::info("Overtime ID: {$overtime->id}, Current Status: {$overtime->status}");

        try {
            foreach ($request->details as $detailId => $data) {
                $detail = OvertimeDetail::find($detailId);
                if ($detail &&
                    $detail->overtime_request_id == $overtime->id &&
                    $detail->isQualitative() &&
                    $detail->canInputPercentageNow()) {

                    \Log::info("Updating detail ID: {$detail->id}, Old percentage: {$detail->percentage_realization}, New percentage: {$data['percentage_realization']}%");
                    
                    $detail->update([
                        'percentage_realization' => $data['percentage_realization']
                    ]);
                }
            }

            // ✅ Trigger pengecekan status setelah update percentage
            $overtime->checkAndUpdateStatusAfterDataInput();
            
            \Log::info("After update - Status: {$overtime->fresh()->status}, Color: {$overtime->fresh()->status_color}");
            \Log::info("=== END UPDATE PERCENTAGE DEBUG ===");

            return response()->json([
                'success' => true,
                'message' => 'Persentase realisasi berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            \Log::error("Error updating percentage: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupdate persentase: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(OvertimeRequest $overtime)
    {
        // ✅ FORCE FRESH DATA dari database (bypass cache)
        $overtime = OvertimeRequest::with([
            'requester', 
            'requesterEmployee.jobLevel', 
            'department', 
            'details.employee.jobLevel', 
            'approvals.approverEmployee.jobLevel'
        ])->find($overtime->id);
        
        // ✅ Debug: Log semua detail untuk memastikan data terbaru
        \Log::info("=== OVERTIME SHOW DEBUG ===");
        \Log::info("Overtime ID: {$overtime->id}");
        \Log::info("Last updated: " . $overtime->updated_at);
        
        foreach($overtime->details as $detail) {
            \Log::info("Detail ID: {$detail->id}, Employee: {$detail->employee->name}, Start: {$detail->start_time}, End: {$detail->end_time}, Updated: {$detail->updated_at}");
        }
        
        $canInputActual = $overtime->canInputActual();
        $canEditTime = $overtime->canEditTime(Auth::id());
        $canInputPercentage = $overtime->canInputPercentage(Auth::id()); 
        
        \Log::info("Show overtime - Can Input Percentage: " . ($canInputPercentage ? 'TRUE' : 'FALSE') . " for User ID: " . Auth::id());
        
        return view('overtime.show', compact('overtime', 'canInputActual', 'canEditTime', 'canInputPercentage'));
    }

    public function updateActual(Request $request, OvertimeRequest $overtime)
    {
        // ✅ Validasi bahwa status harus 'approved' atau 'act'
        if (!$overtime->canInputActual()) {
            return redirect()->back()->with('error', 'Tidak dapat mengupdate qty actual. Pengajuan belum selesai diapprove.');
        }

        $request->validate([
            'details' => 'required|array',
            'details.*.qty_actual' => 'nullable|integer|min:0',
        ]);

        \Log::info("=== UPDATE ACTUAL DEBUG ===");
        \Log::info("Overtime ID: {$overtime->id}, Current Status: {$overtime->status}");

        foreach ($request->details as $detailId => $data) {
            $detail = OvertimeDetail::find($detailId);
            if ($detail && $detail->overtime_request_id == $overtime->id) {
                \Log::info("Updating detail ID: {$detail->id}, Old actual: {$detail->qty_actual}, New actual: {$data['qty_actual']}");
                $detail->update(['qty_actual' => $data['qty_actual']]);
            }
        }

        // ✅ Trigger pengecekan status setelah update data
        $overtime->checkAndUpdateStatusAfterDataInput();

        \Log::info("After update - Status: {$overtime->fresh()->status}, Color: {$overtime->fresh()->status_color}");
        \Log::info("=== END UPDATE ACTUAL DEBUG ===");

        return redirect()->route('overtime.show', $overtime)->with('success', 'Qty Actual berhasil diupdate');
    }

    private function createApprovalRecords(OvertimeRequest $request, $requesterEmployee)
    {
        \Log::info("=== CREATE APPROVAL RECORDS DEBUG ===");
        \Log::info("Request ID: {$request->id}, Department: {$request->department_id}");
        \Log::info("Requester: {$requesterEmployee->name}, Job Level ID: {$requesterEmployee->job_level_id}");
        
        // Get flow job untuk departemen yang mengajukan
        $flowJobs = FlowJob::with('jobLevel')->where('department_id', $request->department_id)
            ->where('is_active', true)
            ->orderBy('step_order')
            ->get();

        \Log::info("Found " . $flowJobs->count() . " flow jobs for department {$request->department_id}");
        foreach ($flowJobs as $fj) {
            \Log::info("Flow Job: {$fj->step_name}, Job Level: {$fj->jobLevel->name} ({$fj->jobLevel->code}), Step Order: {$fj->step_order}");
        }

        // Cari posisi requester dalam flow
        $requesterFlowJob = $flowJobs->where('job_level_id', $requesterEmployee->job_level_id)->first();
        
        if (!$requesterFlowJob) {
            \Log::error("Flow job tidak ditemukan untuk level jabatan pengaju: Job Level ID {$requesterEmployee->job_level_id}");
            throw new \Exception('Flow job tidak ditemukan untuk level jabatan pengaju');
        }

        \Log::info("Requester Flow Job: {$requesterFlowJob->step_name}, Step Order: {$requesterFlowJob->step_order}");

        // Buat approval untuk step selanjutnya
        $nextFlowJobs = $flowJobs->where('step_order', '>', $requesterFlowJob->step_order);
        
        \Log::info("Found " . $nextFlowJobs->count() . " next flow jobs");

        foreach ($nextFlowJobs as $flowJob) {
            $approver = null;

            \Log::info("Processing flow job: {$flowJob->step_name}, Job Level: {$flowJob->jobLevel->code}");

            // ✅ Logika pencarian approver yang lebih komprehensif
            switch ($flowJob->jobLevel->code) {
                case 'DIV':
                    \Log::info("Searching for Division Head approver...");
                    $approver = Employee::with('jobLevel')
                        ->where('job_level_id', $flowJob->job_level_id)
                        ->where('is_active', true)
                        ->first();
                    break;
                    
                case 'SUBDIV':
                    \Log::info("Searching for Sub Division Head approver...");
                    $approver = Employee::with('jobLevel')
                        ->where('job_level_id', $flowJob->job_level_id)
                        ->where('is_active', true)
                        ->first();
                    break;
                    
                case 'HRD':
                    \Log::info("Searching for HRD approver...");
                    $approver = Employee::with('jobLevel')
                        ->where('job_level_id', $flowJob->job_level_id)
                        ->where('is_active', true)
                        ->first();
                    break;
                    
                case 'DEPT':
                    \Log::info("Searching for Department Head approver in department {$request->department_id}...");
                    $approver = Employee::with('jobLevel')
                        ->where('department_id', $request->department_id)
                        ->where('job_level_id', $flowJob->job_level_id)
                        ->where('is_active', true)
                        ->first();
                    break;

                case 'SUBDEPT':
                    \Log::info("Searching for Sub Department Head approver in department {$request->department_id}...");
                    $approver = Employee::with('jobLevel')
                        ->where('department_id', $request->department_id)
                        ->where('job_level_id', $flowJob->job_level_id)
                        ->where('is_active', true)
                        ->first();
                    break;
                    
                case 'SECT':
                    \Log::info("Searching for Section Head approver in department {$request->department_id}...");
                    $approver = Employee::with('jobLevel')
                        ->where('department_id', $request->department_id)
                        ->where('job_level_id', $flowJob->job_level_id)
                        ->where('is_active', true)
                        ->first();
                    break;
                    
                default:
                    // ✅ Untuk job level lain, cari di department yang sama
                    \Log::info("Searching for {$flowJob->jobLevel->code} approver in department {$request->department_id}...");
                    $approver = Employee::with('jobLevel')
                        ->where('department_id', $request->department_id)
                        ->where('job_level_id', $flowJob->job_level_id)
                        ->where('is_active', true)
                        ->first();
                        
                    // ✅ JIKA TIDAK DITEMUKAN, COBA CARI GLOBAL
                    if (!$approver) {
                        \Log::info("Not found in department, searching globally for {$flowJob->jobLevel->code}...");
                        $approver = Employee::with('jobLevel')
                            ->where('job_level_id', $flowJob->job_level_id)
                            ->where('is_active', true)
                            ->first();
                    }
                    break;
            }

            \Log::info("Approver search result: " . ($approver ? "{$approver->name} (ID: {$approver->id})" : 'NOT FOUND'));

            if ($approver) {
                try {
                    $approvalRecord = OvertimeApproval::create([
                        'overtime_request_id' => $request->id,
                        'approver_employee_id' => $approver->id,
                        'approver_level' => $flowJob->jobLevel->code,
                        'step_order' => $flowJob->step_order,
                        'step_name' => $flowJob->step_name,
                        'status' => 'pending',
                    ]);
                    
                    \Log::info("✅ SUCCESS: Created approval ID {$approvalRecord->id} for {$flowJob->step_name} - Approver: {$approver->name}");
                    
                } catch (\Exception $e) {
                    \Log::error("❌ FAILED to create approval for {$flowJob->step_name}: " . $e->getMessage());
                }
            } else {
                \Log::error("❌ CRITICAL: Approver tidak ditemukan untuk step: {$flowJob->step_name}, Job Level: {$flowJob->jobLevel->code}");
            }
        }
        
        \Log::info("=== END CREATE APPROVAL RECORDS DEBUG ===");
    }

    public function checkOvertimeEligibility(Request $request)
    {
        $employee = Employee::with('jobLevel')->find($request->employee_id);
        $departmentId = $request->department_id;
        
        if (!$employee) {
            return response()->json(['eligible' => false, 'message' => 'Employee not found']);
        }
        
        // Cek apakah employee bisa mengajukan overtime di department ini
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
        // Validasi permission
        if (!$overtime->canEditTime(Auth::id())) {
            \Log::warning("Access denied for updateTime - User ID: " . Auth::id() . ", Overtime ID: {$overtime->id}");
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

        \Log::info("=== UPDATE TIME DEBUG ===");
        \Log::info("User: " . Auth::user()->name . " (ID: " . Auth::id() . ")");
        \Log::info("Overtime ID: {$overtime->id}");

        try {
            foreach ($request->details as $detailId => $data) {
                $detail = OvertimeDetail::find($detailId);
                if ($detail && $detail->overtime_request_id == $overtime->id) {
                    
                    \Log::info("BEFORE UPDATE - Detail ID: {$detail->id}, Start: {$detail->start_time}, End: {$detail->end_time}");
                    
                    $detail->update([
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                    ]);
                    
                    // ✅ Fresh reload untuk memastikan data tersimpan
                    $detail = $detail->fresh();
                    
                    \Log::info("AFTER UPDATE - Detail ID: {$detail->id}, Start: {$detail->start_time}, End: {$detail->end_time}, Updated: {$detail->updated_at}");
                }
            }

            // ✅ Update timestamp overtime request juga
            $overtime->touch();

            \Log::info("Time update completed successfully");
            \Log::info("=== END UPDATE TIME DEBUG ===");

            return response()->json([
                'success' => true,
                'message' => 'Jam lembur berhasil diupdate'
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error updating time: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupdate jam lembur: ' . $e->getMessage()
            ], 500);
        }
    }

    // Edit form pengajuan lembur
    public function edit($id)
    {
        $overtime = OvertimeRequest::with(['details.employee', 'department'])
            ->findOrFail($id);

        // Validasi: hanya requester yang boleh edit
        if ($overtime->requester_id !== Auth::id()) {
            return redirect()->route('overtime.index')->with('error', 'Anda tidak berwenang mengedit pengajuan ini.');
        }

        $currentUser = Auth::user();
        $currentEmployee = Employee::with(['department', 'jobLevel'])
            ->where('email', $currentUser->email)
            ->where('is_active', true)
            ->first();

        // ✅ Filter employees berdasarkan hierarki untuk edit juga
        $employees = $this->getEligibleEmployeesForDetail($currentEmployee);
        $departments = Department::where('is_active', true)->get();

        return view('overtime.edit', compact('overtime', 'employees', 'departments'));
    }

    // Update pengajuan lembur
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
            'details.*.work_process' => 'required',
        ]);

        // ✅ Validasi hierarki juga untuk update
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

            // Hapus detail lama, insert detail baru
            $overtime->details()->delete();

            foreach ($request->details as $detail) {
                OvertimeDetail::create([
                    'overtime_request_id' => $overtime->id,
                    'employee_id' => $detail['employee_id'],
                    'start_time' => $detail['start_time'],
                    'end_time' => $detail['end_time'],
                    'work_priority' => $detail['work_priority'],
                    'work_process' => $detail['work_process'],
                    'qty_plan' => $detail['qty_plan'] ?? null,
                    'notes' => $detail['notes'] ?? null,
                ]);
            }

            // Reset status approval jadi pending (jika perlu, bisa optional)
            $overtime->approvals()->update([
                'status' => 'pending',
                'notes' => null,
                'approved_at' => null,
            ]);

            $overtime->updateStatusAndColor();
        });

        return redirect()->route('overtime.index')->with('success', 'Pengajuan lembur berhasil diupdate');
    }

    // Hapus pengajuan lembur
    public function destroy($id)
    {
        $overtime = OvertimeRequest::findOrFail($id);

        if ($overtime->requester_id !== Auth::id()) {
            return redirect()->route('overtime.index')->with('error', 'Anda tidak berwenang menghapus pengajuan ini.');
        }

        DB::transaction(function () use ($overtime) {
            $overtime->details()->delete();
            $overtime->approvals()->delete();
            $overtime->delete();
        });

        return redirect()->route('overtime.index')->with('success', 'Pengajuan lembur berhasil dihapus');
    }
}   