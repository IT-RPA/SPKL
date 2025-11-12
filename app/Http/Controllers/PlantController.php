<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.permission:view-process-types')->only(['index']);
        $this->middleware('check.permission:create-process-types')->only(['store']);
        $this->middleware('check.permission:edit-process-types')->only(['update']);
        $this->middleware('check.permission:delete-process-types')->only(['destroy']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plants = Plant::orderBy('created_at')->get();
        return view('plants.index', compact('plants'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();

        Plant::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Plant berhasil ditambahkan!'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Plant $plant)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();

        $plant->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Plant berhasil diupdate!'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plant $plant)
    {
        try {
            $plant->delete();
            return response()->json([
                'success' => true,
                'message' => 'Plant berhasil dihapus!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Plant. Data mungkin sedang digunakan.'
            ], 400);
        }
    }
}
