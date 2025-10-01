<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\User;

class EmployeeObserver
{
    /**
     * Handle the Employee "updated" event.
     * Sinkronisasi data ke tabel users (TANPA ubah role)
     */
    public function updated(Employee $employee)
    {
        // Cari user yang terkait dengan employee ini
        $user = User::where('employee_id', $employee->employee_id)->first();

        if ($user) {
            // Update data user KECUALI role_id (role tetap manual)
            $user->update([
                'name' => $employee->name,
                'email' => $employee->email,
                'department_id' => $employee->department_id,
                'job_level_id' => $employee->job_level_id,
                'is_active' => $employee->is_active,
            ]);

            \Log::info("User {$user->username} updated from Employee {$employee->employee_id} (Role tidak berubah)");
        }
    }

    /**
     * Handle the Employee "deleted" event.
     * Nonaktifkan user jika employee dihapus
     */
    public function deleted(Employee $employee)
    {
        $user = User::where('employee_id', $employee->employee_id)->first();

        if ($user) {
            $user->update(['is_active' => false]);
            \Log::info("User {$user->username} deactivated because Employee {$employee->employee_id} was deleted");
        }
    }
}