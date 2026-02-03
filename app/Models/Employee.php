<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'department_id',
        'job_level_id',
        'plant_id',
        'is_active',
        'phone',
    ];

    // Existing relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function jobLevel()
    {
        return $this->belongsTo(JobLevel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    // ✅ NEW: Add relationship to overtime details
    public function overtimeDetails()
    {
        return $this->hasMany(OvertimeDetail::class, 'employee_id');
    }

    // ✅ NEW: Add relationship to overtime requests as requester
    public function overtimeRequests()
    {
        return $this->hasMany(OvertimeRequest::class, 'requester_employee_id');
    }

    // ✅ NEW: Helper method to get total overtime hours for a period
    public function getTotalOvertimeHours($month = null, $year = null)
    {
        $query = $this->overtimeDetails()
            ->whereHas('overtimeRequest', function ($q) use ($month, $year) {
                $q->where('status', 'approved');
                if ($month) {
                    $q->whereMonth('date', $month);
                }
                if ($year) {
                    $q->whereYear('date', $year);
                }
            });

        $details = $query->get();
        $totalMinutes = 0;

        foreach ($details as $detail) {
            $startTime = \Carbon\Carbon::parse($detail->start_time);
            $endTime = \Carbon\Carbon::parse($detail->end_time);
            $totalMinutes += $endTime->diffInMinutes($startTime);
        }

        return round($totalMinutes / 60, 2);
    }
}
