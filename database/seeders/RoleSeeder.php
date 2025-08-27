<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // -----------------------------
        // Buat Role Admin
        // -----------------------------
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Full access to all features',
        ]);

        // Attach semua permission ke admin
        $adminRole->permissions()->attach(Permission::all());

        // -----------------------------
        // Buat Role Employee
        // -----------------------------
        $employeeRole = Role::create([
            'name' => 'employee',
            'display_name' => 'Employee',
            'description' => 'Basic employee access',
        ]);

        // Attach beberapa permission terbatas ke employee
        $employeePermissions = Permission::whereIn('name', [
            'view-overtime',
            'create-overtime'
        ])->get();

        $employeeRole->permissions()->attach($employeePermissions);

        // -----------------------------
        // Buat Role Manager
        // -----------------------------
        $managerRole = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Manager with approval rights',
        ]);

        // Attach beberapa permission ke manager
        $managerPermissions = Permission::whereIn('name', [
            'view-overtime',
            'create-overtime',
            'approve-overtime'
        ])->get();

        $managerRole->permissions()->attach($managerPermissions);

        $this->command->info('Roles and permissions successfully seeded into role_permissions!');
    }
}
