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
        'planning_id',
        'overtime_category',
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

    public function planning()
    {
        return $this->belongsTo(OvertimePlanning::class, 'planning_id');
    }

    // Tambahkan juga scope untuk filter by category
    public function scopePlanned($query)
    {
        return $query->where('overtime_category', 'planned');
    }

    public function scopeUnplanned($query)
    {
        return $query->where('overtime_category', 'unplanned');
    }

    public function canInputPercentage($currentUserId)
    {
        try {
            \Log::info("=== DEBUG canInputPercentage START ===");
            \Log::info("Current User ID: {$currentUserId}");
            \Log::info("Overtime Request ID: {$this->id}");
            \Log::info("Overtime Status: {$this->status}");

            $currentUser = User::find($currentUserId);
            if (!$currentUser) {
                \Log::error("User not found with ID: {$currentUserId}");
                return false;
            }

            $currentEmployee = Employee::with('jobLevel')
                ->where('email', $currentUser->email)
                ->first();
            if (!$currentEmployee) {
                \Log::error("Employee not found for email: {$currentUser->email}");
                return false;
            }
            \Log::info("Current Employee: {$currentEmployee->name} (Level: {$currentEmployee->jobLevel->name})");

            $requesterEmployee = $this->requesterEmployee()->with('jobLevel')->first();
            if (!$requesterEmployee) {
                \Log::error("Requester employee not found");
                return false;
            }
            \Log::info("Requester Employee: {$requesterEmployee->name} (Level: {$requesterEmployee->jobLevel->name})");

            $currentJobOrder   = $currentEmployee->jobLevel->level_order ?? 0;
            $requesterJobOrder = $requesterEmployee->jobLevel->level_order ?? 0;
            \Log::info("Job Order - Current: {$currentJobOrder}, Requester: {$requesterJobOrder}");

            // Bisa input jika status 'approved' atau 'completed'
            $statusOk = in_array($this->status, ['approved', 'completed']);
            \Log::info("Status OK for input: " . ($statusOk ? 'YES' : 'NO'));

            if (!$statusOk) {
                \Log::info("FAILED: Status not ready for percentage input");
                return false;
            }

            // Cek apakah user ini approver di request ini
            $isApprover = $this->approvals()
                ->where('approver_employee_id', $currentEmployee->id)
                ->whereIn('status', ['approved', 'pending'])
                ->exists();

            \Log::info("Is Approver: " . ($isApprover ? 'YES' : 'NO'));

            if (!$isApprover) {
                \Log::info("FAILED: User is not an approver for this request");
                return false;
            }
            // ✅ TAMBAHAN: Cek ada detail kualitatif yang siap diisi DAN tidak di-reject
            $readyDetails = $this->details
                ->where('is_rejected', false) // ✅ TAMBAHAN
                ->filter(function ($d) {
                    return $d->isQualitative() && $d->canInputPercentageNow();
                });

            \Log::info("Ready qualitative details count (not rejected): " . $readyDetails->count());

            if ($readyDetails->isEmpty()) {
                \Log::info("FAILED: No qualitative details ready for input (or all rejected)");
                return false;
            }
            // User boleh input kalau lebih tinggi dari requester atau dia memang approver
            $canInput = ($currentJobOrder > $requesterJobOrder) || $isApprover;

            \Log::info("Final Result: " . ($canInput ? 'CAN INPUT' : 'CANNOT INPUT'));
            \Log::info("=== DEBUG canInputPercentage END ===");

            return $canInput;
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
        $date = now()->format('Ymd'); // Format: 20250917
        $prefix = 'SPK';

        \Log::info("=== GENERATE REQUEST NUMBER DEBUG ===");
        \Log::info("Date: {$date}");

        // ✅ PERBAIKAN: Cari nomor terakhir berdasarkan pattern request_number, bukan created_at
        $lastRequest = static::where('request_number', 'like', $prefix . $date . '%')
            ->orderBy('request_number', 'desc')
            ->first();

        if ($lastRequest) {
            \Log::info("Last request found: {$lastRequest->request_number}");

            // Ambil 3 digit terakhir dan increment
            $lastSequence = (int)substr($lastRequest->request_number, -3);
            $sequence = $lastSequence + 1;

            \Log::info("Last sequence: {$lastSequence}, New sequence: {$sequence}");
        } else {
            \Log::info("No previous request found for today, starting with sequence 1");
            $sequence = 1;
        }

        // Format sequence dengan leading zero
        $formattedSequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        $requestNumber = $prefix . $date . $formattedSequence;

        \Log::info("Generated request number: {$requestNumber}");

        // ✅ SAFETY CHECK: Pastikan nomor belum ada (untuk menghindari race condition)
        $attempts = 0;
        $maxAttempts = 100;

        while (static::where('request_number', $requestNumber)->exists() && $attempts < $maxAttempts) {
            $attempts++;
            $sequence++;
            $formattedSequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);
            $requestNumber = $prefix . $date . $formattedSequence;

            \Log::warning("Request number {$requestNumber} already exists, trying next sequence (attempt {$attempts})");
        }

        if ($attempts >= $maxAttempts) {
            throw new \Exception('Unable to generate unique request number after ' . $maxAttempts . ' attempts');
        }

        \Log::info("Final request number: {$requestNumber} (after {$attempts} attempts)");
        \Log::info("=== END GENERATE REQUEST NUMBER DEBUG ===");

        return $requestNumber;
    }

    public function updateStatusAndColor()
    {
        \Log::info("=== UPDATE STATUS AND COLOR DEBUG START ===");
        \Log::info("Overtime ID: {$this->id}");

        $totalApprovals = $this->approvals()->count();
        $approvedCount = $this->approvals()->where('status', 'approved')->count();
        $rejectedCount = $this->approvals()->where('status', 'rejected')->count();
        $cancelledCount = $this->approvals()->where('status', 'cancelled')->count();

        \Log::info("Approvals - Total: {$totalApprovals}, Approved: {$approvedCount}, Rejected: {$rejectedCount}, Cancelled: {$cancelledCount}");

        // Jika ada yang rejected
        if ($rejectedCount > 0) {
            $this->update([
                'status' => 'rejected',
                'status_color' => 'danger'
            ]);
            \Log::info("Status set to REJECTED");

            if ($this->overtime_category == "planned") {
                $totalEmployee = $this->details->count();
                $planned = OvertimePlanning::find($this->planning_id);
                $planned->update([
                    'used_employees' => $planned->used_employees - $totalEmployee,
                    'remaining_employees' => $planned->remaining_employees + $totalEmployee
                ]);
            }
            return;
        }

        // Hitung approval aktif (tidak termasuk yang cancelled)
        $activeApprovals = $totalApprovals - $cancelledCount;

        // Cek apakah semua approval sudah selesai
        $allApprovalsCompleted = ($approvedCount == $activeApprovals && $activeApprovals > 0);
        \Log::info("All approvals completed: " . ($allApprovalsCompleted ? 'YES' : 'NO'));

        if ($allApprovalsCompleted) {
            // ✅ PERBAIKAN: Cek apakah semua data actual/percentage sudah lengkap
            $isDataComplete = $this->isAllDataComplete();
            \Log::info("Is data complete: " . ($isDataComplete ? 'YES' : 'NO'));

            if ($isDataComplete) {
                // Status COMPLETED jika approval selesai DAN data lengkap
                $this->update([
                    'status' => 'completed',
                    'status_color' => 'success'
                ]);
                \Log::info("Status set to COMPLETED");
            } else {
                // ✅ Status APPROVED jika approval selesai tapi data belum lengkap
                $this->update([
                    'status' => 'approved',
                    'status_color' => 'success'
                ]);
                \Log::info("Status set to APPROVED (ready for data input)");

                // Update permission untuk input percentage pada lembur kualitatif
                $this->updatePercentagePermissions();
            }
            return;
        }

        // Jika masih ada yang pending
        $pendingApprovals = $this->approvals()->where('status', 'pending')->get();
        if ($pendingApprovals->count() > 0) {
            $nextApproval = $pendingApprovals->sortBy('step_order')->first();

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
                'status_color' => 'warning'
            ]);
            \Log::info("Status set to {$status} (waiting for {$stepName})");
        } else {
            $this->update([
                'status' => 'pending',
                'status_color' => 'warning'
            ]);
            \Log::info("Status set to PENDING");
        }

        \Log::info("=== UPDATE STATUS AND COLOR DEBUG END ===");
    }
    public function isAllDataComplete()
    {
        // Cek detail quantitative - apakah qty_actual sudah diisi semua
        $quantitativeIncomplete = $this->details()
            ->where('overtime_type', 'quantitative')
            ->where('is_rejected', false)
            ->whereNotNull('qty_plan') // Yang ada qty_plan
            ->whereNull('qty_actual')   // Tapi qty_actual masih kosong
            ->exists();

        if ($quantitativeIncomplete) {
            \Log::info("Quantitative data incomplete for overtime ID: {$this->id}");
            return false;
        }

        // Cek detail qualitative - apakah percentage_realization sudah diisi semua
        $qualitativeIncomplete = $this->details()
            ->where('overtime_type', 'qualitative')
            ->where('is_rejected', false)
            ->whereNull('percentage_realization') // percentage_realization masih kosong
            ->exists();

        if ($qualitativeIncomplete) {
            \Log::info("Qualitative data incomplete for overtime ID: {$this->id}");
            return false;
        }

        \Log::info("All data complete for overtime ID: {$this->id}");
        return true;
    }
    public function checkAndUpdateStatusAfterDataInput()
    {
        // Cek apakah semua approval sudah selesai
        $totalApprovals = $this->approvals()->count();
        $approvedCount = $this->approvals()->where('status', 'approved')->count();
        $cancelledCount = $this->approvals()->where('status', 'cancelled')->count();
        $activeApprovals = $totalApprovals - $cancelledCount;

        $allApprovalsCompleted = ($approvedCount == $activeApprovals && $activeApprovals > 0);

        if ($allApprovalsCompleted && $this->isAllDataComplete()) {
            $this->update([
                'status' => 'completed',
                'status_color' => 'green'
            ]);

            \Log::info("Status changed to COMPLETED for overtime ID: {$this->id}");
        }
    }
    public function canInputActual()
    {
        // ✅ Sesuaikan dengan status yang benar
        return in_array($this->status, ['approved', 'completed']);
    }
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

    public function hasQualitativeNeedingPercentage()
    {
        return $this->details()
            ->where('overtime_type', 'qualitative')
            ->whereNull('percentage_realization')
            ->exists();
    }

    /**
     * Get count qualitative details yang perlu input percentage
     */
    public function getQualitativeNeedingPercentageCount()
    {
        return $this->details()
            ->where('overtime_type', 'qualitative')
            ->whereNull('percentage_realization')
            ->count();
    }

    /**
     * Get count qualitative details yang sudah terisi percentage
     */
    public function getQualitativeFilledPercentageCount()
    {
        return $this->details()
            ->where('overtime_type', 'qualitative')
            ->whereNotNull('percentage_realization')
            ->count();
    }

    /**
     * Check apakah user bisa input percentage untuk overtime ini
     * berdasarkan level user dan status approval
     */
    public function canUserInputPercentage($userId)
    {
        // User harus sudah approve overtime ini
        $userApproval = $this->approvals()
            ->whereHas('approverEmployee', function ($query) use ($userId) {
                $query->whereHas('users', function ($q) use ($userId) {
                    $q->where('id', $userId);
                });
            })
            ->where('status', 'approved')
            ->first();

        if (!$userApproval) {
            return false;
        }

        // Overtime harus sudah status approved (semua approval selesai)
        if ($this->status !== 'approved') {
            return false;
        }

        // Ada detail qualitative yang perlu diinput
        return $this->hasQualitativeNeedingPercentage();
    }
}
