<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class LogActivityJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     */
    public int $backoff = 5;

    /**
     * Create a new job instance.
     *
     * @param  array{user_id: ?int, event_type: string, target_model: string, target_id: ?int, description: string, old_values: ?array, new_values: ?array, user_agent: ?string}  $logData
     */
    public function __construct(
        public array $logData
    ) {
        $this->onQueue('activity-logs');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            ActivityLog::create($this->logData);
        } catch (QueryException $e) {
            Log::error('LogActivityJob failed', [
                'error' => $e->getMessage(),
                'data' => $this->logData,
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('LogActivityJob permanently failed', [
            'error' => $exception->getMessage(),
            'data' => $this->logData,
        ]);
    }
}
