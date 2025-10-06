<?php

namespace App\Http\Controllers;

use App\Models\ProcessType;
use Illuminate\Http\Request;

class ProcessTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.permission:view-process-types')->only(['index']);
        $this->middleware('check.permission:create-process-types')->only(['store']);
        $this->middleware('check.permission:edit-process-types')->only(['update']);
        $this->middleware('check.permission:delete-process-types')->only(['destroy']);
    }

    public function index()
    {
        $processTypes = ProcessType::withCount('overtimeDetails')->orderBy('code')->get();
        return view('process-types.index', compact('processTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:process_types,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        ProcessType::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tipe Proses berhasil ditambahkan!'
        ]);
    }

    public function update(Request $request, ProcessType $processType)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:process_types,code,' . $processType->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $processType->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Tipe Proses berhasil diupdate!'
        ]);
    }

    public function destroy(ProcessType $processType)
    {
        try {
            $processType->delete();
            return response()->json([
                'success' => true,
                'message' => 'Tipe Proses berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Tipe Proses. Data mungkin sedang digunakan.'
            ], 400);
        }
    }
}