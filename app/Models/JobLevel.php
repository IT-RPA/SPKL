<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'level_order',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'level_order' => 'integer'
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function flowJobs()
    {
        return $this->hasMany(FlowJob::class);
    }
}
