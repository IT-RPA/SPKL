<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\OvertimeApproval;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    // ✅ PERBAIKAN: Method untuk Division Head yang menangani multiple departemen
    public function divHeadIndex()
    {
        $currentEmployee = Employee::where('email', Auth::user()->email)->first();
        
        if (!$currentEmployee) {
            return redirect()->back()->with('error', 'Data karyawan tidak ditemukan');
        }

        // ✅ PERBAIKAN: Untuk Division Head, ambil semua approval dari semua departemen yang dia handle
        // Tidak terbatas pada departemen tertentu
        $approvals = OvertimeApproval::with([
            'overtimeRequest.requesterEmployee.jobLevel', 
            'overtimeRequest.department', 
            'overtimeRequest.approvals.approverEmployee.jobLevel',
            'overtimeRequest.details.employee',
            'approverEmployee.jobLevel'
        ])
        ->where('approver_employee_id', $currentEmployee->id)
        ->where('approver_level', 'DIV') // Atau sesuai dengan code job level Division Head
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return view('approvals.div-head', compact('approvals'));
    }

    // Method lain tetap sama seperti sebelumnya
    public function sectHeadIndex()
    {
        $approvals = $this->getApprovalsByStep('Section Head');
        return view('approvals.sect-head', compact('approvals'));
    }

    public function deptHeadIndex()
    {
        $approvals = $this->getApprovalsByStep('Department Head');
        return view('approvals.dept-head', compact('approvals'));
    }

    public function hrdIndex()
    {
        $approvals = $this->getApprovalsByStep('HRD');
        return view('approvals.hrd', compact('approvals'));
    }

    // ✅ MODIFIKASI: Method helper untuk step lain (selain Division Head)
    private function getApprovalsByStep($stepName)
    {
        $currentEmployee = Employee::where('email', Auth::user()->email)->first();
        
        if (!$currentEmployee) {
            return collect();
        }

        // Untuk level selain Division Head, masih terbatas per departemen
        $approvals = OvertimeApproval::with([
            'overtimeRequest.requesterEmployee.jobLevel', 
            'overtimeRequest.department', 
            'overtimeRequest.approvals.approverEmployee.jobLevel',
            'overtimeRequest.details.employee',
            'approverEmployee.jobLevel'
        ])
        ->where('approver_employee_id', $currentEmployee->id)
        ->where('step_name', 'like', '%' . $stepName . '%')
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return $approvals;
    }

    // Method approve, reject, dan lainnya tetap sama
    public function approve(Request $request, OvertimeApproval $approval)
    {
        $currentEmployee = Employee::where('email', Auth::user()->email)->first();
        
        if ($approval->approver_employee_id !== $currentEmployee->id) {
            return redirect()->back()->with('error', 'Anda tidak berwenang untuk approval ini');
        }

        if (!$this->canUserApproveNow($approval)) {
            return redirect()->back()->with('error', 'Belum giliran Anda untuk approve. Masih ada approval sebelumnya yang belum disetujui.');
        }

        $approval->update([
            'status' => 'approved',
            'approved_at' => now(),
            'notes' => $request->notes ?? 'Disetujui',
        ]);

        $approval->overtimeRequest->updateStatusAndColor();

        \Log::info("Approval approved - ID: {$approval->id}, Step: {$approval->step_name}, User: " . Auth::user()->name);

        return redirect()->back()->with('success', 'Pengajuan berhasil disetujui');
    }

    public function reject(Request $request, OvertimeApproval $approval)
    {
        $request->validate([
            'reason' => 'required',
        ]);

        $currentEmployee = Employee::where('email', Auth::user()->email)->first();
        
        if ($approval->approver_employee_id !== $currentEmployee->id) {
            return redirect()->back()->with('error', 'Anda tidak berwenang untuk approval ini');
        }

        if (!$this->canUserApproveNow($approval)) {
            return redirect()->back()->with('error', 'Belum giliran Anda untuk menolak. Masih ada approval sebelumnya yang belum disetujui.');
        }

        DB::beginTransaction();
        
        try {
            $approval->update([
                'status' => 'rejected',
                'approved_at' => now(),
                'notes' => $request->reason,
            ]);

            $pendingApprovals = OvertimeApproval::where('overtime_request_id', $approval->overtime_request_id)
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

            $approval->overtimeRequest->updateStatusAndColor();

            DB::commit();

            \Log::info("Approval rejected - ID: {$approval->id}, Step: {$approval->step_name}, Reason: {$request->reason}");

            return redirect()->back()->with('success', 'Pengajuan berhasil ditolak dan flow approval dihentikan');

        } catch (\Exception $e) {
            DB::rollback();
            
            \Log::error("Error rejecting approval: " . $e->getMessage());
            
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menolak pengajuan: ' . $e->getMessage());
        }
    }

    private function canUserApproveNow(OvertimeApproval $approval)
    {
        if ($approval->status !== 'pending') {
            return false;
        }

        $previousPendingApproval = OvertimeApproval::where('overtime_request_id', $approval->overtime_request_id)
            ->where('step_order', '<', $approval->step_order)
            ->where('status', 'pending')
            ->exists();
        
        return !$previousPendingApproval;
    }

    public function overtimeDetail(OvertimeApproval $approval)
    {
        $request = OvertimeRequest::with([
            'requester',
            'requesterEmployee.jobLevel', 
            'department', 
            'details.employee', 
            'approvals.approverEmployee.jobLevel'
        ])->find($approval->overtime_request_id);

        \Log::info("=== APPROVAL DETAIL DEBUG ===");
        \Log::info("Request ID: {$request->id}");
        \Log::info("Current Approval ID: {$approval->id}");
        \Log::info("Current Approval Status: {$approval->status}");
        \Log::info("Request last updated: " . $request->updated_at);
        
        foreach($request->details as $detail) {
            \Log::info("Detail ID: {$detail->id}, Start: {$detail->start_time}, End: {$detail->end_time}, Updated: {$detail->updated_at}");
        }

        $approvalHistory = $request->approvals->map(function($app) {
            return [
                'step_name' => $app->step_name,
                'level' => $app->approverEmployee ? $app->approverEmployee->jobLevel->name : 'Belum ditentukan',
                'status' => ucfirst($app->status),
                'date' => $app->approved_at ? $app->approved_at->format('d/m/Y H:i') : null,
                'notes' => $app->notes,
                'approver_name' => $app->approverEmployee ? $app->approverEmployee->name : 'Belum ditentukan'
            ];
        });

        $canEditTime = $request->canEditTime(Auth::id());
        
        $currentEmployee = Employee::where('email', Auth::user()->email)->first();
        $isCurrentUserApprover = ($approval->approver_employee_id === $currentEmployee->id);
        $canApproveNow = $isCurrentUserApprover && $this->canUserApproveNow($approval);
        
        \Log::info("Can Edit Time: " . ($canEditTime ? 'TRUE' : 'FALSE') . " for User ID: " . Auth::id());
        \Log::info("Is Current User Approver: " . ($isCurrentUserApprover ? 'TRUE' : 'FALSE'));
        \Log::info("Can Approve Now: " . ($canApproveNow ? 'TRUE' : 'FALSE'));
        \Log::info("=== END APPROVAL DEBUG ===");

        $data = [
            'overtime_id' => $request->id,
            'can_edit_time' => $canEditTime,
            'request_number' => $request->request_number,
            'requester_name' => $request->requesterEmployee ? $request->requesterEmployee->name : $request->requester->name,
            'requester_level' => $request->requesterEmployee ? $request->requesterEmployee->jobLevel->name : $request->requester_level,
            'department_name' => $request->department->name,
            'date' => $request->date->format('d/m/Y'),
            'approval_history' => $approvalHistory,
            
            'has_pending_approval' => $canApproveNow,
            'current_approval_status' => $approval->status,
            'status' => $approval->status,
            'approval_status' => $approval->status,
            'can_approve' => $canApproveNow,
            'current_approval_id' => $approval->id,
            'is_user_turn' => $canApproveNow,
            'is_current_user_approver' => $isCurrentUserApprover,
            
            'details' => $request->details->map(function($detail) {
                return [
                    'id' => $detail->id,
                    'employee_name' => $detail->employee->name,
                    'employee_id' => $detail->employee->employee_id,
                    'start_time' => $detail->start_time,
                    'end_time' => $detail->end_time,
                    'work_priority' => $detail->work_priority,
                    'work_process' => $detail->work_process,
                    'qty_plan' => $detail->qty_plan,
                    'notes' => $detail->notes,
                ];
            }),
        ];

        return response()->json($data);
    }
}