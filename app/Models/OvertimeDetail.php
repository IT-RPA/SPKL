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

    public function canInputPercentageNow()
    {
        if (!$this->isQualitative()) {
            return false;
        }

        // Cek apakah sudah bisa input berdasarkan approval atau waktu
        $request = $this->overtimeRequest;
        
        // Kondisi 1: Semua approval selesai
        if ($request->status === 'completed') {
            return true;
        }

        // Kondisi 2: Sudah melewati jam selesai
        $endDateTime = Carbon::parse($request->date->format('Y-m-d') . ' ' . $this->end_time);
        return now()->greaterThan($endDateTime);
    }
}