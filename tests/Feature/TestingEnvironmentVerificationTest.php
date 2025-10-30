<?php

use Illuminate\Support\Facades\DB;

test('verifies testing environment is configured correctly', function () {
    // Verify APP_ENV is set to testing
    expect(config('app.env'))->toBe('testing');
});

test('verifies database connection is mariadb_testing', function () {
    $connection = config('database.default');
    expect($connection)->toBe('mariadb_testing');
});

test('verifies database name is subwayapp_test', function () {
    $connection = config('database.default');
    $database = config("database.connections.{$connection}.database");

    expect($database)->toBe('subwayapp_test');
});

test('verifies production database is NOT being used', function () {
    $connection = config('database.default');
    $database = config("database.connections.{$connection}.database");

    expect($database)->not->toBe('subwayapp');
});

test('verifies can connect to test database', function () {
    // This will throw exception if connection fails
    $result = DB::connection()->getPdo();

    expect($result)->not->toBeNull();
});

test('verifies actual database name via SQL query', function () {
    $databaseName = DB::selectOne('SELECT DATABASE() as db')->db;

    expect($databaseName)->toBe('subwayapp_test');
    expect($databaseName)->not->toBe('subwayapp');
});

test('verifies RefreshDatabase trait is working', function () {
    // This test will fail if RefreshDatabase is not working properly
    // The migrations table should exist after RefreshDatabase runs
    $tablesExist = DB::selectOne(
        "SELECT COUNT(*) as count FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'migrations'"
    )->count;

    expect($tablesExist)->toBeGreaterThan(0);
});
