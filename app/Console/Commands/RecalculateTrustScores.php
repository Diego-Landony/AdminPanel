<?php

namespace App\Console\Commands;

use App\Models\CustomerDevice;
use App\Services\DeviceService;
use Illuminate\Console\Command;

class RecalculateTrustScores extends Command
{
    protected $signature = 'devices:recalculate-trust-scores
                            {--dry-run : Show what would be changed without actually changing}';

    protected $description = 'Recalculate trust scores for all active customer devices';

    public function __construct(protected DeviceService $deviceService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Recalculating trust scores for all active devices...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $devices = CustomerDevice::where('is_active', true)->get();

        if ($devices->isEmpty()) {
            $this->info('No active devices found.');

            return self::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($devices->count());
        $progressBar->start();

        $updatedCount = 0;

        foreach ($devices as $device) {
            $oldScore = $device->trust_score;
            $newScore = $this->deviceService->calculateTrustScore($device);

            if ($oldScore !== $newScore) {
                if (! $dryRun) {
                    $device->trust_score = $newScore;
                    $device->save();
                }
                $updatedCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($updatedCount > 0) {
            $this->info("✓ Updated trust scores for {$updatedCount} devices");
        } else {
            $this->info('✓ All trust scores are up to date');
        }

        $this->newLine();
        $this->info('Trust score recalculation completed successfully!');

        return self::SUCCESS;
    }
}
