<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterActivity extends Model
{
    use HasFactory;

    protected $table = 'master_activities';

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'duration_minutes',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'duration_minutes' => 'integer',
    ];

    public function getCalculatedDurationMinutesAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return $this->duration_minutes ?? 0;
        }

        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return $start->diffInMinutes($end);
    }
}
