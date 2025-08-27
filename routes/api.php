<?php

use App\Models\OvertimeRequest;
use Illuminate\Http\Request;

Route::middleware('auth:sanctum')->get('/overtime-detail/{approvalId}', function ($approvalId) {
    $approval = \App\Models\OvertimeApproval::with([
        'overtimeRequest.requester',
        'overtimeRequest.department',
        'overtimeRequest.details.employee'
    ])->findOrFail($approvalId);

    $request = $approval->overtimeRequest;
    
    return response()->json([
        'request_number' => $request->request_number,
        'requester_name' => $request->requester->name,
        'requester_level' => ucfirst(str_replace('_', ' ', $request->requester_level)),
        'date' => $request->date->format('d/m/Y'),
        'department_name' => $request->department->name,
        'details' => $request->details->map(function ($detail) {
            return [
                'employee_name' => $detail->employee->name,
                'start_time' => $detail->start_time,
                'end_time' => $detail->end_time,
                'work_priority' => $detail->work_priority,
                'work_process' => $detail->work_process,
                'qty_plan' => $detail->qty_plan,
                'qty_actual' => $detail->qty_actual,
                'notes' => $detail->notes,
            ];
        })
    ]);
});