<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeDeletedCustomersCommand extends Command
{
    protected $signature = 'customers:purge-deleted
                            {--days=30 : Number of days after soft delete to permanently delete}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Permanently delete customers that were soft-deleted more than X days ago';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info('Purging permanently deleted customers...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Find customers deleted more than X days ago
        $customersToDelete = Customer::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days))
            ->get();

        $count = $customersToDelete->count();

        if ($count === 0) {
            $this->info("✓ No customers to purge (deleted more than {$days} days ago)");
            $this->newLine();

            return self::SUCCESS;
        }

        // Show summary
        $this->table(
            ['ID', 'Name', 'Email', 'Deleted At', 'Days Ago'],
            $customersToDelete->map(function (Customer $customer) {
                return [
                    $customer->id,
                    $customer->full_name,
                    $customer->email,
                    $customer->deleted_at->format('Y-m-d H:i:s'),
                    $customer->deleted_at->diffInDays(now()),
                ];
            })
        );

        $this->newLine();

        if ($dryRun) {
            $this->warn("Would permanently delete {$count} customer(s)");
            $this->newLine();

            return self::SUCCESS;
        }

        // Confirm before deleting
        if (! $this->confirm("Are you sure you want to permanently delete {$count} customer(s)?", false)) {
            $this->info('Operation cancelled');

            return self::SUCCESS;
        }

        // Permanently delete customers
        $deletedCount = 0;
        $errors = [];

        foreach ($customersToDelete as $customer) {
            try {
                // Log before deletion for audit trail
                Log::info('Permanently deleting customer', [
                    'customer_id' => $customer->id,
                    'email' => $customer->email,
                    'full_name' => $customer->full_name,
                    'deleted_at' => $customer->deleted_at,
                    'days_since_deletion' => $customer->deleted_at->diffInDays(now()),
                ]);

                // Force delete (hard delete)
                $customer->forceDelete();

                $deletedCount++;
            } catch (\Exception $e) {
                $errors[] = [
                    'customer_id' => $customer->id,
                    'email' => $customer->email,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to permanently delete customer', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Show results
        if ($deletedCount > 0) {
            $this->info("✓ Permanently deleted {$deletedCount} customer(s)");

            Log::info('Customer purge completed', [
                'deleted_count' => $deletedCount,
                'days_threshold' => $days,
            ]);
        }

        if (count($errors) > 0) {
            $this->newLine();
            $this->error('Failed to delete '.count($errors).' customer(s):');
            $this->table(
                ['Customer ID', 'Email', 'Error'],
                collect($errors)->map(fn ($error) => [
                    $error['customer_id'],
                    $error['email'],
                    $error['error'],
                ])
            );
        }

        $this->newLine();
        $this->info('Customer purge completed!');

        return self::SUCCESS;
    }
}
