<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule):void
    {
        $schedule->command('reservations:deactivate')
                ->dailyAt('03:00');

        $schedule->command('app:update-score-cache')
            ->dailyAt('00:00');
            
        $schedule->command('app:update-product-watched-classes-cache')
            ->dailyAt('02:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
