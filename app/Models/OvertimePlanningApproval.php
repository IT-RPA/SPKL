<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimePlanningApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'planning_id',
        'approver_employee_id',
        'approver_level',
        'step_order',
        'step_name',
        'status',
        'notes',
        'approved_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'step_order' => 'integer'
    ];

    // ===== RELATIONSHIPS =====
    
    public function planning()
    {
        return $this->belongsTo(OvertimePlanning::class, 'planning_id');
    }

    public function approverEmployee()
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id');
    }
}
