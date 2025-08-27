<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'overtime_request_id',
        'employee_id',
        'start_time',
        'end_time',
        'work_priority',
        'work_process',
        'qty_plan',
        'qty_actual',
        'notes'
    ];

    public function overtimeRequest()
    {
        return $this->belongsTo(OvertimeRequest::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}