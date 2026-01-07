<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class LogActivityBatchJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     *
     * @param  array<array{user_id: ?int, event_type: string, target_model: string, target_id: ?int, description: string, old_values: ?array, new_values: ?array, user_agent: ?string}>  $logsData
     */
    public function __construct(
        public array $logsData
    ) {
        $this->onQueue('activity-logs');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->logsData)) {
            return;
        }

        try {
            $now = now();
            $logs = array_map(function ($log) use ($now) {
                $log['created_at'] = $now;
                $log['updated_at'] = $now;

                return $log;
            }, $this->logsData);

            ActivityLog::insert($logs);
        } catch (\Exception $e) {
            Log::error('LogActivityBatchJob failed', [
                'error' => $e->getMessage(),
                'count' => count($this->logsData),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('LogActivityBatchJob permanently failed', [
            'error' => $exception->getMessage(),
            'count' => count($this->logsData),
        ]);
    }
}
