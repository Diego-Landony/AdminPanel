<?php

namespace App\Console\Commands;

use App\Services\Wallet\GoogleWalletService;
use Illuminate\Console\Command;

class CreateGoogleWalletClass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:create-google-class';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update the Google Wallet LoyaltyClass for Subway Guatemala';

    /**
     * Execute the console command.
     */
    public function handle(GoogleWalletService $service): int
    {
        $this->info('Creating/updating Google Wallet LoyaltyClass...');

        try {
            $class = $service->createOrUpdateClass();
            $this->info("LoyaltyClass created/updated successfully: {$class->getId()}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
