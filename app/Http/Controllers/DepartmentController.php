<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount(['employees' => function($query) {
            $query->where('is_active', true);
        }])
        ->with(['flowJobs'])
        ->get()
        ->map(function($department) {
            $department->active_employees_count = $department->employees_count;
            return $department;
        });
        
        return view('departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:departments,code',
            'description' => 'nullable|string'
        ]);

        $data = $request->all();
        // Handle checkbox - jika tidak dicentang, set ke false (0)
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        Department::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Departemen berhasil ditambahkan!'
        ]);
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:departments,code,' . $department->id,
            'description' => 'nullable|string'
        ]);

        $data = $request->all();
        // Handle checkbox - jika tidak dicentang, set ke false (0)
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $department->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data departemen berhasil diupdate!'
        ]);
    }

    public function destroy(Department $department)
    {
        try {
            // Cek apakah departemen masih memiliki karyawan aktif
            $activeEmployees = $department->employees()->where('is_active', true)->count();
            if ($activeEmployees > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus departemen karena masih memiliki karyawan aktif!'
                ], 400);
            }

            // Cek apakah departemen masih memiliki flow jobs
            $flowJobsCount = $department->flowJobs()->count();
            if ($flowJobsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus departemen karena masih memiliki flow job!'
                ], 400);
            }

            $department->delete();
            return response()->json([
                'success' => true,
                'message' => 'Departemen berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus departemen karena masih memiliki data terkait!'
            ], 400);
        }
    }
}