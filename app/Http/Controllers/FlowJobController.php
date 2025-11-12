<?php

namespace App\Http\Controllers;

use App\Models\FlowJob;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobLevel;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FlowJobController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.permission:view-flow-jobs')->only(['index', 'show']);
        $this->middleware('check.permission:create-flow-jobs')->only(['create', 'store']);
        $this->middleware('check.permission:edit-flow-jobs')->only(['edit', 'update']);
        $this->middleware('check.permission:delete-flow-jobs')->only(['destroy']);
    }

    public function index()
    {
        $flowJobs = FlowJob::with(['department', 'jobLevel', 'plant'])
            ->orderBy('department_id')
            ->orderBy('applies_to')
            ->orderBy('step_order')
            ->get();

        $departments = Department::where('is_active', true)->get();
        $jobLevels = JobLevel::where('is_active', true)->orderBy('level_order')->get();
        $employees = Employee::where('is_active', true)->get();
        $plants = Plant::get();

        return view('flow-jobs.index', compact('flowJobs', 'departments', 'jobLevels', 'employees', 'plants'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'plant_id' => $request->plant_id === '#' || $request->plant_id === '' ? null : $request->plant_id,
        ]);
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'plant_id' => 'nullable|exists:plants,id',
            'job_level_id' => 'required|exists:job_levels,id',
            'step_order' => [
                'required',
                'integer',
                'min:0',
                Rule::unique('flow_jobs')
                    ->where(function ($query) use ($request) {
                        return $query
                            ->where('plant_id', $request->plant_id)
                            ->where('applies_to', $request->applies_to);
                    }),
            ],
            'step_name' => 'required|string|max:255',
            'approver_employee_id' => 'nullable|exists:employees,id',
            'applies_to' => 'required|in:planned,unplanned,both',
        ]);

        // Check if step_order already exists for this department and applies_to
        $exists = FlowJob::where('department_id', $request->department_id)
            ->where('plant_id', $request->plant_id)
            ->where('step_order', $request->step_order)
            ->where('applies_to', $request->applies_to)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Urutan step dengan applies_to ini sudah ada untuk departemen ini!'
            ], 400);
        }

        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['approver_employee_id'] = $request->approver_employee_id; // Default null, bisa diisi manual nanti

        FlowJob::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Flow Job berhasil ditambahkan!'
        ]);
    }

    public function update(Request $request, FlowJob $flowJob)
    {
        $request->merge([
            'plant_id' => $request->plant_id === '#' || $request->plant_id === '' ? null : $request->plant_id,
        ]);
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'plant_id' => 'nullable|exists:plants,id',
            'job_level_id' => 'required|exists:job_levels,id',
            'step_order' => 'required|integer|min:0',
            'step_name' => 'required|string|max:255',
            'applies_to' => 'required|in:planned,unplanned,both',
        ]);

        // Check if step_order already exists (except current record)
        $exists = FlowJob::where('department_id', $request->department_id)
            ->where('step_order', $request->step_order)
            ->where('applies_to', $request->applies_to)
            ->where('id', '!=', $flowJob->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Urutan step dengan applies_to ini sudah ada untuk departemen ini!'
            ], 400);
        }

        $data = $request->all();
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
