<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserActivity;
use App\Services\ActivityFeedService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

describe('ActivityFeedService', function () {
    describe('getFeed', function () {
        test('returns paginated feed', function () {
            $user = User::factory()->create();
            ActivityLog::factory()->count(20)->create(['user_id' => $user->id]);

            $service = new ActivityFeedService;
            $feed = $service->getFeed([], 15);

            expect($feed)->toBeInstanceOf(LengthAwarePaginator::class);
            expect($feed->perPage())->toBe(15);
        });

        test('excludes heartbeat and page_view events', function () {
            $user = User::factory()->create();

            // Create excluded events
            UserActivity::factory()->heartbeat()->create(['user_id' => $user->id]);
            UserActivity::factory()->pageView()->create(['user_id' => $user->id]);
            ActivityLog::factory()->create([
                'user_id' => $user->id,
                'event_type' => 'heartbeat',
            ]);
            ActivityLog::factory()->create([
                'user_id' => $user->id,
                'event_type' => 'page_view',
            ]);

            // Create valid events
            UserActivity::factory()->login()->create(['user_id' => $user->id]);
            ActivityLog::factory()->created()->create(['user_id' => $user->id]);

            $service = new ActivityFeedService;
            $feed = $service->getFeed([], 50);

            expect($feed->total())->toBe(2);
        });

        test('filters by date range', function () {
            $user = User::factory()->create();

            // Create log from yesterday
            ActivityLog::factory()->create([
                'user_id' => $user->id,
                'created_at' => now()->subDay(),
            ]);

            // Create log from today
            ActivityLog::factory()->create([
                'user_id' => $user->id,
                'created_at' => now(),
            ]);

            $service = new ActivityFeedService;
            $feed = $service->getFeed([
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ], 50);

            expect($feed->total())->toBe(1);
        });

        test('filters by user', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            ActivityLog::factory()->count(5)->create(['user_id' => $user1->id]);
            ActivityLog::factory()->count(3)->create(['user_id' => $user2->id]);

            $service = new ActivityFeedService;
            $feed = $service->getFeed(['user_id' => $user1->id], 50);

            expect($feed->total())->toBe(5);
        });

        test('filters by event type', function () {
            $user = User::factory()->create();

            ActivityLog::factory()->created()->create(['user_id' => $user->id]);
            ActivityLog::factory()->updated()->create(['user_id' => $user->id]);
            ActivityLog::factory()->deleted()->create(['user_id' => $user->id]);

            $service = new ActivityFeedService;
            $feed = $service->getFeed(['event_type' => 'created'], 50);

            expect($feed->total())->toBe(1);
        });

        test('filters by multiple event types', function () {
            $user = User::factory()->create();

            ActivityLog::factory()->created()->create(['user_id' => $user->id]);
            ActivityLog::factory()->updated()->create(['user_id' => $user->id]);
            ActivityLog::factory()->deleted()->create(['user_id' => $user->id]);

            $service = new ActivityFeedService;
            $feed = $service->getFeed(['event_type' => 'created,updated'], 50);

            expect($feed->total())->toBe(2);
        });

        test('filters by search term in description', function () {
            $user = User::factory()->create();

            ActivityLog::factory()->create([
                'user_id' => $user->id,
                'description' => 'Usuario creado exitosamente',
            ]);

            ActivityLog::factory()->create([
                'user_id' => $user->id,
                'description' => 'Orden actualizada',
            ]);

            $service = new ActivityFeedService;
            $feed = $service->getFeed(['search' => 'Usuario'], 50);

            expect($feed->total())->toBe(1);
        });

        test('combines UserActivity and ActivityLog results', function () {
            $user = User::factory()->create();

            ActivityLog::factory()->count(3)->create(['user_id' => $user->id]);
            UserActivity::factory()->login()->count(2)->create(['user_id' => $user->id]);

            $service = new ActivityFeedService;
            $feed = $service->getFeed([], 50);

            expect($feed->total())->toBe(5);
        });

        test('loads user data for feed items', function () {
            $user = User::factory()->create(['name' => 'Test User']);
            ActivityLog::factory()->create(['user_id' => $user->id]);

            $service = new ActivityFeedService;
            $feed = $service->getFeed([], 50);

            $item = $feed->items()[0];
            expect($item->user['name'])->toBe('Test User');
            expect($item->user['email'])->toBe($user->email);
            expect($item->user['initials'])->toBe('TU');
        });

        test('handles deleted users gracefully', function () {
            // Create a user and another user for reference
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            // Create activity log for user1 (will be deleted) and user2 (will remain)
            ActivityLog::factory()->create(['user_id' => $user1->id]);
            ActivityLog::factory()->create(['user_id' => $user2->id]);

            // Delete user1 - this sets user_id to NULL in activity_logs due to ON DELETE SET NULL
            $user1->forceDelete();

            $service = new ActivityFeedService;
            $feed = $service->getFeed([], 50);

            // Find the item with null user_id (deleted user) and the one with valid user
            $items = collect($feed->items());
            $nullUserItem = $items->first(fn ($item) => $item->user_id === null);
            $validUserItem = $items->first(fn ($item) => $item->user_id === $user2->id);

            // The valid user should have proper user data
            expect($validUserItem->user['name'])->toBe($user2->name);

            // The null user_id item should show "Usuario eliminado"
            expect($nullUserItem->user['name'])->toBe('Usuario eliminado');
            expect($nullUserItem->user['initials'])->toBe('UD');
        });

        test('orders results by created_at descending', function () {
            $user = User::factory()->create();

            $oldest = ActivityLog::factory()->create([
                'user_id' => $user->id,
                'created_at' => now()->subDays(2),
            ]);

            $newest = ActivityLog::factory()->create([
                'user_id' => $user->id,
                'created_at' => now(),
            ]);

            $middle = ActivityLog::factory()->create([
                'user_id' => $user->id,
                'created_at' => now()->subDay(),
            ]);

            $service = new ActivityFeedService;
            $feed = $service->getFeed([], 50);

            $items = $feed->items();
            expect($items[0]->created_at)->toBeGreaterThan($items[1]->created_at);
            expect($items[1]->created_at)->toBeGreaterThan($items[2]->created_at);
        });
    });

    describe('getStats', function () {
        test('returns correct stats structure', function () {
            $service = new ActivityFeedService;
            $stats = $service->getStats();

            expect($stats)->toHaveKeys(['total_events', 'unique_users', 'today_events']);
            expect($stats['total_events'])->toBeInt();
            expect($stats['unique_users'])->toBeInt();
            expect($stats['today_events'])->toBeInt();
        });

        test('counts total events correctly', function () {
            $user = User::factory()->create();
            ActivityLog::factory()->count(5)->create(['user_id' => $user->id]);
            UserActivity::factory()->login()->count(3)->create(['user_id' => $user->id]);

            $service = new ActivityFeedService;
            $stats = $service->getStats();

            expect($stats['total_events'])->toBe(8);
        });

        test('counts unique users correctly', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $user3 = User::factory()->create();

            ActivityLog::factory()->create(['user_id' => $user1->id]);
            ActivityLog::factory()->create(['user_id' => $user2->id]);
            UserActivity::factory()->login()->create(['user_id' => $user1->id]);
            UserActivity::factory()->login()->create(['user_id' => $user3->id]);

            $service = new ActivityFeedService;
            $stats = $service->getStats();

            expect($stats['unique_users'])->toBe(3);
        });

        test('counts today events correctly', function () {
            $user = User::factory()->create();

            // Events from yesterday
            ActivityLog::factory()->create([
                'user_id' => $user->id,
                'created_at' => now()->subDay(),
            ]);
            UserActivity::factory()->login()->create([
                'user_id' => $user->id,
                'created_at' => now()->subDay(),
            ]);

            // Events from today
            ActivityLog::factory()->count(3)->create([
                'user_id' => $user->id,
                'created_at' => now(),
            ]);
            UserActivity::factory()->login()->count(2)->create([
                'user_id' => $user->id,
                'created_at' => now(),
            ]);

            $service = new ActivityFeedService;
            $stats = $service->getStats();

            expect($stats['today_events'])->toBe(5);
        });

        test('excludes heartbeat and page_view from stats', function () {
            $user = User::factory()->create();

            // Excluded events
            UserActivity::factory()->heartbeat()->create(['user_id' => $user->id]);
            UserActivity::factory()->pageView()->create(['user_id' => $user->id]);
            ActivityLog::factory()->create([
                'user_id' => $user->id,
                'event_type' => 'heartbeat',
            ]);

            // Valid events
            ActivityLog::factory()->created()->create(['user_id' => $user->id]);

            $service = new ActivityFeedService;
            $stats = $service->getStats();

            expect($stats['total_events'])->toBe(1);
        });

        test('caches statistics', function () {
            $user = User::factory()->create();
            ActivityLog::factory()->count(5)->create(['user_id' => $user->id]);

            $service = new ActivityFeedService;

            // First call - should calculate
            $stats1 = $service->getStats();

            // Add more events after first call
            ActivityLog::factory()->count(3)->create(['user_id' => $user->id]);

            // Second call - should use cache
            $stats2 = $service->getStats();

            // Stats should be the same because of caching
            expect($stats1)->toEqual($stats2);

            // After clearing cache, stats should update
            Cache::flush();
            $stats3 = $service->getStats();

            expect($stats3['total_events'])->toBe(8);
        });

        test('uses different cache keys for different filters', function () {
            $user = User::factory()->create();
            ActivityLog::factory()->count(5)->create([
                'user_id' => $user->id,
                'created_at' => now(),
            ]);

            $service = new ActivityFeedService;

            // Get stats without filters
            $statsNoFilter = $service->getStats();

            // Get stats with date filter
            $statsWithFilter = $service->getStats([
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ]);

            // Both should have same values in this case since all events are today
            expect($statsNoFilter['total_events'])->toBe($statsWithFilter['total_events']);
        });
    });
});
