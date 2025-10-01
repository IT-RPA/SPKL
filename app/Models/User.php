<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'employee_id', 'username','name', 'email', 'password', 'role_id', 
        'department_id', 'job_level_id', 'is_active'  
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'email', 'email'); 
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // ✅ NEW: Relasi ke JobLevel
    public function jobLevel()
    {
        return $this->belongsTo(JobLevel::class);
    }

    public function overtimeRequests()
    {
        return $this->hasMany(OvertimeRequest::class, 'requester_id');
    }

    public function overtimeDetails()
    {
        return $this->hasMany(OvertimeDetail::class, 'employee_id');
    }

    public function approvals()
    {
        return $this->hasMany(OvertimeApproval::class, 'approver_id');
    }

    // ✅ NEW: Helper method untuk cek level berdasarkan JobLevel
    public function isLevel($levelCode)
    {
        return $this->jobLevel && $this->jobLevel->code === $levelCode;
    }

    // ✅ NEW: Helper method untuk mendapatkan Employee yang terkait
    public function getEmployeeAttribute()
    {
        return Employee::where('email', $this->email)
            ->orWhere('employee_id', $this->employee_id)
            ->first();
    }

    public function hasPermission($permission)
    {
        return $this->role && $this->role->permissions->contains('name', $permission);
    }
}