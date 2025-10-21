<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ScheduledJob;
use Carbon\Carbon;

class ScheduledJobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create default stock update scheduled job
        $stockUpdateJob = ScheduledJob::updateOrCreate(
            ['name' => 'Daily Stock Update'],
            [
                'job_class' => 'App\\Jobs\\ProcessStockUpdate',
                'queue_name' => 'stock-updates',
                'scheduled_time' => '03:00:00',
                'timezone' => 'Asia/Dhaka',
                'is_active' => true,
                'job_parameters' => null, // null means all warehouses
            ]
        );

        // Calculate next run time
        $stockUpdateJob->calculateNextRun();

        $this->command->info('Default scheduled job created: Daily Stock Update at 3:00 AM BDT');
    }
}
