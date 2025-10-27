<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Employee;
use App\Models\FlowJob;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class MasterDataSeeder extends Seeder
{
    public function run()
    {
        // =======================
        // 1. PERMISSIONS (LENGKAP)
        // =======================
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
            ['name' => 'view-employees', 'display_name' => 'View Employees'],
            ['name' => 'create-employees', 'display_name' => 'Create Employees'],
            ['name' => 'edit-employees', 'display_name' => 'Edit Employees'],
            ['name' => 'delete-employees', 'display_name' => 'Delete Employees'],               
            ['name' => 'view-departments', 'display_name' => 'View Departments'],
            ['name' => 'create-departments', 'display_name' => 'Create Departments'],
            ['name' => 'edit-departments', 'display_name' => 'Edit Departments'],
            ['name' => 'delete-departments', 'display_name' => 'Delete Departments'],
            ['name' => 'view-job-levels', 'display_name' => 'View Job Levels'],
            ['name' => 'create-job-levels', 'display_name' => 'Create Job Levels'],
            ['name' => 'edit-job-levels', 'display_name' => 'Edit Job Levels'],
            ['name' => 'delete-job-levels', 'display_name' => 'Delete Job Levels'],
            ['name' => 'view-flow-jobs', 'display_name' => 'View Flow Jobs'],
            ['name' => 'create-flow-jobs', 'display_name' => 'Create Flow Jobs'],
            ['name' => 'edit-flow-jobs', 'display_name' => 'Edit Flow Jobs'],
            ['name' => 'delete-flow-jobs', 'display_name' => 'Delete Flow Jobs'],
            ['name' => 'view-planning', 'display_name' => 'View Planning'],
            ['name' => 'create-planning', 'display_name' => 'Create Planning'],
            ['name' => 'edit-planning', 'display_name' => 'Edit Planning'],
            ['name' => 'delete-planning', 'display_name' => 'Delete Planning'],
            ['name' => 'approve-planning', 'display_name' => 'Approve Planning'],
            ['name' => 'view-process-types', 'display_name' => 'View Process Types'],
            ['name' => 'create-process-types', 'display_name' => 'Create Process Types'],
            ['name' => 'edit-process-types', 'display_name' => 'Edit Process Types'],
            ['name' => 'delete-process-types', 'display_name' => 'Delete Process Types'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // =======================
        // 2. ROLES dengan PERMISSIONS
        // =======================
        
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Full access to all features including cross-department planning',
        ]);
        $adminRole->permissions()->attach(Permission::all());

        $managerRole = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Manager with approval rights for their department',
        ]);
        $managerPermissions = Permission::whereIn('name', [
            'view-overtime',
            'create-overtime',
            'approve-overtime',
            'view-employees',
            'view-planning',
            'create-planning',
            'approve-planning',
        ])->get();
        $managerRole->permissions()->attach($managerPermissions);

        $staffRole = Role::create([
            'name' => 'staff',
            'display_name' => 'Staff',
            'description' => 'Basic employee access for viewing and creating overtime',
        ]);
        $staffPermissions = Permission::whereIn('name', [
            'view-overtime',
            'create-overtime',
            'view-planning',
        ])->get();
        $staffRole->permissions()->attach($staffPermissions);

        // =======================
        // 3. DEPARTMENTS
        // =======================
        $departments = [
            ['name' => 'Produksi', 'code' => 'PROD', 'description' => 'Departemen Produksi'],
            ['name' => 'IT', 'code' => 'IT', 'description' => 'Departemen Information Technology'],
            ['name' => 'Human Resource', 'code' => 'HRD', 'description' => 'Departemen Human Resource Development'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Departemen Finance & Accounting'],
        ];
        foreach ($departments as $dept) {
            Department::create($dept);
        }

        // =======================
        // 4. JOB LEVELS
        // =======================
        $jobLevels = [
            ['name' => 'Administrator',  'code' => 'ADMIN', 'level_order' => 0, 'description' => 'System Administrator'],
            ['name' => 'HRD Manager',    'code' => 'HRD',   'level_order' => 1, 'description' => 'Manager HRD'],
            ['name' => 'Division Head',  'code' => 'DIV',   'level_order' => 2, 'description' => 'Kepala Divisi'],
            ['name' => 'Sub Division Head', 'code' => 'SUBDIV', 'level_order' => 3, 'description' => 'Kepala Sub Divisi'],
            ['name' => 'Department Head','code' => 'DEPT',  'level_order' => 4, 'description' => 'Kepala Departemen'],
            ['name' => 'Sub Department Head','code' => 'SUBDEPT', 'level_order' => 5, 'description' => 'Kepala Sub Departemen'],
            ['name' => 'Section Head',   'code' => 'SECT',  'level_order' => 6, 'description' => 'Kepala Seksi'],
            ['name' => 'Foreman',        'code' => 'FORE',  'level_order' => 7, 'description' => 'Supervisor tingkat paling bawah'],
            ['name' => 'Staff',          'code' => 'STAFF', 'level_order' => 8, 'description' => 'Staff/Operator'],
        ];
        foreach ($jobLevels as $level) {
            JobLevel::create($level);
        }

        // =======================
        // Ambil data yang sudah dibuat
        // =======================
        $prodDept = Department::where('code', 'PROD')->first();
        $itDept   = Department::where('code', 'IT')->first();
        $hrdDept  = Department::where('code', 'HRD')->first();
        $finDept  = Department::where('code', 'FIN')->first();

        $adminLevel  = JobLevel::where('code', 'ADMIN')->first();
        $foreman     = JobLevel::where('code', 'FORE')->first();
        $sectHead    = JobLevel::where('code', 'SECT')->first();
        $subDeptHead = JobLevel::where('code', 'SUBDEPT')->first();
        $deptHead    = JobLevel::where('code', 'DEPT')->first();
        $subdivHead  = JobLevel::where('code', 'SUBDIV')->first();
        $divHead     = JobLevel::where('code', 'DIV')->first();
        $hrdManagerL = JobLevel::where('code', 'HRD')->first();
        $staff       = JobLevel::where('code', 'STAFF')->first();

        // =======================
        // 5. USERS + EMPLOYEES dengan Role yang sesuai
        // =======================
        $userData = [
            [
                'id' => 'ADMIN001', 
                'username' => 'admin',
                'name' => 'System Administrator',
                'email' => 'admin@company.com',
                'dept' => $hrdDept->id, 
                'level' => $adminLevel->id,
                'role' => $adminRole->id
            ],

            [
                'id' => 'PROD001', 
                'username' => 'john.doe',
                'name' => 'John Doe',
                'email' => 'john.doe@company.com',
                'dept' => $prodDept->id, 
                'level' => $foreman->id,
                'role' => $staffRole->id
            ],
            [
                'id' => 'PROD002', 
                'username' => 'jane.smith',
                'name' => 'Jane Smith',
                'email' => 'jane.smith@company.com',
                'dept' => $prodDept->id, 
                'level' => $sectHead->id,
                'role' => $managerRole->id
            ],
            [
                'id' => 'PROD003', 
                'username' => 'mike.johnson',
                'name' => 'Mike Johnson',
                'email' => 'mike.johnson@company.com',
                'dept' => $prodDept->id, 
                'level' => $deptHead->id,
                'role' => $managerRole->id
            ],

            [
                'id' => 'IT001', 
                'username' => 'david.brown',
                'name' => 'David Brown',
                'email' => 'david.brown@company.com',
                'dept' => $itDept->id, 
                'level' => $foreman->id,
                'role' => $staffRole->id
            ],

            [
                'id' => 'DIV999', 
                'username' => 'sarah.wilson',
                'name' => 'Sarah Wilson',
                'email' => 'sarah.wilson@company.com',
                'dept' => $hrdDept->id, 
                'level' => $divHead->id,
                'role' => $managerRole->id
            ],

            [
                'id' => 'HRD002', 
                'username' => 'robert.miller',
                'name' => 'Robert Miller',
                'email' => 'robert.miller@company.com',
                'dept' => $hrdDept->id, 
                'level' => $hrdManagerL->id,
                'role' => $managerRole->id
            ],

            [
                'id' => 'FIN002', 
                'username' => 'emily.taylor',
                'name' => 'Emily Taylor',
                'email' => 'emily.taylor@company.com',
                'dept' => $finDept->id, 
                'level' => $deptHead->id,
                'role' => $managerRole->id
            ],

            [
                'id' => 'PROD005', 
                'username' => 'tom.staff1',
                'name' => 'Tom Staff1',
                'email' => 'tom.staff1@company.com',
                'dept' => $prodDept->id, 
                'level' => $staff->id,
                'role' => $staffRole->id
            ],
            [
                'id' => 'IT003', 
                'username' => 'amy.staff2',
                'name' => 'Amy Staff2',
                'email' => 'amy.staff2@company.com',
                'dept' => $itDept->id, 
                'level' => $staff->id,
                'role' => $staffRole->id
            ],
        ];

        foreach ($userData as $data) {
            User::create([
                'employee_id'   => $data['id'],
                'username'      => $data['username'],
                'name'          => $data['name'],
                'email'         => $data['email'],
                'password'      => Hash::make('password123'),
                'role_id'       => $data['role'],
                'department_id' => $data['dept'],
                'job_level_id'  => $data['level'],
                'is_active'     => true,
                'email_verified_at' => now(),
            ]);

            Employee::create([
                'employee_id'   => $data['id'],
                'name'          => $data['name'],
                'email'         => $data['email'],
                'department_id' => $data['dept'],
                'job_level_id'  => $data['level'],
                'is_active'     => true,
            ]);
        }

        // =======================
        // 6. FLOW JOBS (DENGAN STEP 1 = PENGAJUAN BOTH)
        // =======================
        $prodSectHead = Employee::where('employee_id', 'PROD002')->first();
        $prodDeptHead = Employee::where('employee_id', 'PROD003')->first();
        $divisionHead = Employee::where('employee_id', 'DIV999')->first();
        $hrdManager   = Employee::where('employee_id', 'HRD002')->first();
        $finDeptHead  = Employee::where('employee_id', 'FIN002')->first();

        $flowJobs = [
            // ===== PRODUKSI =====
            // Step 1-4: BOTH (sama untuk Planning & Unplanned)
            ['dept' => $prodDept->id, 'level' => $foreman->id,     'approver' => null,              'order' => 1, 'name' => 'Pengajuan Laksanakan', 'applies' => 'both'],
            ['dept' => $prodDept->id, 'level' => $sectHead->id,    'approver' => $prodSectHead->id, 'order' => 2, 'name' => 'Approval Section Head', 'applies' => 'both'],
            ['dept' => $prodDept->id, 'level' => $subDeptHead->id, 'approver' => null,              'order' => 3, 'name' => 'Approval Sub Department Head', 'applies' => 'both'],
            ['dept' => $prodDept->id, 'level' => $deptHead->id,    'approver' => $prodDeptHead->id, 'order' => 4, 'name' => 'Approval Department Head', 'applies' => 'both'],
            
            // Step 5-7: UNPLANNED ONLY (tambahan untuk Unplanned)
            ['dept' => $prodDept->id, 'level' => $subdivHead->id,  'approver' => null,              'order' => 5, 'name' => 'Approval Sub Division Head', 'applies' => 'unplanned'],
            ['dept' => $prodDept->id, 'level' => $divHead->id,     'approver' => $divisionHead->id, 'order' => 6, 'name' => 'Approval Division Head', 'applies' => 'unplanned'],
            ['dept' => $prodDept->id, 'level' => $hrdManagerL->id, 'approver' => $hrdManager->id,   'order' => 7, 'name' => 'Approval HRD', 'applies' => 'unplanned'],

            // ===== IT =====
            // Step 1-2: BOTH (sama untuk Planning & Unplanned)
            ['dept' => $itDept->id, 'level' => $foreman->id,     'approver' => null,              'order' => 1, 'name' => 'Pengajuan Laksanakan', 'applies' => 'both'],
            ['dept' => $itDept->id, 'level' => $subdivHead->id,  'approver' => null,              'order' => 2, 'name' => 'Approval Sub Division Head', 'applies' => 'both'],
            ['dept' => $itDept->id, 'level' => $divHead->id,     'approver' => $divisionHead->id, 'order' => 3, 'name' => 'Approval Division Head', 'applies' => 'both'],
            
            // Step 4: UNPLANNED ONLY (tambahan untuk Unplanned)
            ['dept' => $itDept->id, 'level' => $hrdManagerL->id, 'approver' => $hrdManager->id,   'order' => 4, 'name' => 'Approval HRD', 'applies' => 'unplanned'],

            // ===== FINANCE =====
            // Step 1-2: BOTH (sama untuk Planning & Unplanned)
            ['dept' => $finDept->id, 'level' => $deptHead->id,  'approver' => $finDeptHead->id,  'order' => 1, 'name' => 'Pengajuan Laksanakan', 'applies' => 'both'],
            ['dept' => $finDept->id, 'level' => $divHead->id,   'approver' => $divisionHead->id, 'order' => 2, 'name' => 'Approval Division Head', 'applies' => 'both'],
            
            // Step 3: UNPLANNED ONLY (tambahan untuk Unplanned)
            ['dept' => $finDept->id, 'level' => $hrdManagerL->id, 'approver' => $hrdManager->id,   'order' => 3, 'name' => 'Approval HRD', 'applies' => 'unplanned'],
            
            // ===== HRD =====
            // Step 1-2: BOTH (sama untuk Planning & Unplanned)
            ['dept' => $hrdDept->id, 'level' => $hrdManagerL->id, 'approver' => $hrdManager->id,   'order' => 1, 'name' => 'Pengajuan Laksanakan', 'applies' => 'both'],
            ['dept' => $hrdDept->id, 'level' => $divHead->id,     'approver' => $divisionHead->id, 'order' => 2, 'name' => 'Approval Division Head', 'applies' => 'both'],
        ];

        foreach ($flowJobs as $flow) {
            FlowJob::create([
                'department_id'        => $flow['dept'],
                'job_level_id'         => $flow['level'],
                'approver_employee_id' => $flow['approver'],
                'step_order'           => $flow['order'],
                'step_name'            => $flow['name'],
                'applies_to'           => $flow['applies'],
                'is_active'            => true,
            ]);
        }

        $this->command->info('✅ Master data seeded successfully!');
        $this->command->info('👤 Admin user created:');
        $this->command->info('   Username: admin | Email: admin@company.com | Password: password123');
        $this->command->info('📋 Flow Jobs Structure:');
        $this->command->info('   Produksi: Step 1-4 (both), Step 5-7 (unplanned)');
        $this->command->info('   IT: Step 1-3 (both), Step 4 (unplanned)');
        $this->command->info('   Finance: Step 1-2 (both), Step 3 (unplanned)');
        $this->command->info('   HRD: Step 1-2 (both)');
        $this->command->info('🔄 Step 1 = Pengajuan/Pelaksanaan (available untuk Planning dan Unplanned)');
    }
}