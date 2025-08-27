<?php

namespace App\Http\Controllers;

use App\Models\FlowJob;
use App\Models\Department;
use App\Models\JobLevel;
use Illuminate\Http\Request;

class FlowJobController extends Controller
{
    public function index()
    {
        $flowJobs = FlowJob::with(['department', 'jobLevel'])->orderBy('department_id')->orderBy('step_order')->get();
        $departments = Department::where('is_active', true)->get();
        $jobLevels = JobLevel::where('is_active', true)->orderBy('level_order')->get();
        
        return view('flow-jobs.index', compact('flowJobs', 'departments', 'jobLevels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'job_level_id' => 'required|exists:job_levels,id',
            'step_order' => 'required|integer|min:1',
            'step_name' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        // Check if step_order already exists for this department
        $exists = FlowJob::where('department_id', $request->department_id)
                         ->where('step_order', $request->step_order)
                         ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Urutan step sudah ada untuk departemen ini!'
            ]);
        }

        FlowJob::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Flow Job berhasil ditambahkan!'
        ]);
    }

    public function update(Request $request, FlowJob $flowJob)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'job_level_id' => 'required|exists:job_levels,id',
            'step_order' => 'required|integer|min:1',
            'step_name' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        // Check if step_order already exists for this department (except current record)
        $exists = FlowJob::where('department_id', $request->department_id)
                         ->where('step_order', $request->step_order)
                         ->where('id', '!=', $flowJob->id)
                         ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Urutan step sudah ada untuk departemen ini!'
            ]);
        }

        $flowJob->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Flow Job berhasil diupdate!'
        ]);
    }

    public function destroy(FlowJob $flowJob)
    {
        $flowJob->delete();
        return response()->json([
            'success' => true,
            'message' => 'Flow Job berhasil dihapus!'
        ]);
    }
}