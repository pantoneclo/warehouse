<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledJob;
use Illuminate\Support\Facades\Log;

class RunScheduledJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:run-scheduled 
                            {--dry-run : Show what would be executed without actually running}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled jobs that are due';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        try {
            $dueJobs = ScheduledJob::getDueJobs();

            if ($dueJobs->isEmpty()) {
                $this->info('No scheduled jobs are due to run.');
                return 0;
            }

            $this->info("Found {$dueJobs->count()} scheduled job(s) due to run:");

            foreach ($dueJobs as $scheduledJob) {
                $this->line("- {$scheduledJob->name} (Class: {$scheduledJob->job_class})");

                if (!$dryRun) {
                    $this->runScheduledJob($scheduledJob);
                }
            }

            if ($dryRun) {
                $this->warn('DRY RUN: No jobs were actually executed.');
            } else {
                $this->info('All scheduled jobs have been processed.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Error running scheduled jobs: " . $e->getMessage());
            Log::error("Scheduled jobs command failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Run a specific scheduled job
     *
     * @param ScheduledJob $scheduledJob
     */
    private function runScheduledJob(ScheduledJob $scheduledJob)
    {
        try {
            $this->info("Executing: {$scheduledJob->name}");

            // Get the job class
            $jobClass = $scheduledJob->job_class;

            if (!class_exists($jobClass)) {
                throw new \Exception("Job class {$jobClass} does not exist");
            }

            // Get job parameters
            $parameters = $scheduledJob->job_parameters ?? [];

            // Dispatch the job
            if (empty($parameters)) {
                $jobClass::dispatch();
            } else {
                // If parameters exist, pass them to the constructor
                $jobClass::dispatch(...array_values($parameters));
            }

            // Mark as executed
            $scheduledJob->markAsExecuted();

            $this->info("✓ Successfully dispatched: {$scheduledJob->name}");

            Log::info("Scheduled job dispatched", [
                'job_name' => $scheduledJob->name,
                'job_class' => $jobClass,
                'parameters' => $parameters,
                'next_run' => $scheduledJob->next_run_at
            ]);

        } catch (\Exception $e) {
            $this->error("✗ Failed to execute {$scheduledJob->name}: " . $e->getMessage());

            Log::error("Scheduled job execution failed", [
                'job_name' => $scheduledJob->name,
                'job_class' => $scheduledJob->job_class,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
