<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\OvertimeApproval;
use App\Models\Employee;
use App\Models\JobLevel;
use App\Models\OvertimeDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    public function sectHeadIndex(Request $request)
    {
        $approvals = $this->getApprovalsWithPercentageNeeded($request->job_level);

        $joblevel = JobLevel::where('code', $request->job_level)->first();

        return view('approvals.master', compact('approvals', 'joblevel'));
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
            'details.processType',
            'details.rejectedBy',
            'approvals.approverEmployee.jobLevel'
        ])->find($approval->overtime_request_id);

        \Log::info("=== APPROVAL DETAIL DEBUG ===");
        \Log::info("Request ID: {$request->id}");
        \Log::info("Request Status: {$request->status}");
        \Log::info("Current Approval ID: {$approval->id}");
        \Log::info("Current Approval Status: {$approval->status}");

        // Debug setiap detail
        foreach ($request->details as $detail) {
            \Log::info("Detail ID: {$detail->id}, Type: {$detail->overtime_type}, Can Input Now: " .
                ($detail->canInputPercentageNow() ? 'YES' : 'NO'));
        }

        $approvalHistory = $request->approvals->map(function ($app) {
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
        $canInputPercentage = $request->canInputPercentage(Auth::id());

        $currentEmployee = Employee::where('email', Auth::user()->email)->first();
        $isCurrentUserApprover = ($approval->approver_employee_id === $currentEmployee->id);
        $canApproveNow = $isCurrentUserApprover && $this->canUserApproveNow($approval);

        // Debug tambahan untuk percentage
        $hasQualitativeDetails = $request->details->where('overtime_type', 'qualitative')->count() > 0;
        $qualitativeReadyCount = $request->details->where('overtime_type', 'qualitative')
            ->filter(function ($detail) {
                return $detail->canInputPercentageNow();
            })->count();

        \Log::info("Can Edit Time: " . ($canEditTime ? 'TRUE' : 'FALSE'));
        \Log::info("Can Input Percentage: " . ($canInputPercentage ? 'TRUE' : 'FALSE'));
        \Log::info("Has Qualitative: " . ($hasQualitativeDetails ? 'TRUE' : 'FALSE'));
        \Log::info("Qualitative Ready Count: " . $qualitativeReadyCount);
        \Log::info("=== END APPROVAL DEBUG ===");

        $data = [
            'overtime_id' => $request->id,
            'can_edit_time' => $canEditTime,
            'can_input_percentage' => $canInputPercentage,
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

            'details' => $request->details->map(function ($detail) {
                $canInputNow = false;
                try {
                    $canInputNow = $detail->canInputPercentageNow();
                } catch (\Exception $e) {
                    \Log::error("Error calling canInputPercentageNow for detail {$detail->id}: " . $e->getMessage());
                }

                return [
                    'id' => $detail->id,
                    'employee_name' => $detail->employee->name,
                    'employee_id' => $detail->employee->employee_id,
                    'start_time' => $detail->start_time,
                    'end_time' => $detail->end_time,
                    'work_priority' => $detail->work_priority,
                    'work_process' => $detail->processType->name ?? '-',
                    'overtime_type' => $detail->overtime_type ?? 'quantitative',
                    'qty_plan' => $detail->qty_plan,
                    'qty_actual' => $detail->qty_actual,
                    'percentage_realization' => $detail->percentage_realization,
                    'can_input_percentage_now' => $canInputNow,
                    'notes' => $detail->notes,
                    'is_rejected' => $detail->is_rejected ?? false,
                    'rejection_reason' => $detail->rejection_reason,
                    'rejected_by_name' => $detail->rejectedBy ? $detail->rejectedBy->name : null,
                    'rejected_at' => $detail->rejected_at ? $detail->rejected_at->format('d/m/Y H:i') : null,
                ];
            }),
        ];

        return response()->json($data);
    }

    /**
     * Method untuk mengambil semua approval dengan tambahan data percentage yang perlu diinput
     */
    private function getApprovalsWithPercentageNeeded($approverLevel)
    {
        $currentEmployee = Employee::where('email', Auth::user()->email)->first();

        if (!$currentEmployee) {
            return collect();
        }

        // Ambil approval biasa (pending/approved/rejected)
        $approvals = OvertimeApproval::with([
            'overtimeRequest.requesterEmployee.jobLevel',
            'overtimeRequest.department',
            'overtimeRequest.approvals.approverEmployee.jobLevel',
            'overtimeRequest.details.employee',
            'approverEmployee.jobLevel'
        ])
            ->where('approver_employee_id', $currentEmployee->id)
            // ->where('approver_level', $currentEmployee->jobLevel)
            ->orderBy('created_at', 'desc')
            ->get();

        // TAMBAHAN: Ambil juga overtime yang sudah approved dan perlu input percentage
        // untuk level yang sama atau di bawah current employee
        $overtimesNeedingPercentage = OvertimeRequest::with([
            'requesterEmployee.jobLevel',
            'department',
            'details.employee',
            'approvals.approverEmployee.jobLevel'
        ])
            ->where('status', 'approved') // Status approved = perlu input data
            ->whereHas('details', function ($query) {
                $query->where('overtime_type', 'qualitative')
                    ->whereNull('percentage_realization');
            })
            ->whereHas('approvals', function ($query) use ($currentEmployee) {
                $query->where('approver_employee_id', $currentEmployee->id)
                    ->where('status', 'approved'); // User ini sudah approve
            })
            ->get();

        // Gabungkan data untuk ditampilkan dalam satu tabel
        $combinedData = $approvals->toBase();

        // Tambahkan data percentage yang perlu diinput sebagai "pseudo approval"
        foreach ($overtimesNeedingPercentage as $overtime) {
            // Cari approval user ini untuk overtime tersebut
            $userApproval = $overtime->approvals->where('approver_employee_id', $currentEmployee->id)->first();

            if ($userApproval) {
                // Buat duplikat approval tapi dengan flag khusus
                $pseudoApproval = $userApproval->replicate();
                $pseudoApproval->needs_percentage_input = true;
                $pseudoApproval->percentage_status = 'needs_input';

                $combinedData->push($pseudoApproval);
            }
        }

        return $combinedData->sortByDesc('created_at');
    }

    public function rejectDetail(Request $request, $detailId)
    {
        $request->validate([
            'reason' => 'required|string|min:10',
        ], [
            'reason.required' => 'Alasan penolakan harus diisi',
            'reason.min' => 'Alasan penolakan minimal 10 karakter'
        ]);

        $detail = OvertimeDetail::findOrFail($detailId);
        $currentEmployee = Employee::where('email', Auth::user()->email)->first();

        if (!$currentEmployee) {
            return response()->json([
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan'
            ], 403);
        }

        // Cek apakah user ini approver dari overtime request ini
        $overtimeRequest = $detail->overtimeRequest;
        $currentApproval = $overtimeRequest->approvals()
            ->where('approver_employee_id', $currentEmployee->id)
            ->where('status', 'pending')
            ->first();

        if (!$currentApproval) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berwenang untuk menolak detail ini'
            ], 403);
        }

        // Cek apakah ini giliran user untuk approve
        $previousPendingExists = OvertimeApproval::where('overtime_request_id', $overtimeRequest->id)
            ->where('step_order', '<', $currentApproval->step_order)
            ->where('status', 'pending')
            ->exists();

        if ($previousPendingExists) {
            return response()->json([
                'success' => false,
                'message' => 'Belum giliran Anda untuk memproses approval ini'
            ], 403);
        }

        // Update detail dengan status rejected
        $detail->update([
            'is_rejected' => true,
            'rejection_reason' => $request->reason,
            'rejected_by' => $currentEmployee->id,
            'rejected_at' => now(),
        ]);

        \Log::info("Detail rejected - Detail ID: {$detail->id}, Employee: {$detail->employee->name}, Rejected by: {$currentEmployee->name}");

        return response()->json([
            'success' => true,
            'message' => 'Detail berhasil ditolak',
            'detail_id' => $detail->id
        ]);
    }

    public function unrejectDetail($detailId)
    {
        $detail = OvertimeDetail::findOrFail($detailId);
        $currentEmployee = Employee::where('email', Auth::user()->email)->first();

        if (!$currentEmployee) {
            return response()->json([
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan'
            ], 403);
        }

        $overtimeRequest = $detail->overtimeRequest;
        $isApprover = $overtimeRequest->approvals()
            ->where('approver_employee_id', $currentEmployee->id)
            ->where('status', 'pending')
            ->exists();

        if (!$isApprover && $detail->rejected_by !== $currentEmployee->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berwenang untuk membatalkan penolakan ini'
            ], 403);
        }

        $detail->update([
            'is_rejected' => false,
            'rejection_reason' => null,
            'rejected_by' => null,
            'rejected_at' => null,
        ]);

        \Log::info("Detail unreject - Detail ID: {$detail->id}, Employee: {$detail->employee->name}");

        return response()->json([
            'success' => true,
            'message' => 'Penolakan berhasil dibatalkan',
            'detail_id' => $detail->id
        ]);
    }
}
