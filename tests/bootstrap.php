<?php

/*
|--------------------------------------------------------------------------
| Test Bootstrap File
|--------------------------------------------------------------------------
|
| This file is loaded before running tests to ensure that the testing
| environment is properly configured. It forces Laravel to use the
| .env.testing file instead of .env.
|
*/

// Set memory limit for tests
ini_set('memory_limit', '512M');

// Load Composer's autoloader first
require __DIR__.'/../vendor/autoload.php';

// Load .env.testing file manually using Dotenv
// Use overload() to ensure these variables take precedence
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../', '.env.testing');
$dotenv->safeLoad(); // Load but don't overwrite existing vars

// Now override critical variables to ensure they're set correctly
$_ENV['DB_CONNECTION'] = 'mariadb_testing';
$_SERVER['DB_CONNECTION'] = 'mariadb_testing';
putenv('DB_CONNECTION=mariadb_testing');

$_ENV['DB_DATABASE'] = 'subwayapp_test';
$_SERVER['DB_DATABASE'] = 'subwayapp_test';
putenv('DB_DATABASE=subwayapp_test');

// Ensure APP_ENV is set to testing
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';
putenv('APP_ENV=testing');
