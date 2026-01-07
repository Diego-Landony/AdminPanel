<?php

use App\Jobs\LogActivityJob;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\User;
use App\Support\ActivityLogging;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure logging is enabled and sync for tests
    ActivityLogging::reset();
    ActivityLogging::enableSync();
});

afterEach(function () {
    ActivityLogging::reset();
});

describe('ActivityLogging Toggle', function () {
    it('can disable activity logging', function () {
        ActivityLogging::disable();

        expect(ActivityLogging::isEnabled())->toBeFalse();
    });

    it('can enable activity logging', function () {
        ActivityLogging::disable();
        ActivityLogging::enable();

        expect(ActivityLogging::isEnabled())->toBeTrue();
    });

    it('can enable sync mode', function () {
        ActivityLogging::enableAsync();
        ActivityLogging::enableSync();

        expect(ActivityLogging::isAsync())->toBeFalse();
    });

    it('can enable async mode', function () {
        ActivityLogging::enableSync();
        ActivityLogging::enableAsync();

        expect(ActivityLogging::isAsync())->toBeTrue();
    });

    it('can reset to default state', function () {
        ActivityLogging::disable();
        ActivityLogging::enableSync();

        ActivityLogging::reset();

        expect(ActivityLogging::isEnabled())->toBeTrue();
        expect(ActivityLogging::isAsync())->toBeTrue();
    });

    it('can execute callback without logging', function () {
        $result = ActivityLogging::withoutLogging(function () {
            return 'test_result';
        });

        expect($result)->toBe('test_result');
    });

    it('disables logging during withoutLogging callback', function () {
        $wasEnabled = null;

        ActivityLogging::withoutLogging(function () use (&$wasEnabled) {
            $wasEnabled = ActivityLogging::isEnabled();
        });

        expect($wasEnabled)->toBeFalse();
        expect(ActivityLogging::isEnabled())->toBeTrue();
    });

    it('re-enables logging after withoutLogging callback even on exception', function () {
        try {
            ActivityLogging::withoutLogging(function () {
                throw new Exception('Test exception');
            });
        } catch (Exception $e) {
            // Expected
        }

        expect(ActivityLogging::isEnabled())->toBeTrue();
    });

    it('can execute callback with sync logging', function () {
        ActivityLogging::enableAsync();

        $wasSync = null;
        ActivityLogging::sync(function () use (&$wasSync) {
            $wasSync = ! ActivityLogging::isAsync();
        });

        expect($wasSync)->toBeTrue();
        expect(ActivityLogging::isAsync())->toBeTrue();
    });
});

describe('ActivityObserver', function () {
    it('logs when a customer is created via HTTP request', function () {
        $user = createTestUser();
        $this->actingAs($user);

        $customerData = [
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'test-activity@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subway_card' => '9999999999',
            'birth_date' => '1990-05-15',
        ];

        $this->post('/customers', $customerData);

        expect(ActivityLog::where('event_type', 'created')
            ->where('target_model', Customer::class)
            ->exists()
        )->toBeTrue();
    });

    it('logs when a customer is updated via HTTP request', function () {
        $user = createTestUser();
        $this->actingAs($user);

        $customer = Customer::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
        ]);

        $this->put("/customers/{$customer->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $customer->email,
            'subway_card' => $customer->subway_card,
            'birth_date' => $customer->birth_date->format('Y-m-d'),
        ]);

        expect(ActivityLog::where('event_type', 'updated')
            ->where('target_model', Customer::class)
            ->where('target_id', $customer->id)
            ->exists()
        )->toBeTrue();
    });

    it('logs when a customer is deleted via HTTP request', function () {
        $user = createTestUser();
        $this->actingAs($user);

        $customer = Customer::factory()->create();

        $this->delete("/customers/{$customer->id}");

        expect(ActivityLog::where('event_type', 'deleted')
            ->where('target_model', Customer::class)
            ->where('target_id', $customer->id)
            ->exists()
        )->toBeTrue();
    });

    it('does not log when activity logging is disabled', function () {
        $user = createTestUser();
        $this->actingAs($user);

        ActivityLogging::disable();

        $customerData = [
            'first_name' => 'No',
            'last_name' => 'Log',
            'email' => 'nolog@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subway_card' => '8888888888',
            'birth_date' => '1990-05-15',
        ];

        $this->post('/customers', $customerData);

        expect(ActivityLog::where('target_model', Customer::class)
            ->where('description', 'like', '%No Log%')
            ->exists()
        )->toBeFalse();
    });

    it('includes user_id in activity log', function () {
        $user = createTestUser();
        $this->actingAs($user);

        $customerData = [
            'first_name' => 'With',
            'last_name' => 'UserId',
            'email' => 'withuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subway_card' => '7777777777',
            'birth_date' => '1990-05-15',
        ];

        $this->post('/customers', $customerData);

        $log = ActivityLog::where('target_model', Customer::class)
            ->where('event_type', 'created')
            ->latest()
            ->first();

        expect($log)->not->toBeNull();
        expect($log->user_id)->toBe($user->id);
    });

    it('includes user_agent in activity log', function () {
        $user = createTestUser();
        $this->actingAs($user);

        $customerData = [
            'first_name' => 'With',
            'last_name' => 'UserAgent',
            'email' => 'withagent@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subway_card' => '6666666666',
            'birth_date' => '1990-05-15',
        ];

        $this->withHeader('User-Agent', 'TestBrowser/1.0')
            ->post('/customers', $customerData);

        $log = ActivityLog::where('target_model', Customer::class)
            ->where('event_type', 'created')
            ->latest()
            ->first();

        expect($log)->not->toBeNull();
        expect($log->user_agent)->toBe('TestBrowser/1.0');
    });
});

describe('Ignored Fields', function () {
    it('does not include password in logged values', function () {
        $user = createTestUser();
        $this->actingAs($user);

        $customerData = [
            'first_name' => 'Password',
            'last_name' => 'Test',
            'email' => 'passwordtest@example.com',
            'password' => 'secretpassword123',
            'password_confirmation' => 'secretpassword123',
            'subway_card' => '5555555555',
            'birth_date' => '1990-05-15',
        ];

        $this->post('/customers', $customerData);

        $log = ActivityLog::where('target_model', Customer::class)
            ->where('event_type', 'created')
            ->latest()
            ->first();

        expect($log)->not->toBeNull();

        if ($log->new_values) {
            expect($log->new_values)->not->toHaveKey('password');
        }
    });

    it('does not include updated_at in logged values', function () {
        $user = createTestUser();
        $this->actingAs($user);

        $customer = Customer::factory()->create();

        $this->put("/customers/{$customer->id}", [
            'first_name' => 'UpdatedFirst',
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'subway_card' => $customer->subway_card,
            'birth_date' => $customer->birth_date->format('Y-m-d'),
        ]);

        $log = ActivityLog::where('target_model', Customer::class)
            ->where('event_type', 'updated')
            ->where('target_id', $customer->id)
            ->first();

        expect($log)->not->toBeNull();

        if ($log->new_values) {
            expect($log->new_values)->not->toHaveKey('updated_at');
        }

        if ($log->old_values) {
            expect($log->old_values)->not->toHaveKey('updated_at');
        }
    });

    it('does not include remember_token in logged values', function () {
        $user = createTestUser();
        $this->actingAs($user);

        $customerData = [
            'first_name' => 'Token',
            'last_name' => 'Test',
            'email' => 'tokentest@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subway_card' => '4444444444',
            'birth_date' => '1990-05-15',
        ];

        $this->post('/customers', $customerData);

        $log = ActivityLog::where('target_model', Customer::class)
            ->where('event_type', 'created')
            ->latest()
            ->first();

        expect($log)->not->toBeNull();

        if ($log->new_values) {
            expect($log->new_values)->not->toHaveKey('remember_token');
        }
    });

    it('does not log when only ignored fields change', function () {
        $user = createTestUser();
        $this->actingAs($user);

        $customer = Customer::factory()->create();
        $initialLogCount = ActivityLog::where('target_model', Customer::class)
            ->where('target_id', $customer->id)
            ->count();

        // Manually update only updated_at (which is ignored)
        // This simulates a scenario where only ignored fields are modified
        $customer->timestamps = false;
        $customer->updated_at = now()->addHour();
        $customer->save();

        $newLogCount = ActivityLog::where('target_model', Customer::class)
            ->where('target_id', $customer->id)
            ->where('event_type', 'updated')
            ->count();

        // Should not create a new log since only ignored fields changed
        expect($newLogCount)->toBe($initialLogCount);
    });
});

describe('LogActivityJob', function () {
    it('creates activity log when dispatched synchronously', function () {
        $data = [
            'user_id' => 1,
            'event_type' => 'test_event',
            'target_model' => User::class,
            'target_id' => 1,
            'description' => 'Test description',
            'old_values' => null,
            'new_values' => ['name' => 'Test'],
            'user_agent' => 'Test Agent',
        ];

        LogActivityJob::dispatchSync($data);

        expect(ActivityLog::where('event_type', 'test_event')->exists())->toBeTrue();

        $log = ActivityLog::where('event_type', 'test_event')->first();
        expect($log->description)->toBe('Test description');
        expect($log->user_agent)->toBe('Test Agent');
        expect($log->new_values)->toBe(['name' => 'Test']);
    });

    it('stores all required fields correctly', function () {
        $data = [
            'user_id' => 42,
            'event_type' => 'custom_event',
            'target_model' => Customer::class,
            'target_id' => 123,
            'description' => 'Custom activity description',
            'old_values' => ['status' => 'inactive'],
            'new_values' => ['status' => 'active'],
            'user_agent' => 'Mozilla/5.0',
        ];

        LogActivityJob::dispatchSync($data);

        $log = ActivityLog::where('event_type', 'custom_event')->first();

        expect($log)->not->toBeNull();
        expect($log->user_id)->toBe(42);
        expect($log->target_model)->toBe(Customer::class);
        expect($log->target_id)->toBe(123);
        expect($log->description)->toBe('Custom activity description');
        expect($log->old_values)->toBe(['status' => 'inactive']);
        expect($log->new_values)->toBe(['status' => 'active']);
        expect($log->user_agent)->toBe('Mozilla/5.0');
    });

    it('queues job when async mode is enabled', function () {
        Queue::fake();
        ActivityLogging::enableAsync();

        $data = [
            'user_id' => 1,
            'event_type' => 'async_test',
            'target_model' => User::class,
            'target_id' => 1,
            'description' => 'Async test',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ];

        LogActivityJob::dispatch($data);

        Queue::assertPushed(LogActivityJob::class, function ($job) {
            return $job->logData['event_type'] === 'async_test';
        });
    });

    it('job is configured with correct queue name', function () {
        $data = [
            'user_id' => 1,
            'event_type' => 'queue_test',
            'target_model' => User::class,
            'target_id' => 1,
            'description' => 'Queue test',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ];

        $job = new LogActivityJob($data);

        expect($job->queue)->toBe('activity-logs');
    });

    it('handles null values gracefully', function () {
        $data = [
            'user_id' => null,
            'event_type' => 'null_test',
            'target_model' => User::class,
            'target_id' => null,
            'description' => 'Null values test',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ];

        LogActivityJob::dispatchSync($data);

        $log = ActivityLog::where('event_type', 'null_test')->first();

        expect($log)->not->toBeNull();
        expect($log->user_id)->toBeNull();
        expect($log->target_id)->toBeNull();
        expect($log->old_values)->toBeNull();
        expect($log->new_values)->toBeNull();
        expect($log->user_agent)->toBeNull();
    });
});

describe('ActivityLog Model', function () {
    it('can query logs by model type', function () {
        LogActivityJob::dispatchSync([
            'user_id' => 1,
            'event_type' => 'created',
            'target_model' => Customer::class,
            'target_id' => 1,
            'description' => 'Customer created',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ]);

        LogActivityJob::dispatchSync([
            'user_id' => 1,
            'event_type' => 'created',
            'target_model' => User::class,
            'target_id' => 1,
            'description' => 'User created',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ]);

        $customerLogs = ActivityLog::forModel(Customer::class)->get();
        $userLogs = ActivityLog::forModel(User::class)->get();

        expect($customerLogs)->toHaveCount(1);
        expect($userLogs)->toHaveCount(1);
    });

    it('can query logs by user', function () {
        LogActivityJob::dispatchSync([
            'user_id' => 100,
            'event_type' => 'created',
            'target_model' => Customer::class,
            'target_id' => 1,
            'description' => 'By user 100',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ]);

        LogActivityJob::dispatchSync([
            'user_id' => 200,
            'event_type' => 'created',
            'target_model' => Customer::class,
            'target_id' => 2,
            'description' => 'By user 200',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ]);

        $user100Logs = ActivityLog::byUser(100)->get();
        $user200Logs = ActivityLog::byUser(200)->get();

        expect($user100Logs)->toHaveCount(1);
        expect($user200Logs)->toHaveCount(1);
    });

    it('can query logs by event type', function () {
        LogActivityJob::dispatchSync([
            'user_id' => 1,
            'event_type' => 'created',
            'target_model' => Customer::class,
            'target_id' => 1,
            'description' => 'Created event',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ]);

        LogActivityJob::dispatchSync([
            'user_id' => 1,
            'event_type' => 'updated',
            'target_model' => Customer::class,
            'target_id' => 1,
            'description' => 'Updated event',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ]);

        $createdLogs = ActivityLog::ofType('created')->get();
        $updatedLogs = ActivityLog::ofType('updated')->get();

        expect($createdLogs)->toHaveCount(1);
        expect($updatedLogs)->toHaveCount(1);
    });

    it('can query logs by multiple event types', function () {
        LogActivityJob::dispatchSync([
            'user_id' => 1,
            'event_type' => 'created',
            'target_model' => Customer::class,
            'target_id' => 1,
            'description' => 'Created',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ]);

        LogActivityJob::dispatchSync([
            'user_id' => 1,
            'event_type' => 'updated',
            'target_model' => Customer::class,
            'target_id' => 1,
            'description' => 'Updated',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ]);

        LogActivityJob::dispatchSync([
            'user_id' => 1,
            'event_type' => 'deleted',
            'target_model' => Customer::class,
            'target_id' => 1,
            'description' => 'Deleted',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ]);

        $logs = ActivityLog::ofType(['created', 'updated'])->get();

        expect($logs)->toHaveCount(2);
    });

    it('casts old_values and new_values as arrays', function () {
        LogActivityJob::dispatchSync([
            'user_id' => 1,
            'event_type' => 'cast_test',
            'target_model' => Customer::class,
            'target_id' => 1,
            'description' => 'Cast test',
            'old_values' => ['key1' => 'old_value'],
            'new_values' => ['key1' => 'new_value'],
            'user_agent' => null,
        ]);

        $log = ActivityLog::where('event_type', 'cast_test')->first();

        expect($log->old_values)->toBeArray();
        expect($log->new_values)->toBeArray();
        expect($log->old_values['key1'])->toBe('old_value');
        expect($log->new_values['key1'])->toBe('new_value');
    });

    it('has user relationship', function () {
        $user = User::factory()->create();

        LogActivityJob::dispatchSync([
            'user_id' => $user->id,
            'event_type' => 'relation_test',
            'target_model' => Customer::class,
            'target_id' => 1,
            'description' => 'Relation test',
            'old_values' => null,
            'new_values' => null,
            'user_agent' => null,
        ]);

        $log = ActivityLog::where('event_type', 'relation_test')->first();

        expect($log->user)->not->toBeNull();
        expect($log->user->id)->toBe($user->id);
    });
});
