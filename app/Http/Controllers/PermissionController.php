<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct()
    {
        // Terapkan middleware permission untuk setiap action
        $this->middleware('check.permission:view-permissions')->only(['index', 'show']);
        $this->middleware('check.permission:create-permissions')->only(['create', 'store']);
        $this->middleware('check.permission:edit-permissions')->only(['edit', 'update']);
        $this->middleware('check.permission:delete-permissions')->only(['destroy']);
    }

    public function index()
    {
        $permissions = Permission::all();
        return view('permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions',
            'display_name' => 'required',
            'description' => 'nullable',
        ]);

        Permission::create($request->all());

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil ditambahkan');
    }

    public function edit(Permission $permission)
    {
        $permissions = Permission::all();
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id,
            'display_name' => 'required',
            'description' => 'nullable',
        ]);

        $permission->update($request->all());

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil diupdate');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('permissions.index')->with('success', 'Permission berhasil dihapus');
    }
}