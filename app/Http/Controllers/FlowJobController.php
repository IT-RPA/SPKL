<?php

namespace App\Http\Controllers;

use App\Models\FlowJob;
use App\Models\Department;
use App\Models\JobLevel;
use Illuminate\Http\Request;

class FlowJobController extends Controller
{
    public function __construct()
    {
        // ✅ PERBAIKAN: Gunakan middleware yang sudah terdaftar di Kernel
        $this->middleware('check.permission:view-flow-jobs')->only(['index', 'show']);
        $this->middleware('check.permission:create-flow-jobs')->only(['create', 'store']);
        $this->middleware('check.permission:edit-flow-jobs')->only(['edit', 'update']);
        $this->middleware('check.permission:delete-flow-jobs')->only(['destroy']);
    }

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
            'step_name' => 'required|string|max:255'
        ]);

        // Check if step_order already exists for this department
        $exists = FlowJob::where('department_id', $request->department_id)
                         ->where('step_order', $request->step_order)
                         ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Urutan step sudah ada untuk departemen ini!'
            ], 400);
        }

        $data = $request->all();
        // Handle checkbox - jika tidak dicentang, set ke false (0)
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        FlowJob::create($data);

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
            'step_name' => 'required|string|max:255'
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
            ], 400);
        }

        $data = $request->all();
        // Handle checkbox - jika tidak dicentang, set ke false (0)
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $flowJob->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data Flow Job berhasil diupdate!'
        ]);
    }

    public function destroy(FlowJob $flowJob)
    {
        try {
            $flowJob->delete();
            return response()->json([
                'success' => true,
                'message' => 'Flow Job berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Flow Job. Data mungkin sedang digunakan.'
            ], 400);
        }
    }
}