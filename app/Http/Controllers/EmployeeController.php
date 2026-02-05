<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Plant;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.permission:view-employees')->only(['index', 'show']);
        $this->middleware('check.permission:create-employees')->only(['create', 'store']);
        $this->middleware('check.permission:edit-employees')->only(['edit', 'update']);
        $this->middleware('check.permission:delete-employees')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Employee::with(['department', 'jobLevel', 'plant']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('jobLevel', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $employees = $query->get();
        $departments = Department::where('is_active', true)->get();
        $jobLevels = JobLevel::where('is_active', true)->orderBy('level_order')->get();
        $plants = Plant::get();

        return view('employees.index', compact('employees', 'departments', 'jobLevels', 'plants'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string|max:20|unique:employees,employee_id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'department_id' => 'required|exists:departments,id',
            'job_level_id' => 'required|exists:job_levels,id',
            'plant_id' => 'nullable|exists:plants,id',
            'type' => 'required|in:karyawan,pkl,harian_lepas', // <── NEW
        ]);

        $data = $request->only([
            'employee_id',
            'name',
            'email',
            'department_id',
            'job_level_id',
            'plant_id',
            'type',
            'phone'
        ]);

        $data['is_active'] = $request->has('is_active');

        Employee::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Karyawan berhasil ditambahkan!'
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'employee_id' => 'required|string|max:20|unique:employees,employee_id,' . $employee->id,
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $employee->id,
            'department_id' => 'required|exists:departments,id',
            'plant_id' => 'nullable|exists:plants,id',
            'job_level_id' => 'required|exists:job_levels,id',
            'type' => 'required|in:karyawan,pkl,harian_lepas', // <── NEW
        ]);

        $data = $request->only([
            'employee_id',
            'name',
            'email',
            'department_id',
            'job_level_id',
            'plant_id',
            'type',
            'phone'
        ]);

        $data['is_active'] = $request->has('is_active');

        $employee->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data karyawan berhasil diupdate!'
        ]);
    }

    public function destroy(Employee $employee)
    {
        try {
            $user = \App\Models\User::where('employee_id', $employee->employee_id)->first();
            if ($user) {
                $user->delete();
            }

            $employee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan dan akun pengguna terkait berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus karyawan. Data mungkin sedang digunakan.'
            ], 400);
        }
    }

    public function getByJobLevel($jobLevel)
    {
        $employees = Employee::where('job_level_id', $jobLevel)
            ->select('id', 'name')
            ->get();

        return response()->json($employees);
    }
}
