<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function flowJobs()
    {
        return $this->hasMany(FlowJob::class)->orderBy('step_order');
    }

    public function getActiveEmployeesCountAttribute()
    {
        return $this->employees()->where('is_active', true)->count();
    }
}