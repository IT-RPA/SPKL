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
        // Ambil employees yang belum punya user account
        $employees = Employee::whereNotIn('employee_id', function($query) {
            $query->select('employee_id')->from('users');
        })->where('is_active', true)->get();
        
        // Ambil semua roles untuk dipilih
        $roles = Role::all();
        
        return view('users.create', compact('employees', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,employee_id|unique:users,employee_id',
            'username' => 'required|string|unique:users',
            'password' => 'required|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        // Ambil data employee
        $employee = Employee::where('employee_id', $request->employee_id)->first();

        if (!$employee) {
            return back()->withErrors(['employee_id' => 'Employee tidak ditemukan'])->withInput();
        }

        DB::transaction(function () use ($request, $employee) {
            User::create([
                'employee_id' => $employee->employee_id,
                'username' => $request->username,
                'name' => $employee->name,
                'email' => $employee->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id, // Role dari input manual
                'department_id' => $employee->department_id,
                'job_level_id' => $employee->job_level_id,
                'is_active' => true,
            ]);
        });

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan');
    }

    public function edit(User $user)
    {
        // Ambil employee yang terkait
        $employee = Employee::where('employee_id', $user->employee_id)->first();
        
        // Ambil semua roles untuk dipilih
        $roles = Role::all();
        
        return view('users.edit', compact('user', 'employee', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username,' . $user->id,
            'password' => 'nullable|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        DB::transaction(function () use ($request, $user) {
            $data = [
                'username' => $request->username,
                'role_id' => $request->role_id, // Update role dari input manual
            ];
            
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);
        });

        return redirect()->route('users.index')->with('success', 'User berhasil diupdate');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User berhasil dihapus');
    }

    // API untuk Select2 - Search employees
    public function searchEmployees(Request $request)
    {
        $search = $request->get('q');
        
        $employees = Employee::where('is_active', true)
            ->whereNotIn('employee_id', function($query) {
                $query->select('employee_id')->from('users');
            })
            ->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'employee_id', 'name']);

        return response()->json([
            'results' => $employees->map(function($emp) {
                return [
                    'id' => $emp->employee_id,
                    'text' => "{$emp->employee_id} - {$emp->name}"
                ];
            })
        ]);
    }

    // API untuk Get employee data by employee_id
    public function getEmployeeData($employeeId)
    {
        $employee = Employee::with(['department', 'jobLevel'])
            ->where('employee_id', $employeeId)
            ->first();

        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        return response()->json([
            'name' => $employee->name,
            'email' => $employee->email,
            'department' => $employee->department->name,
            'job_level' => $employee->jobLevel->name . ' (' . $employee->jobLevel->code . ')',
        ]);
    }
}