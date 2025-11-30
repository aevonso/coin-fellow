<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        //очистка увед. каждый день в 3:00
        $schedule->command('notifications:cleanup --days=30')->dailyAt('03:00');
        
        $schedule->command('budgets:check-alerts')->dailyAt('09:00');
        
        $schedule->command('queue:work --stop-when-empty')->everyMinute();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}