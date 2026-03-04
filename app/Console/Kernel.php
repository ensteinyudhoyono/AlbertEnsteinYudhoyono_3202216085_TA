<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        
        // Send rent reminders every 5 minutes
        $schedule->command('rent:send-reminders')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Check for expired rents every 10 minutes
        $schedule->command('rent:check-expired')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Check for overdue rents every 5 minutes
        $schedule->command('rent:check-overdue')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Send security notifications at exact end time (every minute)
        $schedule->command('rent:notify-security-end-time')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Calculate automatic penalties for overdue rents every 5 minutes
        $schedule->command('rent:calculate-auto-penalties')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Comprehensive penalty check every 10 minutes (backup)
        $schedule->command('rent:check-and-fix-penalties')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
