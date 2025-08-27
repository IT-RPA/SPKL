<?php
// database/seeders/MasterDataSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Employee;
use App\Models\FlowJob;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MasterDataSeeder extends Seeder
{
    public function run()
    {
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
            ['name' => 'Foreman',        'code' => 'FORE',  'level_order' => 5, 'description' => 'Supervisor tingkat paling bawah'],
            ['name' => 'Section Head',   'code' => 'SECT',  'level_order' => 4, 'description' => 'Kepala Seksi'],
            ['name' => 'Department Head','code' => 'DEPT',  'level_order' => 3, 'description' => 'Kepala Departemen'],
            ['name' => 'Division Head',  'code' => 'DIV',   'level_order' => 2, 'description' => 'Kepala Divisi'],
            ['name' => 'HRD Manager',    'code' => 'HRD',   'level_order' => 1, 'description' => 'Manager HRD'],
            ['name' => 'Staff',          'code' => 'STAFF', 'level_order' => 6, 'description' => 'Staff/Operator'],
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
        $deptHead    = JobLevel::where('code', 'DEPT')->first();
        $divHead     = JobLevel::where('code', 'DIV')->first();
        $hrdManagerL = JobLevel::where('code', 'HRD')->first();
        $staff       = JobLevel::where('code', 'STAFF')->first();

        // =======================
        // Users + Employees
        // =======================
        $userData = [
            // Produksi
            ['id' => 'PROD001', 'name' => 'John Doe',     'email' => 'john.doe@company.com',   'dept' => $prodDept->id, 'level' => $foreman->id],
            ['id' => 'PROD002', 'name' => 'Jane Smith',   'email' => 'jane.smith@company.com', 'dept' => $prodDept->id, 'level' => $sectHead->id],
            ['id' => 'PROD003', 'name' => 'Mike Johnson', 'email' => 'mike.johnson@company.com','dept' => $prodDept->id, 'level' => $deptHead->id],
            ['id' => 'PROD004', 'name' => 'Sarah Wilson', 'email' => 'sarah.wilson@company.com','dept' => $prodDept->id, 'level' => $divHead->id],

            // IT
            ['id' => 'IT001', 'name' => 'David Brown', 'email' => 'david.brown@company.com', 'dept' => $itDept->id, 'level' => $foreman->id],
            ['id' => 'IT002', 'name' => 'Lisa Davis',  'email' => 'lisa.davis@company.com',  'dept' => $itDept->id, 'level' => $divHead->id],

            // HRD
            ['id' => 'HRD002','name' => 'Robert Miller','email' => 'robert.miller@company.com','dept' => $hrdDept->id, 'level' => $hrdManagerL->id],

            // Finance
            ['id' => 'FIN002','name' => 'Emily Taylor','email' => 'emily.taylor@company.com','dept' => $finDept->id, 'level' => $deptHead->id],
            ['id' => 'FIN003','name' => 'James Anderson','email' => 'james.anderson@company.com','dept' => $finDept->id, 'level' => $divHead->id],

            // Staff
            ['id' => 'PROD005','name' => 'Tom Staff1','email' => 'tom.staff1@company.com','dept' => $prodDept->id, 'level' => $staff->id],
            ['id' => 'IT003','name' => 'Amy Staff2','email' => 'amy.staff2@company.com','dept' => $itDept->id, 'level' => $staff->id],
        ];

        foreach ($userData as $data) {
            User::create([
                'employee_id' => $data['id'],
                'name'        => $data['name'],
                'email'       => $data['email'],
                'password'    => Hash::make('password123'),
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
        // Ambil employee buat flow approver
        // =======================
        $prodSectHead = Employee::where('employee_id', 'PROD002')->first(); // Jane
        $prodDeptHead = Employee::where('employee_id', 'PROD003')->first(); // Mike
        $prodDivHead  = Employee::where('employee_id', 'PROD004')->first(); // Sarah
        $hrdManager   = Employee::where('employee_id', 'HRD002')->first(); // Robert
        $itDivHead    = Employee::where('employee_id', 'IT002')->first();  // Lisa
        $finDeptHead  = Employee::where('employee_id', 'FIN002')->first(); // Emily
        $finDivHead   = Employee::where('employee_id', 'FIN003')->first(); // James

        // =======================
        // Flow Jobs
        // =======================
        $flowJobs = [
            // Produksi
            ['dept' => $prodDept->id, 'level' => $foreman->id, 'approver' => null,            'order' => 1, 'name' => 'Pengajuan'],
            ['dept' => $prodDept->id, 'level' => $sectHead->id, 'approver' => $prodSectHead->id,'order' => 2, 'name' => 'Approval Section Head'],
            ['dept' => $prodDept->id, 'level' => $deptHead->id, 'approver' => $prodDeptHead->id,'order' => 3, 'name' => 'Approval Department Head'],
            ['dept' => $prodDept->id, 'level' => $divHead->id,  'approver' => $prodDivHead->id, 'order' => 4, 'name' => 'Approval Division Head'],
            ['dept' => $prodDept->id, 'level' => $hrdManagerL->id,'approver' => $hrdManager->id,'order' => 5, 'name' => 'Approval HRD'],

            // IT
            ['dept' => $itDept->id, 'level' => $foreman->id, 'approver' => null, 'order' => 1, 'name' => 'Pengajuan'],
            ['dept' => $itDept->id, 'level' => $divHead->id, 'approver' => $itDivHead->id, 'order' => 2, 'name' => 'Approval Division Head'],
            ['dept' => $itDept->id, 'level' => $hrdManagerL->id, 'approver' => $hrdManager->id, 'order' => 3, 'name' => 'Approval HRD'],

            // Finance
            ['dept' => $finDept->id, 'level' => $deptHead->id, 'approver' => $finDeptHead->id,'order' => 1, 'name' => 'Pengajuan'],
            ['dept' => $finDept->id, 'level' => $divHead->id,  'approver' => $finDivHead->id, 'order' => 2, 'name' => 'Approval Division Head'],
            ['dept' => $finDept->id, 'level' => $hrdManagerL->id,'approver' => $hrdManager->id,'order' => 3, 'name' => 'Approval HRD'],
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

        $this->command->info('✅ Master data seeded successfully!');
    }
}
