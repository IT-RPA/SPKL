<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['employees', 'flowJobs'])->get();
        return view('departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:departments,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        Department::create($request->all());

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
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $department->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Departemen berhasil diupdate!'
        ]);
    }

    public function destroy(Department $department)
    {
        try {
            $department->delete();
            return response()->json([
                'success' => true,
                'message' => 'Departemen berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus departemen karena masih memiliki data terkait!'
            ]);
        }
    }
}
