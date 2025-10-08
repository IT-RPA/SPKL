<?php
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
        'process_type_id',
        'overtime_type',
        'qty_plan',
        'qty_actual',
        'percentage_realization',
        'can_input_percentage',
        'notes',
        'is_actual_enabled',
        'is_rejected',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'can_input_percentage' => 'boolean',
        'is_actual_enabled' => 'boolean',
        'percentage_realization' => 'decimal:2',
        'is_rejected' => 'boolean',
        'rejected_at' => 'datetime',
    ];

    public function processType()
    {
        return $this->belongsTo(ProcessType::class, 'process_type_id');
    }

    public function overtimeRequest()
    {
        return $this->belongsTo(OvertimeRequest::class, 'overtime_request_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function rejectedBy()
    {
        return $this->belongsTo(Employee::class, 'rejected_by');
    }

    public function getDurationInMinutes()
    {
        $startTime = \Carbon\Carbon::parse($this->start_time);
        $endTime = \Carbon\Carbon::parse($this->end_time);
        return $endTime->diffInMinutes($startTime);
    }

    public function getFormattedDuration()
    {
        $minutes = $this->getDurationInMinutes();
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%d jam %d menit', $hours, $mins);
    }

    public function isQuantitative()
    {
        return $this->overtime_type === 'quantitative';
    }

    public function isQualitative()
    {
        return $this->overtime_type === 'qualitative';
    }

    public function canInputPercentageNow()
{
    try {
        \Log::info("=== DEBUG canInputPercentageNow START ===");
        \Log::info("Detail ID: {$this->id}, Employee: {$this->employee->name}");
        \Log::info("Overtime Type: {$this->overtime_type}");
        
        // ✅ TAMBAHAN: Jika detail di-reject, tidak bisa input percentage
        if ($this->is_rejected) {
            \Log::info("FAILED: Detail is rejected");
            return false;
        }
        
        // Hanya untuk lembur kualitatif
        if ($this->overtime_type !== 'qualitative') {
            \Log::info("FAILED: Not qualitative overtime");
            return false;
        }
        
        $overtime = $this->overtimeRequest;
        \Log::info("Overtime Status: {$overtime->status}");
        
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