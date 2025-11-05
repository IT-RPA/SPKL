<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Observers\EmployeeObserver;
use Illuminate\Support\Facades\View;
use App\Http\View\Composers\SidebarComposer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        View::composer(
            'layouts.app', // ganti sesuai path sidebar Anda
            SidebarComposer::class
        );
        // Register Employee Observer untuk auto-sync dengan Users
        Employee::observe(EmployeeObserver::class);

        // Blade directive untuk permission checking
        Blade::if('permission', function ($permission) {
            $user = Auth::user();
            return $user && $user->hasPermission($permission);
        });
    }
}
