<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class UsereSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $employeeRole = Role::where('name', 'employee')->first();
        $managerRole = Role::where('name', 'manager')->first();

        $hrdDept = Department::where('code', 'HRD')->first();
        $itDept = Department::where('code', 'IT')->first();
        $prodDept = Department::where('code', 'PROD')->first();

        // Create Admin User
        User::create([
            'employee_id' => 'ADM001',
            'name' => 'Super Admin',
            'email' => 'admin@company.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'department_id' => $itDept->id,
            'level' => 'hrd',
            'phone' => null,
            'is_active' => true,
        ]);

        // Create HRD Users
        User::create([
            'employee_id' => 'HRD001',
            'name' => 'HRD Manager',
            'email' => 'hrd@company.com',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'department_id' => $hrdDept->id,
            'level' => 'hrd',
            'phone' => null,
            'is_active' => true,
        ]);

        // Create Division Head
        User::create([
            'employee_id' => 'DIV001',
            'name' => 'Division Head Production',
            'email' => 'divhead@company.com',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'department_id' => $prodDept->id,
            'level' => 'div_head',
            'phone' => null,
            'is_active' => true,
        ]);

        // Create Department Head
        User::create([
            'employee_id' => 'DPT001',
            'name' => 'Dept Head Production',
            'email' => 'depthead@company.com',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'department_id' => $prodDept->id,
            'level' => 'dept_head',
            'phone' => null,
            'is_active' => true,
        ]);

        // Create Section Head
        User::create([
            'employee_id' => 'SEC001',
            'name' => 'Section Head Production',
            'email' => 'secthead@company.com',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'department_id' => $prodDept->id,
            'level' => 'sect_head',
            'phone' => null,
            'is_active' => true,
        ]);

        // Create Foreman
        User::create([
            'employee_id' => 'FOR001',
            'name' => 'Foreman Production',
            'email' => 'foreman@company.com',
            'password' => Hash::make('password'),
            'role_id' => $employeeRole->id,
            'department_id' => $prodDept->id,
            'level' => 'foreman',
            'phone' => null,
            'is_active' => true,
        ]);

        // Create regular employees
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'employee_id' => 'EMP' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'name' => 'Employee ' . $i,
                'email' => 'employee' . $i . '@company.com',
                'password' => Hash::make('password'),
                'role_id' => $employeeRole->id,
                'department_id' => $prodDept->id,
                'level' => 'foreman',
                'phone' => null,
                'is_active' => true,
            ]);
        }
    }
}
