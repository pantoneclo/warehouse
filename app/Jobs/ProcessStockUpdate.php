<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class ProcessStockUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 7200; // 2 hours timeout for large datasets
    public $tries = 2; // Reduced tries since this is a full process
    public $maxExceptions = 1;

    protected $warehouseId;

    /**
     * Create a new job instance.
     *
     * @param int|null $warehouseId Optional warehouse ID, null for all warehouses
     */
    public function __construct($warehouseId = null)
    {
        $this->warehouseId = $warehouseId;
    }

    /**
     * Execute the job.
     * Runs the same logic as the scheduled stock update command
     */
    public function handle()
    {
        try {
            Log::info("Starting background stock update job", [
                'warehouse_id' => $this->warehouseId ?: 'all warehouses',
                'job_id' => $this->job->getJobId()
            ]);

            // Run the scheduled stock update command in the background
            $exitCode = Artisan::call('stock:scheduled-update', [
                '--warehouse-id' => $this->warehouseId
            ]);

            if ($exitCode === 0) {
                Log::info("Background stock update job completed successfully", [
                    'warehouse_id' => $this->warehouseId ?: 'all warehouses',
                    'job_id' => $this->job->getJobId()
                ]);
            } else {
                throw new \Exception("Stock update command failed with exit code: {$exitCode}");
            }

        } catch (\Exception $e) {
            Log::error("Background stock update job failed", [
                'warehouse_id' => $this->warehouseId ?: 'all warehouses',
                'job_id' => $this->job->getJobId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Stock update job permanently failed", [
            'warehouse_id' => $this->warehouseId ?: 'all warehouses',
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // You could add notification logic here to alert administrators
        // For example, send an email or Slack notification about the failure
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return [
            'stock-update',
            'warehouse:' . ($this->warehouseId ?: 'all'),
            'background-process'
        ];
    }
}
