<?php
// Updated routes/web.php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\JobLevelController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FlowJobController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::redirect('/', '/login');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // User Management
    Route::resource('users', UserController::class);
    
    // Role Management
    Route::resource('roles', RoleController::class);
    
    // Permission Management
    Route::resource('permissions', PermissionController::class);
    
    // Master Data Management
    Route::resource('departments', DepartmentController::class);
    Route::resource('job-levels', JobLevelController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('flow-jobs', FlowJobController::class);
    
    // Overtime Management
    Route::resource('overtime', OvertimeController::class);
    Route::put('/overtime/{overtime}/update-time', [OvertimeController::class, 'updateTime'])->name('overtime.update-time');
    Route::put('/overtime/{overtime}/update-actual', [OvertimeController::class, 'updateActual'])->name('overtime.update-actual');
    Route::post('/check-overtime-eligibility', [OvertimeController::class, 'checkOvertimeEligibility']);
    
    
    // AJAX route untuk get employees by department
    Route::get('/api/employees-by-department', [OvertimeController::class, 'getEmployeesByDepartment'])->name('api.employees-by-department');
    
    // Approval Routes
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/sect-head', [ApprovalController::class, 'sectHeadIndex'])->name('sect-head');
        Route::get('/dept-head', [ApprovalController::class, 'deptHeadIndex'])->name('dept-head');
        route::get('/sub-div-head', [ApprovalController::class, 'subDivHeadIndex'])->name('sub-div-head');
        Route::get('/div-head', [ApprovalController::class, 'divHeadIndex'])->name('div-head');
        Route::get('/hrd', [ApprovalController::class, 'hrdIndex'])->name('hrd');
        
        Route::put('/approve/{approval}', [ApprovalController::class, 'approve'])->name('approve');
        Route::put('/reject/{approval}', [ApprovalController::class, 'reject'])->name('reject');
    });
    Route::get('/approvals/detail/{approval}', [ApprovalController::class, 'overtimeDetail'])->name('approvals.detail');
});