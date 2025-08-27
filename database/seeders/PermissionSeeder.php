<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'view-users', 'display_name' => 'View Users'],
            ['name' => 'create-users', 'display_name' => 'Create Users'],
            ['name' => 'edit-users', 'display_name' => 'Edit Users'],
            ['name' => 'delete-users', 'display_name' => 'Delete Users'],
            ['name' => 'view-roles', 'display_name' => 'View Roles'],
            ['name' => 'create-roles', 'display_name' => 'Create Roles'],
            ['name' => 'edit-roles', 'display_name' => 'Edit Roles'],
            ['name' => 'delete-roles', 'display_name' => 'Delete Roles'],
            ['name' => 'view-permissions', 'display_name' => 'View Permissions'],
            ['name' => 'create-permissions', 'display_name' => 'Create Permissions'],
            ['name' => 'edit-permissions', 'display_name' => 'Edit Permissions'],
            ['name' => 'delete-permissions', 'display_name' => 'Delete Permissions'],
            ['name' => 'view-overtime', 'display_name' => 'View Overtime'],
            ['name' => 'create-overtime', 'display_name' => 'Create Overtime'],
            ['name' => 'edit-overtime', 'display_name' => 'Edit Overtime'],
            ['name' => 'delete-overtime', 'display_name' => 'Delete Overtime'],
            ['name' => 'approve-overtime', 'display_name' => 'Approve Overtime'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}

