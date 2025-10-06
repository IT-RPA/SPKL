<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OvertimePlanning;
use Carbon\Carbon;

class ExpirePlanningCommand extends Command
{
    protected $signature = 'planning:expire';
    protected $description = 'Auto-expire planning yang sudah lewat H+1';

    public function handle()
    {
        $this->info('🔄 Checking for expired planning...');

        // ✅ FIX: Gunakan 'planned_date' bukan 'date'
        $expiredCount = OvertimePlanning::where('planned_date', '<', Carbon::now()->startOfDay())
            ->where('status', 'approved')
            ->update(['status' => 'expired']);

        if ($expiredCount > 0) {
            $this->info("✅ {$expiredCount} planning telah di-expire");
        } else {
            $this->info("ℹ️  Tidak ada planning yang perlu di-expire");
        }

        return 0;
    }
}