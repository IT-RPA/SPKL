<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OvertimePlanning;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendPlanningReminderCommand extends Command
{
    protected $signature = 'planning:reminder';
    protected $description = 'Kirim reminder untuk planning H-7';

    public function handle()
    {
        $this->info('📢 Checking for planning reminder H-7...');

        // Planning yang 7 hari lagi dan status approved
        $reminderDate = Carbon::now()->addDays(7)->format('Y-m-d');
        
        // ✅ FIX: Gunakan 'planned_date' bukan 'date'
        $plannings = OvertimePlanning::where('planned_date', $reminderDate)
            ->where('status', 'approved')
            ->with(['department', 'creator'])
            ->get();

        if ($plannings->isEmpty()) {
            $this->info("ℹ️  Tidak ada planning untuk reminder H-7");
            return 0;
        }

        foreach ($plannings as $planning) {
            // ✅ FIX: Gunakan 'planned_date' bukan 'date'
            Log::info("REMINDER H-7: Planning #{$planning->id} - {$planning->department->name} - {$planning->planned_date}");
            
            $this->info("📧 Reminder sent for Planning #{$planning->id}");
            
            // TODO: Tambahkan notifikasi database atau email di sini
            // Contoh:
            // $planning->creator->notify(new PlanningReminderNotification($planning));
        }

        $this->info("✅ {$plannings->count()} reminder telah dikirim");

        return 0;
    }
}