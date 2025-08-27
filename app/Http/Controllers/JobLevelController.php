<?php

namespace App\Http\Controllers;

use App\Models\JobLevel;
use Illuminate\Http\Request;

class JobLevelController extends Controller
{
    public function index()
    {
        $jobLevels = JobLevel::with('employees')->orderBy('level_order')->get();
        return view('job-levels.index', compact('jobLevels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:job_levels,code',
            'level_order' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        JobLevel::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Level Jabatan berhasil ditambahkan!'
        ]);
    }

    public function update(Request $request, JobLevel $jobLevel)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:job_levels,code,' . $jobLevel->id,
            'level_order' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $jobLevel->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Level Jabatan berhasil diupdate!'
        ]);
    }

    public function destroy(JobLevel $jobLevel)
    {
        try {
            $jobLevel->delete();
            return response()->json([
                'success' => true,
                'message' => 'Level Jabatan berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus level jabatan karena masih memiliki data terkait!'
            ]);
        }
    }
}