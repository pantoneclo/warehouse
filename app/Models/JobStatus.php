<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_name',
        'queue_name',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_DONE = 'done';
    const STATUS_FAILED = 'failed';

    /**
     * Get the latest job statuses
     */
    public static function getLatest($limit = 20)
    {
        return self::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get running jobs count
     */
    public static function getRunningCount()
    {
        return self::where('status', self::STATUS_RUNNING)->count();
    }

    /**
     * Get failed jobs count for today
     */
    public static function getTodayFailedCount()
    {
        return self::where('status', self::STATUS_FAILED)
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Clean up old job statuses (keep only last 1000 records)
     */
    public static function cleanup()
    {
        $keepIds = self::orderBy('created_at', 'desc')
            ->limit(1000)
            ->pluck('id');
            
        if ($keepIds->isNotEmpty()) {
            self::whereNotIn('id', $keepIds)->delete();
        }
    }
}
