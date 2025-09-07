<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class OvertimeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'requester_id',
        'requester_employee_id',
        'requester_level',
        'date',
        'department_id',
        'status',
        'status_color'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function requesterEmployee()
    {
        return $this->belongsTo(Employee::class, 'requester_employee_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function details()
    {
        return $this->hasMany(OvertimeDetail::class);
    }

    public function approvals()
    {
        return $this->hasMany(OvertimeApproval::class)->orderBy('step_order');
    }

    public function canInputPercentage($currentUserId)
{
    try {
        $currentUser = User::find($currentUserId);
        if (!$currentUser) return false;

        $currentEmployee = Employee::with('jobLevel')
            ->where('email', $currentUser->email)
            ->first();
        if (!$currentEmployee) return false;

        $requesterEmployee = $this->requesterEmployee()->with('jobLevel')->first();
        if (!$requesterEmployee) return false;

        $currentJobOrder   = $currentEmployee->jobLevel->level_order ?? 0;
        $requesterJobOrder = $requesterEmployee->jobLevel->level_order ?? 0;

        // --- Syarat waktu ---
        $latestEndTime = $this->details()->max('end_time');
        $passedEndTime = false;
        if ($latestEndTime) {
            $endDateTime = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $latestEndTime);
            $passedEndTime = now()->greaterThan($endDateTime);
        }

        // --- Syarat status ---
        $allApprovalsDone = $this->status === 'completed';
        $statusOk = $allApprovalsDone || $this->status === 'approved' || $passedEndTime;

        if (!$statusOk) {
            return false;
        }

        // --- Cek apakah user ini approver di request ini ---
        $isApprover = $this->approvals()
            ->where('approver_employee_id', $currentEmployee->id)
            ->whereIn('status', ['approved', 'pending'])
            ->exists();

        if (!$isApprover) {
            return false;
        }

        // --- Cek ada detail kualitatif yang siap diisi ---
        $readyDetails = $this->details->filter(function($d) {
            return $d->isQualitative() && $d->canInputPercentageNow();
        });

        if ($readyDetails->isEmpty()) {
            return false;
        }

        // --- User boleh input kalau lebih tinggi dari requester atau dia memang approver ---
        return ($currentJobOrder > $requesterJobOrder) || $isApprover;
    } catch (\Exception $e) {
        \Log::error("canInputPercentage Error: " . $e->getMessage());
        return false;
    }
}

public function updatePercentagePermissions()
{
    // Update can_input_percentage untuk detail yang kualitatif
    $this->details()->where('overtime_type', 'qualitative')->update([
        'can_input_percentage' => true
    ]);
}

    public static function generateRequestNumber()
    {
        $date = now()->format('Ymd');
        $lastRequest = static::whereDate('created_at', now())->latest()->first();
        $sequence = $lastRequest ? (int)substr($lastRequest->request_number, -3) + 1 : 1;
        
        return 'SPK' . $date . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    public function updateStatusAndColor()
    {
        $totalApprovals = $this->approvals()->count();
        $approvedCount = $this->approvals()->where('status', 'approved')->count();
        $rejectedCount = $this->approvals()->where('status', 'rejected')->count();
        $cancelledCount = $this->approvals()->where('status', 'cancelled')->count();

        // Jika ada yang rejected
        if ($rejectedCount > 0) {
            $this->update([
                'status' => 'rejected',
                'status_color' => 'red'
            ]);
            return;
        }

        // Jika semua sudah approved (tidak menghitung yang cancelled)
        $activeApprovals = $totalApprovals - $cancelledCount;
        if ($approvedCount == $activeApprovals && $activeApprovals > 0) {
            $this->update([
                'status' => 'completed', // ✅ Gunakan status yang ada di enum
                'status_color' => 'green'
            ]);
            $this->updatePercentagePermissions();
            return;
        }

        // Jika masih ada yang pending
        $pendingApprovals = $this->approvals()->where('status', 'pending')->get();
        if ($pendingApprovals->count() > 0) {
            $nextApproval = $pendingApprovals->sortBy('step_order')->first();
            
            // ✅ PERBAIKAN: Gunakan status yang sesuai dengan enum di migration
            $statusMap = [
                'Approval Section Head' => 'approved_sect',
                'Approval Sub Department Head' => 'approved_subdept',
                'Approval Department Head' => 'approved_dept',
                'Approval Sub Division Head' => 'approved_subdiv',
                'Approval Division Head' => 'approved_div',
                'Approval HRD' => 'approved_hrd',
            ];
            
            $stepName = $nextApproval->step_name;
            $status = $statusMap[$stepName] ?? 'pending';
            
            $this->update([
                'status' => $status,
                'status_color' => 'yellow'
            ]);
        } else {
            $this->update([
                'status' => 'pending',
                'status_color' => 'yellow'
            ]);
        }
    }

    public function canInputActual()
    {
        // ✅ Sesuaikan dengan status yang benar
        return $this->status === 'completed';
    }
// Perbaiki method canEditTime di App\Models\OvertimeRequest

public function canEditTime($currentUserId)
{
    try {
        $currentUser = User::find($currentUserId);
        if (!$currentUser) {
            \Log::warning("canEditTime: User not found - ID: {$currentUserId}");
            return false;
        }
        
        $currentEmployee = Employee::with('jobLevel')->where('email', $currentUser->email)->first();
        if (!$currentEmployee) {
            \Log::warning("canEditTime: Employee not found for email: {$currentUser->email}");
            return false;
        }
        
        $requesterEmployee = $this->requesterEmployee()->with('jobLevel')->first();
        if (!$requesterEmployee) {
            \Log::warning("canEditTime: Requester employee not found for request ID: {$this->id}");
            return false;
        }
        
        // Pastikan jobLevel ter-load
        if (!$currentEmployee->jobLevel || !$requesterEmployee->jobLevel) {
            \Log::warning("canEditTime: Job level not found - Current: " . ($currentEmployee->jobLevel ? 'OK' : 'NULL') . 
                         ", Requester: " . ($requesterEmployee->jobLevel ? 'OK' : 'NULL'));
            return false;
        }
        
        /*$currentJobOrder = $currentEmployee->jobLevel->step_order ?? 0;
        $requesterJobOrder = $requesterEmployee->jobLevel->step_order ?? 0;*/
        $currentJobOrder   = $currentEmployee->jobLevel->level_order ?? 0;
        $requesterJobOrder = $requesterEmployee->jobLevel->level_order ?? 0;
        
        \Log::info("canEditTime Debug - Current Employee: {$currentEmployee->name} (Order: {$currentJobOrder}), " .
                  "Requester: {$requesterEmployee->name} (Order: {$requesterJobOrder})");
        
        // ✅ PERBAIKAN: Tambahkan kondisi khusus untuk approver yang sedang memproses
        $isCurrentApprover = $this->approvals()
            ->where('approver_employee_id', $currentEmployee->id)
            ->where('status', 'pending')
            ->exists();
            
        // ✅ User dengan job level lebih tinggi ATAU approver yang sedang memproses bisa edit jam
        $canEdit = ($currentJobOrder > $requesterJobOrder) || $isCurrentApprover;
        
        \Log::info("canEditTime Result: " . ($canEdit ? 'TRUE' : 'FALSE') . 
                  " (Higher level: " . ($currentJobOrder > $requesterJobOrder ? 'YES' : 'NO') . 
                  ", Current approver: " . ($isCurrentApprover ? 'YES' : 'NO') . ")");
        
        return $canEdit;
        
    } catch (\Exception $e) {
        \Log::error("canEditTime Error: " . $e->getMessage());
        return false;
    }
}
}