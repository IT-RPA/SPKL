<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     */
    protected $commands = [
        Commands\ExpirePlanningCommand::class,
        Commands\SendPlanningReminderCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // 🔄 Auto-expire planning setiap hari jam 00:01
        $schedule->command('planning:expire')
                 ->dailyAt('00:01')
                 ->withoutOverlapping()
                 ->onSuccess(function () {
                     \Log::info('Planning auto-expire completed');
                 })
                 ->onFailure(function () {
                     \Log::error('Planning auto-expire failed');
                 });

        // 📢 Kirim reminder H-7 setiap hari jam 08:00
        $schedule->command('planning:reminder')
                 ->dailyAt('08:00')
                 ->withoutOverlapping()
                 ->onSuccess(function () {
                     \Log::info('Planning reminder completed');
                 })
                 ->onFailure(function () {
                     \Log::error('Planning reminder failed');
                 });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}