<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Models\JobStatus;

class ProcessStockUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 7200; // 2 hours timeout for large datasets
    public $tries = 2; // Reduced tries since this is a full process
    public $maxExceptions = 1;

    protected $warehouseId;
    protected $jobStatusId;

    /**
     * Create a new job instance.
     *
     * @param int|null $warehouseId Optional warehouse ID, null for all warehouses
     */
    public function __construct($warehouseId = null)
    {
        $this->warehouseId = $warehouseId;

        // Create job status record when job is dispatched
        $this->jobStatusId = JobStatus::create([
            'job_name' => 'Stock Update',
            'queue_name' => 'stock-updates',
            'status' => JobStatus::STATUS_PENDING,
            'meta' => [
                'warehouse_id' => $warehouseId,
                'dispatched_at' => now()->toISOString(),
            ]
        ])->id;
    }

    /**
     * Execute the job.
     * Runs the same logic as the scheduled stock update command
     */
    public function handle()
    {
        // Update job status to running
        $this->updateJobStatus(JobStatus::STATUS_RUNNING, [
            'started_at' => now()->toISOString(),
        ]);

        try {
            Log::info("Starting background stock update job", [
                'warehouse_id' => $this->warehouseId ?: 'all warehouses',
                'job_id' => $this->job->getJobId(),
                'job_status_id' => $this->jobStatusId
            ]);

            // Run the scheduled stock update command in the background
            $exitCode = Artisan::call('stock:scheduled-update', [
                '--warehouse-id' => $this->warehouseId
            ]);

            if ($exitCode === 0) {
                Log::info("Background stock update job completed successfully", [
                    'warehouse_id' => $this->warehouseId ?: 'all warehouses',
                    'job_id' => $this->job->getJobId(),
                    'job_status_id' => $this->jobStatusId
                ]);

                // Update job status to done
                $this->updateJobStatus(JobStatus::STATUS_DONE, [
                    'completed_at' => now()->toISOString(),
                    'exit_code' => $exitCode,
                ]);
            } else {
                throw new \Exception("Stock update command failed with exit code: {$exitCode}");
            }

        } catch (\Exception $e) {
            Log::error("Background stock update job failed", [
                'warehouse_id' => $this->warehouseId ?: 'all warehouses',
                'job_id' => $this->job->getJobId(),
                'job_status_id' => $this->jobStatusId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update job status to failed
            $this->updateJobStatus(JobStatus::STATUS_FAILED, [
                'failed_at' => now()->toISOString(),
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
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
            'job_status_id' => $this->jobStatusId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Update job status to failed (final failure)
        $this->updateJobStatus(JobStatus::STATUS_FAILED, [
            'permanently_failed_at' => now()->toISOString(),
            'final_error_message' => $exception->getMessage(),
            'final_error_trace' => $exception->getTraceAsString(),
            'retry_attempts' => $this->attempts(),
        ]);

        // You could add notification logic here to alert administrators
        // For example, send an email or Slack notification about the failure
    }

    /**
     * Update job status in database
     */
    private function updateJobStatus($status, $additionalMeta = [])
    {
        try {
            $jobStatus = JobStatus::find($this->jobStatusId);
            if ($jobStatus) {
                $currentMeta = $jobStatus->meta ?? [];
                $jobStatus->update([
                    'status' => $status,
                    'meta' => array_merge($currentMeta, $additionalMeta)
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("Failed to update job status", [
                'job_status_id' => $this->jobStatusId,
                'error' => $e->getMessage()
            ]);
        }
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
