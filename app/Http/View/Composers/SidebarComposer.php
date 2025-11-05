<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\OvertimeApproval;
use Auth;

class SidebarComposer
{
    public function compose(View $view)
    {
        $currentEmployee = Employee::with('jobLevel')
            ->where('email', Auth::user()->email ?? null)
            ->first();

        $pendingApprovals = collect();
        $pendingPercentageCount = 0;

        if ($currentEmployee && $currentEmployee->jobLevel) {
            $pendingApprovals = OvertimeApproval::where('approver_employee_id', $currentEmployee->id)
                ->where('status', 'pending')
                ->with('overtimeRequest')
                ->get();

            $pendingPercentageCount = OvertimeRequest::whereHas('details', function ($q) {
                $q->where('overtime_type', 'qualitative')
                    ->whereNull('percentage_realization');
            })
                ->whereHas('approvals', function ($q) use ($currentEmployee) {
                    $q->where('approver_employee_id', $currentEmployee->id)
                        ->whereIn('status', ['approved', 'pending']);
                })
                ->where('status', 'approved')
                ->count();
        }



        $view->with(compact(
            'pendingApprovals',
            'pendingPercentageCount',
            'currentEmployee'
        ));
    }
}
