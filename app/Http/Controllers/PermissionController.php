<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
    $permissions = Permission::all(); // ganti paginate() -> all()
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