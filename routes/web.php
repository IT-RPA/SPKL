<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\PlanningOvertimeController; 
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\JobLevelController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProcessTypeController;
use App\Http\Controllers\FlowJobController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Authentication
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::redirect('/', '/login');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data/daily', [DashboardController::class, 'getDailyChartData'])->name('dashboard.chart.daily');
    Route::get('/dashboard/chart-data/monthly', [DashboardController::class, 'getMonthlyChartData'])->name('dashboard.chart.monthly');
    
    // User Management
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
    
    // Master Data
    Route::resource('departments', DepartmentController::class);
    Route::resource('job-levels', JobLevelController::class);
    Route::resource('employees', EmployeeController::class);
    Route::resource('flow-jobs', FlowJobController::class);
    
    // ✅ PLANNING LEMBUR (BARU)
    Route::prefix('planning')->name('planning.')->group(function () {
        Route::get('/', [PlanningOvertimeController::class, 'index'])->name('index');
        Route::get('/create', [PlanningOvertimeController::class, 'create'])->name('create');
        Route::post('/', [PlanningOvertimeController::class, 'store'])->name('store');
        Route::get('/{planning}', [PlanningOvertimeController::class, 'show'])->name('show');
        
        // Approval Planning
        Route::put('/approval/{approval}/approve', [PlanningOvertimeController::class, 'approve'])->name('approve');
        Route::put('/approval/{approval}/reject', [PlanningOvertimeController::class, 'reject'])->name('reject');
    });
    
    // Overtime Management
    Route::resource('overtime', OvertimeController::class);
    Route::put('/overtime/{overtime}/update-time', [OvertimeController::class, 'updateTime'])->name('overtime.update-time');
    Route::put('/overtime/{overtime}/update-actual', [OvertimeController::class, 'updateActual'])->name('overtime.update-actual');
    Route::put('/overtime/{overtime}/update-percentage', [OvertimeController::class, 'updatePercentage'])->name('overtime.update-percentage');
    Route::post('/check-overtime-eligibility', [OvertimeController::class, 'checkOvertimeEligibility']);
    
    // ✅ API ROUTES (BARU)
    Route::get('/api/employees-by-department', [OvertimeController::class, 'getEmployeesByDepartment'])->name('api.employees-by-department');
    Route::get('/api/check-available-planning', [OvertimeController::class, 'checkAvailablePlanning'])->name('api.check-planning');
    
    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/overtime-leaderboard', [ReportController::class, 'index'])->name('overtime-leaderboard');
        Route::get('/employee-details/{employee}', [ReportController::class, 'getEmployeeDetails'])->name('employee-details');
        Route::get('/export-excel', [ReportController::class, 'exportExcel'])->name('export-excel');
    });
    
    // Approvals
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/sect-head', [ApprovalController::class, 'sectHeadIndex'])->name('sect-head');
        Route::get('/sub-dept-head', [ApprovalController::class, 'subDeptHeadIndex'])->name('sub-dept-head');
        Route::get('/dept-head', [ApprovalController::class, 'deptHeadIndex'])->name('dept-head');
        Route::get('/sub-div-head', [ApprovalController::class, 'subDivHeadIndex'])->name('sub-div-head');
        Route::get('/div-head', [ApprovalController::class, 'divHeadIndex'])->name('div-head');
        Route::get('/hrd', [ApprovalController::class, 'hrdIndex'])->name('hrd');
        
        Route::put('/approve/{approval}', [ApprovalController::class, 'approve'])->name('approve');
        Route::put('/reject/{approval}', [ApprovalController::class, 'reject'])->name('reject');
        Route::post('/detail/{detailId}/reject', [ApprovalController::class, 'rejectDetail'])->name('detail.reject');
        Route::post('/detail/{detailId}/unreject', [ApprovalController::class, 'unrejectDetail'])->name('detail.unreject');
    });
    // Process Types
    Route::resource('process-types', ProcessTypeController::class)->middleware('auth');

    Route::get('/approvals/detail/{approval}', [ApprovalController::class, 'overtimeDetail'])->name('approvals.detail');
    Route::get('/api/employees/search', [UserController::class, 'searchEmployees'])->name('api.employees.search');
    Route::get('/api/employees/{employee}', [UserController::class, 'getEmployeeData'])->name('api.employees.get');
});