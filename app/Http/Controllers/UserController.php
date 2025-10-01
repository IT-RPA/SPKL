<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function __construct()
    {
        // Terapkan middleware permission untuk setiap action
        $this->middleware('check.permission:view-users')->only(['index', 'show']);
        $this->middleware('check.permission:create-users')->only(['create', 'store']);
        $this->middleware('check.permission:edit-users')->only(['edit', 'update']);
        $this->middleware('check.permission:delete-users')->only(['destroy']);
    }

    public function index()
    {
        $users = User::with(['role', 'department', 'jobLevel'])->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $departments = Department::all();
        $jobLevels = JobLevel::orderBy('level_order')->get();
        
        return view('users.create', compact('roles', 'departments', 'jobLevels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|unique:users',
            'username' => 'required|string|unique:users',
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
            'job_level_id' => 'required|exists:job_levels,id',
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'employee_id' => $request->employee_id,
                'username' => $request->username,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'department_id' => $request->department_id,
                'job_level_id' => $request->job_level_id,
                'is_active' => true,
            ]);

            Employee::updateOrCreate(
                ['employee_id' => $request->employee_id],
                [
                    'name' => $request->name,
                    'email' => $request->email,
                    'department_id' => $request->department_id,
                    'job_level_id' => $request->job_level_id,
                    'is_active' => true,
                ]
            );
        });

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $departments = Department::all();
        $jobLevels = JobLevel::orderBy('level_order')->get();
        
        return view('users.edit', compact('user', 'roles', 'departments', 'jobLevels'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'employee_id' => 'required|unique:users,employee_id,' . $user->id,
            'username' => 'required|string|unique:users,username,' . $user->id,
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
            'job_level_id' => 'required|exists:job_levels,id',
        ]);

        DB::transaction(function () use ($request, $user) {
            $data = $request->only(['employee_id', 'username', 'name', 'email', 'role_id', 'department_id', 'job_level_id']);
            
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            Employee::updateOrCreate(
                ['employee_id' => $request->employee_id],
                [
                    'name' => $request->name,
                    'email' => $request->email,
                    'department_id' => $request->department_id,
                    'job_level_id' => $request->job_level_id,
                    'is_active' => true,
                ]
            );
        });

        return redirect()->route('users.index')->with('success', 'User berhasil diupdate');
    }

    public function destroy(User $user)
    {
        DB::transaction(function () use ($user) {
            Employee::where('employee_id', $user->employee_id)
                ->update(['is_active' => false]);
            
            $user->delete();
        });
        
        return redirect()->route('users.index')->with('success', 'User berhasil dihapus');
    }
}