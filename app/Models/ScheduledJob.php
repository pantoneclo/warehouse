<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ScheduledJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'job_class',
        'queue_name',
        'scheduled_time',
        'timezone',
        'is_active',
        'job_parameters',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'job_parameters' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'scheduled_time' => 'datetime:H:i:s',
    ];

    /**
     * Get jobs that are due to run
     */
    public static function getDueJobs()
    {
        return self::where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->get();
    }

    /**
     * Calculate next run time based on scheduled time
     */
    public function calculateNextRun()
    {
        $timezone = $this->timezone ?? 'Asia/Dhaka';
        $now = Carbon::now($timezone);
        $scheduledTime = Carbon::parse($this->scheduled_time, $timezone);
        
        // Set the scheduled time for today
        $nextRun = $now->copy()
            ->setTime($scheduledTime->hour, $scheduledTime->minute, $scheduledTime->second);
        
        // If the time has already passed today, schedule for tomorrow
        if ($nextRun <= $now) {
            $nextRun->addDay();
        }
        
        // Convert back to UTC for storage
        $this->next_run_at = $nextRun->utc();
        $this->save();
        
        return $this->next_run_at;
    }

    /**
     * Mark job as executed
     */
    public function markAsExecuted()
    {
        $this->last_run_at = now();
        $this->calculateNextRun();
    }

    /**
     * Get active scheduled jobs
     */
    public static function getActive()
    {
        return self::where('is_active', true)
            ->orderBy('scheduled_time')
            ->get();
    }
}
