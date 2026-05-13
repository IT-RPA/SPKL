<?php

namespace App\Http\Controllers;

use App\Models\MasterActivity;
use Illuminate\Http\Request;

class MasterActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $activities = MasterActivity::orderBy('name')->get();
        return view('master-activities.index', compact('activities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'is_active' => 'boolean'
        ]);

        $validated['duration_minutes'] = $this->calculateDurationMinutes($validated['start_time'], $validated['end_time']);
        $validated['is_active'] = $request->has('is_active');

        MasterActivity::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Aktivitas berhasil ditambahkan'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'is_active' => 'boolean'
        ]);

        $validated['duration_minutes'] = $this->calculateDurationMinutes($validated['start_time'], $validated['end_time']);
        $validated['is_active'] = $request->has('is_active');

        $activity = MasterActivity::findOrFail($id);
        $activity->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Aktivitas berhasil diperbarui'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $activity = MasterActivity::findOrFail($id);
        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Aktivitas berhasil dihapus'
        ]);
    }

    private function calculateDurationMinutes(string $startTime, string $endTime): int
    {
        $start = \Carbon\Carbon::createFromFormat('H:i', $startTime);
        $end = \Carbon\Carbon::createFromFormat('H:i', $endTime);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return $start->diffInMinutes($end);
    }
}
