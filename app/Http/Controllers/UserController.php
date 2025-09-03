<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\JobLevel;  // ✅ NEW: Import JobLevel
use App\Models\Employee;  // ✅ NEW: Import Employee untuk sinkronisasi
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;  // ✅ NEW: Untuk transaction

class UserController extends Controller
{
    public function index()
    {
        // ✅ Updated: Include jobLevel relasi
        $users = User::with(['role', 'department', 'jobLevel'])->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $departments = Department::all();
        $jobLevels = JobLevel::orderBy('level_order')->get();  // ✅ NEW: JobLevels
        
        return view('users.create', compact('roles', 'departments', 'jobLevels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|unique:users',
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
            'job_level_id' => 'required|exists:job_levels,id',  // ✅ Updated validation
        ]);

        // ✅ NEW: Gunakan transaction untuk sinkronisasi User dan Employee
        DB::transaction(function () use ($request) {
            // Buat User
            $user = User::create([
                'employee_id' => $request->employee_id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'department_id' => $request->department_id,
                'job_level_id' => $request->job_level_id,  // ✅ Updated field
                'is_active' => true,
            ]);

            // ✅ NEW: Auto-create Employee yang terkait
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
        $jobLevels = JobLevel::orderBy('level_order')->get();  // ✅ NEW: JobLevels
        
        return view('users.edit', compact('user', 'roles', 'departments', 'jobLevels'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'employee_id' => 'required|unique:users,employee_id,' . $user->id,
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
            'job_level_id' => 'required|exists:job_levels,id',  // ✅ Updated validation
        ]);

        // ✅ NEW: Gunakan transaction untuk sinkronisasi
        DB::transaction(function () use ($request, $user) {
            $data = $request->only(['employee_id', 'name', 'email', 'role_id', 'department_id', 'job_level_id']);
            
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            // ✅ NEW: Update Employee yang terkait
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
        // ✅ NEW: Gunakan transaction
        DB::transaction(function () use ($user) {
            // Soft delete employee terkait (set is_active = false)
            Employee::where('employee_id', $user->employee_id)
                ->update(['is_active' => false]);
            
            $user->delete();
        });
        
        return redirect()->route('users.index')->with('success', 'User berhasil dihapus');
    }
}