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

        // Scheduled stock update for all warehouses
        // Runs daily at 3 AM BDT
        $schedule->command('stock:scheduled-update')
                 ->dailyAt('03:00')
                 ->timezone('Asia/Dhaka')
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
