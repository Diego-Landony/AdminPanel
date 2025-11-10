<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupOldTokens extends Command
{
    protected $signature = 'tokens:cleanup
                            {--days=7 : Delete tokens expired more than this many days ago}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Cleanup expired Sanctum tokens from the database';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $expirationDate = now()->subDays($days);

        $this->info("Looking for tokens expired before: {$expirationDate->format('Y-m-d H:i:s')}");

        $query = DB::table('personal_access_tokens')
            ->where('expires_at', '<', $expirationDate);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No expired tokens found to cleanup.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("DRY RUN: Would delete {$count} expired tokens.");

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Successfully deleted {$deleted} expired tokens.");

        return self::SUCCESS;
    }
}
