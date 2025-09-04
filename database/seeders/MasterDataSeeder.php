<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Employee;
use App\Models\FlowJob;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class MasterDataSeeder extends Seeder
{
    public function run()
    {
        // =======================
        // Roles (tambah jika belum ada)
        // =======================
        $roles = [
            ['name' => 'admin', 'display_name' => 'Administrator'],
            ['name' => 'user', 'display_name' => 'User'],
            ['name' => 'approver', 'display_name' => 'Approver'],
        ];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
        
        $userRole = Role::where('name', 'user')->first();

        // =======================
        // Departments
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
        // Job Levels
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
        // Ambil data yang baru dibuat
        // =======================
        $prodDept = Department::where('code', 'PROD')->first();
        $itDept   = Department::where('code', 'IT')->first();
        $hrdDept  = Department::where('code', 'HRD')->first();
        $finDept  = Department::where('code', 'FIN')->first();

        $foreman     = JobLevel::where('code', 'FORE')->first();
        $sectHead    = JobLevel::where('code', 'SECT')->first();
        $subDeptHead = JobLevel::where('code', 'SUBDEPT')->first();
        $deptHead    = JobLevel::where('code', 'DEPT')->first();
        $subdivHead = JobLevel::where('code', 'SUBDIV')->first();
        $divHead     = JobLevel::where('code', 'DIV')->first();
        $hrdManagerL = JobLevel::where('code', 'HRD')->first();
        $staff       = JobLevel::where('code', 'STAFF')->first();

        // =======================
        // ✅ Users + Employees dengan proper relations
        // =======================
        $userData = [
            // Produksi
            ['id' => 'PROD001', 'name' => 'John Doe',     'email' => 'john.doe@company.com',     'dept' => $prodDept->id, 'level' => $foreman->id],
            ['id' => 'PROD002', 'name' => 'Jane Smith',   'email' => 'jane.smith@company.com',   'dept' => $prodDept->id, 'level' => $sectHead->id],
            ['id' => 'PROD003', 'name' => 'Mike Johnson', 'email' => 'mike.johnson@company.com', 'dept' => $prodDept->id, 'level' => $deptHead->id],

            // IT
            ['id' => 'IT001', 'name' => 'David Brown', 'email' => 'david.brown@company.com', 'dept' => $itDept->id, 'level' => $foreman->id],

            // ✅ SATU DIVISION HEAD untuk semua departemen 
            ['id' => 'DIV999', 'name' => 'Sarah Wilson', 'email' => 'sarah.wilson@company.com', 'dept' => $hrdDept->id, 'level' => $divHead->id],

            // HRD
            ['id' => 'HRD002', 'name' => 'Robert Miller', 'email' => 'robert.miller@company.com', 'dept' => $hrdDept->id, 'level' => $hrdManagerL->id],

            // Finance
            ['id' => 'FIN002', 'name' => 'Emily Taylor', 'email' => 'emily.taylor@company.com', 'dept' => $finDept->id, 'level' => $deptHead->id],

            // Staff
            ['id' => 'PROD005', 'name' => 'Tom Staff1', 'email' => 'tom.staff1@company.com', 'dept' => $prodDept->id, 'level' => $staff->id],
            ['id' => 'IT003', 'name' => 'Amy Staff2', 'email' => 'amy.staff2@company.com', 'dept' => $itDept->id, 'level' => $staff->id],
        ];

        foreach ($userData as $data) {
            // ✅ Buat User dengan relasi proper ke JobLevel
            User::create([
                'employee_id'   => $data['id'],
                'name'          => $data['name'],
                'email'         => $data['email'],
                'password'      => Hash::make('password123'),
                'role_id'       => $userRole->id,
                'department_id' => $data['dept'],
                'job_level_id'  => $data['level'],  // ✅ Proper relation
                'is_active'     => true,
                'email_verified_at' => now(),
            ]);

            // ✅ Buat Employee yang sinkron
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
        // Ambil employee buat flow approver
        // =======================
        $prodSectHead = Employee::where('employee_id', 'PROD002')->first(); // Jane
        $prodDeptHead = Employee::where('employee_id', 'PROD003')->first(); // Mike
        $itForeman    = Employee::where('employee_id', 'IT001')->first();   // David
        $divisionHead = Employee::where('employee_id', 'DIV999')->first();  // Sarah - SATU untuk semua dept
        $hrdManager   = Employee::where('employee_id', 'HRD002')->first();  // Robert
        $finDeptHead  = Employee::where('employee_id', 'FIN002')->first();  // Emily

        // =======================
        // Flow Jobs - semua departemen menggunakan Division Head yang sama
        // =======================
        $flowJobs = [
            // Produksi Flow
            ['dept' => $prodDept->id, 'level' => $foreman->id,     'approver' => null,            'order' => 1, 'name' => 'Pengajuan'],
            ['dept' => $prodDept->id, 'level' => $sectHead->id,    'approver' => $prodSectHead->id,'order' => 2, 'name' => 'Approval Section Head'],
            ['dept' => $prodDept->id, 'level' => $subDeptHead->id, 'approver' => null,             'order' => 3, 'name' => 'Approval Sub Department Head'],
            ['dept' => $prodDept->id, 'level' => $deptHead->id,    'approver' => $prodDeptHead->id,'order' => 4, 'name' => 'Approval Department Head'],
            ['dept' => $prodDept->id, 'level' => $subdivHead->id,  'approver' => null,            'order' => 5, 'name' => 'Approval Sub Division Head'],
            ['dept' => $prodDept->id, 'level' => $divHead->id,     'approver' => $divisionHead->id, 'order' => 6, 'name' => 'Approval Division Head'],
            ['dept' => $prodDept->id, 'level' => $hrdManagerL->id, 'approver' => $hrdManager->id,  'order' => 7, 'name' => 'Approval HRD'],

            // IT Flow
            ['dept' => $itDept->id, 'level' => $foreman->id,     'approver' => null,            'order' => 1, 'name' => 'Pengajuan'],
            ['dept' => $itDept->id, 'level' => $subdivHead->id,  'approver' => null,            'order' => 2, 'name' => 'Approval Sub Division Head'],
            ['dept' => $itDept->id, 'level' => $divHead->id,     'approver' => $divisionHead->id,'order' => 3, 'name' => 'Approval Division Head'],
            ['dept' => $itDept->id, 'level' => $hrdManagerL->id, 'approver' => $hrdManager->id,  'order' => 4, 'name' => 'Approval HRD'],

            // Finance Flow
            ['dept' => $finDept->id, 'level' => $deptHead->id,    'approver' => $finDeptHead->id, 'order' => 1, 'name' => 'Pengajuan'],
            ['dept' => $finDept->id, 'level' => $divHead->id,     'approver' => $divisionHead->id,'order' => 2, 'name' => 'Approval Division Head'],
            ['dept' => $finDept->id, 'level' => $hrdManagerL->id, 'approver' => $hrdManager->id,  'order' => 3, 'name' => 'Approval HRD'],
            
            // HRD Flow
            ['dept' => $hrdDept->id, 'level' => $hrdManagerL->id, 'approver' => null,            'order' => 1, 'name' => 'Pengajuan'],
            ['dept' => $hrdDept->id, 'level' => $divHead->id,     'approver' => $divisionHead->id,'order' => 2, 'name' => 'Approval Division Head'],
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
    }
}