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

        return view('overtime.index', compact('requests'));
    }

    public function create()
    {
        $employees = Employee::with(['department', 'jobLevel'])
            ->where('is_active', true)
            ->get();
        $departments = Department::where('is_active', true)->get();
        
        return view('overtime.create', compact('employees', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id', // Pengaju berdasarkan employee yang dipilih
            'date' => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'details' => 'required|array|min:1',
            'details.*.employee_id' => 'required|exists:employees,id',
            'details.*.start_time' => 'required',
            'details.*.end_time' => 'required',
            'details.*.work_priority' => 'required',
            'details.*.work_process' => 'required',
        ]);

        DB::transaction(function () use ($request) {
            // Get requester employee data
            $requesterEmployee = Employee::with('jobLevel')->find($request->employee_id);
            
            $overtimeRequest = OvertimeRequest::create([
                'request_number' => OvertimeRequest::generateRequestNumber(),
                'requester_id' => Auth::id(),
                'requester_employee_id' => $requesterEmployee->id,
                'requester_level' => $requesterEmployee->jobLevel->code,
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
                    'qty_plan' => $detail['qty_plan'] ?? null,
                    'qty_actual' => null,
                    'notes' => $detail['notes'] ?? null,
                ]);
            }

            // Create approval records based on flow job
            $this->createApprovalRecords($overtimeRequest, $requesterEmployee);
            $overtimeRequest->updateStatusAndColor();
        });

        return redirect()->route('overtime.index')->with('success', 'Pengajuan lembur berhasil dibuat');
    }

    public function show(OvertimeRequest $overtime)
{
    // ✅ PERBAIKAN: FORCE FRESH DATA dari database (bypass cache)
    $overtime = OvertimeRequest::with([
        'requester', 
        'requesterEmployee.jobLevel', 
        'department', 
        'details.employee', 
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
    
    \Log::info("Can Edit Time: " . ($canEditTime ? 'TRUE' : 'FALSE') . " for User ID: " . Auth::id());
    \Log::info("=== END DEBUG ===");
    
    return view('overtime.show', compact('overtime', 'canInputActual', 'canEditTime'));
}

    public function updateActual(Request $request, OvertimeRequest $overtime)
    {
        // ✅ PERBAIKAN: Validasi bahwa status harus 'act'
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

        // ✅ PERBAIKAN: Update status menjadi completed jika semua actual sudah diisi
        $incompleteActuals = $overtime->details()
            ->whereNotNull('qty_plan')
            ->whereNull('qty_actual')
            ->count();

        if ($incompleteActuals == 0) {
            $overtime->update([
                'status' => 'completed', 
                'status_color' => 'green'
            ]);
        }

        return redirect()->route('overtime.show', $overtime)->with('success', 'Qty Actual berhasil diupdate');
    }

   private function createApprovalRecords(OvertimeRequest $request, $requesterEmployee)
    {
        // Get flow job untuk departemen yang mengajukan
        $flowJobs = FlowJob::where('department_id', $request->department_id)
            ->where('is_active', true)
            ->orderBy('step_order')
            ->get();

        // Cari posisi requester dalam flow
        $requesterFlowJob = $flowJobs->where('job_level_id', $requesterEmployee->job_level_id)->first();
        
        if (!$requesterFlowJob) {
            throw new \Exception('Flow job tidak ditemukan untuk level jabatan pengaju');
        }

        // Buat approval untuk step selanjutnya
        $nextFlowJobs = $flowJobs->where('step_order', '>', $requesterFlowJob->step_order);

        foreach ($nextFlowJobs as $flowJob) {
            $approver = null;

            // ✅ PERBAIKAN: Khusus untuk HRD, cari di department HRD
            if ($flowJob->jobLevel->code === 'HRD' || $flowJob->step_name === 'Approval HRD') {
                $approver = Employee::where('job_level_id', $flowJob->job_level_id)
                    ->where('is_active', true)
                    ->first(); // HRD bisa dari department mana saja
            } else {
                // Untuk level lain, cari di department yang sama
                $approver = Employee::where('department_id', $request->department_id)
                    ->where('job_level_id', $flowJob->job_level_id)
                    ->where('is_active', true)
                    ->first();
            }

            if ($approver) {
                OvertimeApproval::create([
                    'overtime_request_id' => $request->id,
                    'approver_employee_id' => $approver->id,
                    'approver_level' => $flowJob->jobLevel->code,
                    'step_order' => $flowJob->step_order,
                    'step_name' => $flowJob->step_name,
                    'status' => 'pending',
                ]);
            } else {
                // ✅ LOG jika approver tidak ditemukan
                \Log::warning("Approver tidak ditemukan untuk step: {$flowJob->step_name}, Job Level: {$flowJob->jobLevel->code}");
            }
        }
    }

    // Function untuk get employees berdasarkan department via AJAX
    public function getEmployeesByDepartment(Request $request)
    {
        $employees = Employee::with('jobLevel')
            ->where('department_id', $request->department_id)
            ->where('is_active', true)
            ->get();

        return response()->json($employees);
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
            'show_success' => $eligible, // Untuk menampilkan toast success jika valid
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
    \Log::info("Input data: " . json_encode($request->input('details')));

    try {
        foreach ($request->details as $detailId => $data) {
            $detail = OvertimeDetail::find($detailId);
            if ($detail && $detail->overtime_request_id == $overtime->id) {
                
                \Log::info("BEFORE UPDATE - Detail ID: {$detail->id}, Start: {$detail->start_time}, End: {$detail->end_time}");
                
                $detail->update([
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                ]);
                
                // ✅ PERBAIKAN: Fresh reload untuk memastikan data tersimpan
                $detail = $detail->fresh();
                
                \Log::info("AFTER UPDATE - Detail ID: {$detail->id}, Start: {$detail->start_time}, End: {$detail->end_time}, Updated: {$detail->updated_at}");
            } else {
                \Log::warning("Detail not found or not owned by overtime - Detail ID: {$detailId}, Overtime ID: {$overtime->id}");
            }
        }

        // ✅ PERBAIKAN: Update timestamp overtime request juga
        $overtime->touch();

        \Log::info("Time update completed successfully");
        \Log::info("=== END UPDATE TIME DEBUG ===");

        return response()->json([
            'success' => true,
            'message' => 'Jam lembur berhasil diupdate'
        ]);
        
    } catch (\Exception $e) {
        \Log::error("Error updating time: " . $e->getMessage());
        \Log::error("Stack trace: " . $e->getTraceAsString());
        
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

        $employees = Employee::with(['department', 'jobLevel'])
            ->where('is_active', true)
            ->get();
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