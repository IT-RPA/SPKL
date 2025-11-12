<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'plant_id',
        'job_level_id',
        'approver_employee_id',
        'step_order',
        'step_name',
        'applies_to',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'step_order' => 'integer'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function jobLevel()
    {
        return $this->belongsTo(JobLevel::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    // ✅ TAMBAH RELASI INI
    public function approverEmployee()
    {
        return $this->belongsTo(Employee::class, 'approver_employee_id');
    }
}
