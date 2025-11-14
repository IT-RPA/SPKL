<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OvertimePlanning extends Model
{
    use HasFactory;

    protected $fillable = [
        'planning_number',
        'plant_id',
        'department_id',
        'planned_date',
        'max_employees',
        'planned_start_time',
        'planned_end_time',
        'work_description',
        'reason',
        'used_employees',
        'remaining_employees',
        'status',
        'created_by',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'planned_date' => 'date',
        'approved_at' => 'datetime',
        'max_employees' => 'integer',
        'used_employees' => 'integer',
        'remaining_employees' => 'integer'
    ];

    // ===== RELATIONSHIPS =====

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function approvals()
    {
        return $this->hasMany(OvertimePlanningApproval::class, 'planning_id')->orderBy('step_order');
    }

    public function overtimeRequests()
    {
        return $this->hasMany(OvertimeRequest::class, 'planning_id');
    }

    // ===== STATIC METHODS =====

    /**
     * Generate unique planning number
     * Format: PLAN-YYYYMMDD-XXX
     */
    public static function generatePlanningNumber($date)
    {
        $dateStr = Carbon::parse($date)->format('Ymd');
        $prefix = 'PLAN';

        \Log::info("=== GENERATE PLANNING NUMBER DEBUG ===");
        \Log::info("Date: {$dateStr}");

        $lastPlanning = static::where('planning_number', 'like', "{$prefix}-{$dateStr}%")
            ->orderBy('planning_number', 'desc')
            ->first();

        if ($lastPlanning) {
            \Log::info("Last planning found: {$lastPlanning->planning_number}");
            $lastSequence = (int)substr($lastPlanning->planning_number, -3);
            $sequence = $lastSequence + 1;
            \Log::info("Last sequence: {$lastSequence}, New sequence: {$sequence}");
        } else {
            \Log::info("No previous planning found for this date, starting with sequence 1");
            $sequence = 1;
        }

        $planningNumber = $prefix . '-' . $dateStr . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);

        // Safety check untuk race condition
        $attempts = 0;
        $maxAttempts = 100;

        while (static::where('planning_number', $planningNumber)->exists() && $attempts < $maxAttempts) {
            $attempts++;
            $sequence++;
            $planningNumber = $prefix . '-' . $dateStr . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
            \Log::warning("Planning number {$planningNumber} already exists, trying next sequence (attempt {$attempts})");
        }

        if ($attempts >= $maxAttempts) {
            throw new \Exception('Unable to generate unique planning number after ' . $maxAttempts . ' attempts');
        }

        \Log::info("Final planning number: {$planningNumber} (after {$attempts} attempts)");
        \Log::info("=== END GENERATE PLANNING NUMBER DEBUG ===");

        return $planningNumber;
    }

    // ===== STATUS MANAGEMENT =====

    /**
     * Update status berdasarkan approval
     */
    public function updateStatusBasedOnApprovals()
    {
        \Log::info("=== UPDATE PLANNING STATUS DEBUG ===");
        \Log::info("Planning ID: {$this->id}");

        $totalApprovals = $this->approvals()->count();
        $approvedCount = $this->approvals()->where('status', 'approved')->count();
        $rejectedCount = $this->approvals()->where('status', 'rejected')->count();

        \Log::info("Approvals - Total: {$totalApprovals}, Approved: {$approvedCount}, Rejected: {$rejectedCount}");

        // Jika ada yang rejected
        if ($rejectedCount > 0) {
            $this->update(['status' => 'rejected']);
            \Log::info("Status set to REJECTED");
            return;
        }

        // Jika semua sudah approved
        if ($approvedCount == $totalApprovals && $totalApprovals > 0) {
            $this->update([
                'status' => 'approved',
                'approved_at' => now()
            ]);
            \Log::info("Status set to APPROVED");
            return;
        }

        // Jika masih pending
        $this->update(['status' => 'pending']);
        \Log::info("Status set to PENDING");
        \Log::info("=== END UPDATE PLANNING STATUS DEBUG ===");
    }

    // ===== VALIDATION & CHECKS =====

    /**
     * Check apakah planning masih valid untuk digunakan
     */
    public function isValid()
    {
        return $this->status === 'approved' &&
            $this->remaining_employees > 0 &&
            $this->planned_date >= now()->format('Y-m-d');
    }

    /**
     * Check apakah perlu reminder H-7
     */
    public function needsReminder()
    {
        $sevenDaysFromNow = now()->addDays(7)->format('Y-m-d');
        return $this->status === 'approved' &&
            $this->planned_date == $sevenDaysFromNow;
    }

    /**
     * Check apakah sudah expired (H+1)
     */
    public function isExpired()
    {
        $oneDayAfter = Carbon::parse($this->planned_date)->addDay()->format('Y-m-d');
        return now()->format('Y-m-d') >= $oneDayAfter &&
            $this->status === 'approved' &&
            $this->remaining_employees > 0;
    }

    // ===== USAGE TRACKING =====

    /**
     * Increment penggunaan kuota
     */
    public function incrementUsage($count = 1)
    {
        \Log::info("=== INCREMENT USAGE DEBUG ===");
        \Log::info("Planning ID: {$this->id}, Current used: {$this->used_employees}, Increment by: {$count}");

        $this->increment('used_employees', $count);
        $this->decrement('remaining_employees', $count);

        // Fresh reload
        $this->refresh();

        \Log::info("After increment - Used: {$this->used_employees}, Remaining: {$this->remaining_employees}");

        // Jika quota habis, set status completed
        if ($this->remaining_employees <= 0) {
            $this->update(['status' => 'completed']);
            \Log::info("Quota habis, status set to COMPLETED");
        }

        \Log::info("=== END INCREMENT USAGE DEBUG ===");
    }

    /**
     * Decrement penggunaan kuota (jika overtime dibatalkan)
     */
    public function decrementUsage($count = 1)
    {
        \Log::info("=== DECREMENT USAGE DEBUG ===");
        \Log::info("Planning ID: {$this->id}, Current used: {$this->used_employees}, Decrement by: {$count}");

        $this->decrement('used_employees', $count);
        $this->increment('remaining_employees', $count);

        // Fresh reload
        $this->refresh();

        \Log::info("After decrement - Used: {$this->used_employees}, Remaining: {$this->remaining_employees}");

        // Jika sebelumnya completed, kembalikan ke approved
        if ($this->status === 'completed' && $this->remaining_employees > 0) {
            $this->update(['status' => 'approved']);
            \Log::info("Status changed back to APPROVED");
        }

        \Log::info("=== END DECREMENT USAGE DEBUG ===");
    }

    // ===== SCOPE QUERIES =====

    /**
     * Scope untuk planning yang valid (bisa digunakan)
     */
    public function scopeValid($query)
    {
        return $query->where('status', 'approved')
            ->where('remaining_employees', '>', 0)
            ->where('planned_date', '>=', now()->format('Y-m-d'));
    }

    /**
     * Scope untuk planning yang perlu reminder
     */
    public function scopeNeedsReminder($query)
    {
        $sevenDaysFromNow = now()->addDays(7)->format('Y-m-d');
        return $query->where('status', 'approved')
            ->where('planned_date', $sevenDaysFromNow);
    }

    /**
     * Scope untuk planning yang expired
     */
    public function scopeExpired($query)
    {
        $oneDayAgo = now()->subDay()->format('Y-m-d');
        return $query->where('status', 'approved')
            ->where('remaining_employees', '>', 0)
            ->where('planned_date', '<=', $oneDayAgo);
    }
}
