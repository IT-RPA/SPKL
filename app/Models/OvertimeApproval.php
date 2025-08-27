<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'overtime_request_id',
        'approver_employee_id',
        'approver_level',
        'step_order',
        'step_name',
        'status',
        'approved_at',
        'notes'
    ];

    protected $casts = [
        'approved_at' => 'datetime'
    ];

    public function overtimeRequest()
    {
        return $this->belongsTo(OvertimeRequest::class);
    }

    public function approverEmployee()
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id');
    }
}