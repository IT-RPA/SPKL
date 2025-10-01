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
        // 1. PERMISSIONS
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
            ['name' => 'view-flow-jobs', 'display_name' => 'view-flow-jobs'],
            ['name' => 'create-flow-jobs', 'display_name' => 'Create Flow Jobs'],
            ['name' => 'edit-flow-jobs', 'display_name' => 'Edit Flow Jobs'],
            ['name' => 'delete-flow-jobs', 'display_name' => 'Delete Flow Jobs'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // =======================
        // 2. ROLES dengan PERMISSIONS
        // =======================
        
        // Admin Role - Full Access
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Full access to all features',
        ]);
        $adminRole->permissions()->attach(Permission::all());

        // Manager Role - Approval Rights (untuk Section Head & Dept Head)
        $managerRole = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Manager with approval rights',
        ]);
        $managerPermissions = Permission::whereIn('name', [
            'view-overtime',
            'create-overtime',
            'approve-overtime',
            'view-employees',
        ])->get();
        $managerRole->permissions()->attach($managerPermissions);

        // Staff/Employee Role - Basic Access
        $staffRole = Role::create([
            'name' => 'staff',
            'display_name' => 'Staff',
            'description' => 'Basic employee access',
        ]);
        $staffPermissions = Permission::whereIn('name', [
            'view-overtime',
            'create-overtime',
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
            ['name' => 'Foreman',        'code' => 'FORE',  'level_order' => 7, 'description' => 'Supervisor tingkat paling bawah'],
            ['name' => 'Section Head',   'code' => 'SECT',  'level_order' => 6, 'description' => 'Kepala Seksi'],
            ['name' => 'Sub Department Head','code' => 'SUBDEPT', 'level_order' => 5, 'description' => 'Kepala Sub Departemen'],
            ['name' => 'Department Head','code' => 'DEPT',  'level_order' => 4, 'description' => 'Kepala Departemen'],
            ['name' => 'Sub Division Head',  'code' => 'SUBDIV',   'level_order' => 3, 'description' => 'Kepala Sub Divisi'],
            ['name' => 'Division Head',  'code' => 'DIV',   'level_order' => 2, 'description' => 'Kepala Divisi'],
            ['name' => 'HRD Manager',    'code' => 'HRD',   'level_order' => 1, 'description' => 'Manager HRD'],
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
            // Produksi - Staff (Foreman = Staff Role)
            [
                'id' => 'PROD001', 
                'username' => 'john.doe',
                'name' => 'John Doe',
                'email' => 'john.doe@company.com',
                'dept' => $prodDept->id, 
                'level' => $foreman->id,
                'role' => $staffRole->id  // Staff
            ],
            // Produksi - Manager (Section Head = Manager Role)
            [
                'id' => 'PROD002', 
                'username' => 'jane.smith',
                'name' => 'Jane Smith',
                'email' => 'jane.smith@company.com',
                'dept' => $prodDept->id, 
                'level' => $sectHead->id,
                'role' => $managerRole->id  // Manager
            ],
            // Produksi - Manager (Dept Head = Manager Role)
            [
                'id' => 'PROD003', 
                'username' => 'mike.johnson',
                'name' => 'Mike Johnson',
                'email' => 'mike.johnson@company.com',
                'dept' => $prodDept->id, 
                'level' => $deptHead->id,
                'role' => $managerRole->id  // Manager
            ],

            // IT - Staff (Foreman = Staff Role)
            [
                'id' => 'IT001', 
                'username' => 'david.brown',
                'name' => 'David Brown',
                'email' => 'david.brown@company.com',
                'dept' => $itDept->id, 
                'level' => $foreman->id,
                'role' => $staffRole->id  // Staff
            ],

            // Division Head - Admin (berlaku untuk semua departemen)
            [
                'id' => 'DIV999', 
                'username' => 'sarah.wilson',
                'name' => 'Sarah Wilson',
                'email' => 'sarah.wilson@company.com',
                'dept' => $hrdDept->id, 
                'level' => $divHead->id,
                'role' => $adminRole->id  // Admin
            ],

            // HRD Manager - Admin
            [
                'id' => 'HRD002', 
                'username' => 'robert.miller',
                'name' => 'Robert Miller',
                'email' => 'robert.miller@company.com',
                'dept' => $hrdDept->id, 
                'level' => $hrdManagerL->id,
                'role' => $adminRole->id  // Admin
            ],

            // Finance Dept Head - Manager
            [
                'id' => 'FIN002', 
                'username' => 'emily.taylor',
                'name' => 'Emily Taylor',
                'email' => 'emily.taylor@company.com',
                'dept' => $finDept->id, 
                'level' => $deptHead->id,
                'role' => $managerRole->id  // Manager
            ],

            // Staff Produksi - Staff Role
            [
                'id' => 'PROD005', 
                'username' => 'tom.staff1',
                'name' => 'Tom Staff1',
                'email' => 'tom.staff1@company.com',
                'dept' => $prodDept->id, 
                'level' => $staff->id,
                'role' => $staffRole->id  // Staff
            ],
            // Staff IT - Staff Role
            [
                'id' => 'IT003', 
                'username' => 'amy.staff2',
                'name' => 'Amy Staff2',
                'email' => 'amy.staff2@company.com',
                'dept' => $itDept->id, 
                'level' => $staff->id,
                'role' => $staffRole->id  // Staff
            ],
        ];

        foreach ($userData as $data) {
            // Buat User dengan role yang sesuai
            User::create([
                'employee_id'   => $data['id'],
                'username'      => $data['username'],
                'name'          => $data['name'],
                'email'         => $data['email'],
                'password'      => Hash::make('password123'),
                'role_id'       => $data['role'],  // Role sesuai job level
                'department_id' => $data['dept'],
                'job_level_id'  => $data['level'],
                'is_active'     => true,
                'email_verified_at' => now(),
            ]);

            // Buat Employee yang sinkron
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
        // 6. FLOW JOBS
        // =======================
        $prodSectHead = Employee::where('employee_id', 'PROD002')->first();
        $prodDeptHead = Employee::where('employee_id', 'PROD003')->first();
        $divisionHead = Employee::where('employee_id', 'DIV999')->first();
        $hrdManager   = Employee::where('employee_id', 'HRD002')->first();
        $finDeptHead  = Employee::where('employee_id', 'FIN002')->first();

        $flowJobs = [
            // Produksi Flow
            ['dept' => $prodDept->id, 'level' => $foreman->id,     'approver' => null,              'order' => 1, 'name' => 'Pengajuan'],
            ['dept' => $prodDept->id, 'level' => $sectHead->id,    'approver' => $prodSectHead->id, 'order' => 2, 'name' => 'Approval Section Head'],
            ['dept' => $prodDept->id, 'level' => $subDeptHead->id, 'approver' => null,              'order' => 3, 'name' => 'Approval Sub Department Head'],
            ['dept' => $prodDept->id, 'level' => $deptHead->id,    'approver' => $prodDeptHead->id, 'order' => 4, 'name' => 'Approval Department Head'],
            ['dept' => $prodDept->id, 'level' => $subdivHead->id,  'approver' => null,              'order' => 5, 'name' => 'Approval Sub Division Head'],
            ['dept' => $prodDept->id, 'level' => $divHead->id,     'approver' => $divisionHead->id, 'order' => 6, 'name' => 'Approval Division Head'],
            ['dept' => $prodDept->id, 'level' => $hrdManagerL->id, 'approver' => $hrdManager->id,   'order' => 7, 'name' => 'Approval HRD'],

            // IT Flow
            ['dept' => $itDept->id, 'level' => $foreman->id,     'approver' => null,              'order' => 1, 'name' => 'Pengajuan'],
            ['dept' => $itDept->id, 'level' => $subdivHead->id,  'approver' => null,              'order' => 2, 'name' => 'Approval Sub Division Head'],
            ['dept' => $itDept->id, 'level' => $divHead->id,     'approver' => $divisionHead->id, 'order' => 3, 'name' => 'Approval Division Head'],
            ['dept' => $itDept->id, 'level' => $hrdManagerL->id, 'approver' => $hrdManager->id,   'order' => 4, 'name' => 'Approval HRD'],

            // Finance Flow
            ['dept' => $finDept->id, 'level' => $deptHead->id,    'approver' => $finDeptHead->id,  'order' => 1, 'name' => 'Pengajuan'],
            ['dept' => $finDept->id, 'level' => $divHead->id,     'approver' => $divisionHead->id, 'order' => 2, 'name' => 'Approval Division Head'],
            ['dept' => $finDept->id, 'level' => $hrdManagerL->id, 'approver' => $hrdManager->id,   'order' => 3, 'name' => 'Approval HRD'],
            
            // HRD Flow
            ['dept' => $hrdDept->id, 'level' => $hrdManagerL->id, 'approver' => null,              'order' => 1, 'name' => 'Pengajuan'],
            ['dept' => $hrdDept->id, 'level' => $divHead->id,     'approver' => $divisionHead->id, 'order' => 2, 'name' => 'Approval Division Head'],
        ];

        foreach ($flowJobs as $flow) {
            FlowJob::create([
                'department_id'        => $flow['dept'],
                'job_level_id'         => $flow['level'],
                'approver_employee_id' => $flow['approver'],
                'step_order'           => $flow['order'],
                'step_name'            => $flow['name'],
                'is_active'            => true,
            ]);
        }

        $this->command->info('Master data, roles, and permissions seeded successfully!');
    }
}   