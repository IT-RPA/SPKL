<?php
// App\Models\OvertimeDetail.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'overtime_type',
        'qty_plan',
        'qty_actual',
        'percentage_realization',
        'can_input_percentage',
        'notes',
        'is_actual_enabled'
    ];

    protected $casts = [
        'can_input_percentage' => 'boolean',
        'is_actual_enabled' => 'boolean',
        'percentage_realization' => 'decimal:2'
    ];

public function overtimeRequest()
{
    return $this->belongsTo(OvertimeRequest::class, 'overtime_request_id');
}

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function isQuantitative()
    {
        return $this->overtime_type === 'quantitative';
    }

    public function isQualitative()
    {
        return $this->overtime_type === 'qualitative';
    }

    // Update method canInputPercentageNow di App\Models\OvertimeDetail

public function canInputPercentageNow()
{
    try {
        \Log::info("=== DEBUG canInputPercentageNow START ===");
        \Log::info("Detail ID: {$this->id}, Employee: {$this->employee->name}");
        \Log::info("Overtime Type: {$this->overtime_type}");
        
        // Hanya untuk lembur kualitatif
        if ($this->overtime_type !== 'qualitative') {
            \Log::info("FAILED: Not qualitative overtime");
            return false;
        }
        
        $overtime = $this->overtimeRequest;
        \Log::info("Overtime Status: {$overtime->status}");
        
        // ✅ PERBAIKAN: Syarat yang disederhanakan
        // Bisa input jika status 'approved' (semua approval selesai) atau 'completed'
        if (in_array($overtime->status, ['approved', 'completed'])) {
            \Log::info("SUCCESS: Status allows percentage input ({$overtime->status})");
            return true;
        }
        
        \Log::info("FAILED: Status not ready for percentage input");
        \Log::info("=== DEBUG canInputPercentageNow END ===");
        return false;
        
    } catch (\Exception $e) {
        \Log::error("canInputPercentageNow Error: " . $e->getMessage());
        return false;
    }
}

}