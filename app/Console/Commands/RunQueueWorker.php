<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunQueueWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-queue-worker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run queue worker if not already running';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Use a cache lock to ensure only one worker runs at a time
        // The lock name is 'queue_worker_lock', and it expires after 600 seconds (10 minutes)
        // Adjust the expiration time based on your longest expected job duration
        $lock = Cache::lock('queue_worker_lock', 600);

        if ($lock->get()) {
            try {
                Log::info('Starting background queue worker...');
                
                // Run the queue worker and stop when the queue is empty
                Artisan::call('queue:work', [
                    '--stop-when-empty' => true,
                    '--tries' => 3,
                    '--timeout' => 90
                ]);
                
                Log::info('Background queue worker finished.');
            } catch (\Exception $e) {
                Log::error('Queue worker failed: ' . $e->getMessage());
            } finally {
                // Always release the lock when done
                $lock->release();
            }
        } else {
            Log::info('Queue worker is already running. Skipping execution.');
        }

        return Command::SUCCESS;
    }
}
