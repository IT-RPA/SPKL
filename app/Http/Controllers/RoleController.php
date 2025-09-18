<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct()
    {
        // Terapkan middleware permission untuk setiap action
        $this->middleware('check.permission:view-roles')->only(['index', 'show']);
        $this->middleware('check.permission:create-roles')->only(['create', 'store']);
        $this->middleware('check.permission:edit-roles')->only(['edit', 'update']);
        $this->middleware('check.permission:delete-roles')->only(['destroy']);
    }

    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles',
            'display_name' => 'required',
            'description' => 'nullable',
            'permissions' => 'array',
        ]);

        $role = Role::create($request->only('name', 'display_name', 'description'));
        
        if ($request->permissions) {
            $role->permissions()->attach($request->permissions);
        }

        return redirect()->route('roles.index')->with('success', 'Role berhasil ditambahkan');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'display_name' => 'required',
            'description' => 'nullable',
            'permissions' => 'array',
        ]);

        $role->update($request->only('name', 'display_name', 'description'));
        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->route('roles.index')->with('success', 'Role berhasil diupdate');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus');
    }
}