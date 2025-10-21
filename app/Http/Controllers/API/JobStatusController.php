<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\JobStatus;
use App\Models\ScheduledJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class JobStatusController extends Controller
{
    /**
     * Get latest job statuses
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 20);
            $limit = min($limit, 100); // Max 100 records

            $jobStatuses = JobStatus::getLatest($limit);

            // Add human-readable time differences
            $jobStatuses->transform(function ($job) {
                $job->created_at_human = $job->created_at->diffForHumans();
                $job->updated_at_human = $job->updated_at->diffForHumans();
                return $job;
            });

            return response()->json([
                'success' => true,
                'data' => $jobStatuses,
                'meta' => [
                    'running_count' => JobStatus::getRunningCount(),
                    'today_failed_count' => JobStatus::getTodayFailedCount(),
                    'total_count' => JobStatus::count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch job statuses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get scheduled jobs
     *
     * @return JsonResponse
     */
    public function getScheduledJobs(): JsonResponse
    {
        try {
            $scheduledJobs = ScheduledJob::getActive();

            // Add human-readable time information
            $scheduledJobs->transform(function ($job) {
                $job->scheduled_time_human = Carbon::parse($job->scheduled_time)->format('h:i A');
                $job->next_run_human = $job->next_run_at ? $job->next_run_at->diffForHumans() : 'Not scheduled';
                $job->last_run_human = $job->last_run_at ? $job->last_run_at->diffForHumans() : 'Never';
                return $job;
            });

            return response()->json([
                'success' => true,
                'data' => $scheduledJobs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch scheduled jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create or update a scheduled job
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeScheduledJob(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'job_class' => 'required|string|max:255',
                'queue_name' => 'nullable|string|max:255',
                'scheduled_time' => 'required|date_format:H:i',
                'timezone' => 'nullable|string|max:50',
                'is_active' => 'boolean',
                'job_parameters' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['timezone'] = $data['timezone'] ?? 'Asia/Dhaka';

            // Check if job with same name exists
            $existingJob = ScheduledJob::where('name', $data['name'])->first();

            if ($existingJob) {
                $existingJob->update($data);
                $existingJob->calculateNextRun();
                $scheduledJob = $existingJob;
                $message = 'Scheduled job updated successfully';
            } else {
                $scheduledJob = ScheduledJob::create($data);
                $scheduledJob->calculateNextRun();
                $message = 'Scheduled job created successfully';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $scheduledJob
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save scheduled job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update scheduled job status (activate/deactivate)
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateScheduledJob(Request $request, int $id): JsonResponse
    {
        try {
            $scheduledJob = ScheduledJob::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'is_active' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $scheduledJob->update([
                'is_active' => $request->is_active
            ]);

            if ($request->is_active) {
                $scheduledJob->calculateNextRun();
            }

            return response()->json([
                'success' => true,
                'message' => 'Scheduled job updated successfully',
                'data' => $scheduledJob
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update scheduled job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a scheduled job
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteScheduledJob(int $id): JsonResponse
    {
        try {
            $scheduledJob = ScheduledJob::findOrFail($id);
            $scheduledJob->delete();

            return response()->json([
                'success' => true,
                'message' => 'Scheduled job deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete scheduled job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clean up old job statuses
     *
     * @return JsonResponse
     */
    public function cleanup(): JsonResponse
    {
        try {
            JobStatus::cleanup();

            return response()->json([
                'success' => true,
                'message' => 'Job statuses cleaned up successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup job statuses',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
