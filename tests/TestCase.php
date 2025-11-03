<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // CRITICAL SAFETY CHECK: Verify we are in testing environment
        if (config('app.env') !== 'testing') {
            throw new \Exception('Tests can ONLY run in testing environment! Current: '.config('app.env'));
        }

        // CRITICAL SAFETY CHECK: Verify we are using test database
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($database === 'subwayapp') {
            throw new \Exception(
                "DANGER: Tests attempting to use PRODUCTION database '{$database}'! ".
                "Expected 'subwayapp_test'. Connection: {$connection}"
            );
        }

        // Additional safety: verify we're using the correct connection
        if ($connection !== 'mariadb_testing') {
            throw new \Exception(
                "WARNING: Expected database connection 'mariadb_testing' but got '{$connection}'. ".
                "Database: {$database}"
            );
        }

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }
}
