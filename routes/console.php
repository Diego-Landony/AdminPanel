<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cleanup expired tokens daily (tokens expired more than 7 days ago)
Schedule::command('sanctum:prune-expired --hours=168')->daily();

// Cleanup inactive devices daily
// - Mark devices as inactive after 6 months (180 days) of no use
// - Soft delete devices inactive for 1 year (365 days)
Schedule::command('devices:cleanup --inactive-days=180 --delete-days=365')->daily();

// Purge customers that were soft-deleted more than 30 days ago
Schedule::command('customers:purge-deleted --days=30')->daily();

// Expire inactive points daily (6 months inactivity)
Schedule::job(new \App\Jobs\ExpireInactivePoints)->daily();

// Cleanup expired carts every hour
Schedule::job(new \App\Jobs\CleanupExpiredCarts)->hourly();
