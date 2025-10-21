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
    // protected $commands = [
    //     Commands\ParcelCron::class,
    // ];
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('Parcel:cron')
        //          ->everyMinute();
        // $schedule->command('inspire')->hourly();
        $schedule->command('stock:update')->everyMinute();

        // Run scheduled jobs every minute
        $schedule->command('jobs:run-scheduled')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Clean up old job statuses daily at 2 AM
        $schedule->call(function () {
            \App\Models\JobStatus::cleanup();
        })->dailyAt('02:00');

        // Legacy: Keep the original scheduled stock update as fallback
        // This will be replaced by the dynamic scheduled jobs system
        $schedule->command('stock:scheduled-update')
                 ->dailyAt('03:00')
                 ->timezone('Asia/Dhaka')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->skip(function () {
                     // Skip if there's an active scheduled job for stock updates
                     return \App\Models\ScheduledJob::where('is_active', true)
                         ->where('job_class', 'App\\Jobs\\ProcessStockUpdate')
                         ->exists();
                 });
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
