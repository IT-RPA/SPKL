<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\JobLevel;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['department', 'jobLevel'])->get();
        $departments = Department::where('is_active', true)->get();
        $jobLevels = JobLevel::where('is_active', true)->orderBy('level_order')->get();
        
        return view('employees.index', compact('employees', 'departments', 'jobLevels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string|max:20|unique:employees,employee_id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'department_id' => 'required|exists:departments,id',
            'job_level_id' => 'required|exists:job_levels,id'
        ]);

        $data = $request->all();
        // Handle checkbox - jika tidak dicentang, set ke false (0)
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

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
            'job_level_id' => 'required|exists:job_levels,id'
        ]);

        $data = $request->all();
        // Handle checkbox - jika tidak dicentang, set ke false (0)
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $employee->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data karyawan berhasil diupdate!'
        ]);
    }

    public function destroy(Employee $employee)
    {
        try {
            $employee->delete();
            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus karyawan. Data mungkin sedang digunakan.'
            ], 400);
        }
    }
}