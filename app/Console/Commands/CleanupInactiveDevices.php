<?php

namespace App\Console\Commands;

use App\Services\DeviceService;
use Illuminate\Console\Command;

class CleanupInactiveDevices extends Command
{
    protected $signature = 'devices:cleanup
                            {--inactive-days=90 : Days of inactivity before marking device as inactive}
                            {--delete-days=180 : Days of inactivity before soft deleting device}
                            {--dry-run : Show what would be changed without actually changing}';

    protected $description = 'Cleanup inactive customer devices (mark inactive after 90 days, delete after 180 days)';

    public function __construct(protected DeviceService $deviceService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $inactiveDays = (int) $this->option('inactive-days');
        $deleteDays = (int) $this->option('delete-days');
        $dryRun = $this->option('dry-run');

        $this->info('Cleaning up inactive devices...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $deactivatedCount = 0;
        $deletedCount = 0;

        if (! $dryRun) {
            $deactivatedCount = $this->deviceService->deactivateInactiveDevices($inactiveDays);
            $deletedCount = $this->deviceService->cleanupOldDevices($deleteDays);
        } else {
            $deactivatedCount = \App\Models\CustomerDevice::where('is_active', true)
                ->where('last_used_at', '<', now()->subDays($inactiveDays))
                ->count();

            $deletedCount = \App\Models\CustomerDevice::where('is_active', false)
                ->where('last_used_at', '<', now()->subDays($deleteDays))
                ->count();
        }

        if ($deactivatedCount > 0) {
            $this->info("✓ Marked {$deactivatedCount} devices as inactive (not used in {$inactiveDays}+ days)");
        } else {
            $this->info('✓ No devices to mark as inactive');
        }

        if ($deletedCount > 0) {
            $this->info("✓ Soft deleted {$deletedCount} devices (inactive for {$deleteDays}+ days)");
        } else {
            $this->info('✓ No devices to delete');
        }

        $this->newLine();
        $this->info('Device cleanup completed successfully!');

        return self::SUCCESS;
    }
}
