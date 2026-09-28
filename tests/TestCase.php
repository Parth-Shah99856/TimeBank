<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application and enforces database isolation BEFORE any traits or migrations run.
     *
     * Layer 1 — Early putenv():
     *   PHP's putenv() ensures process environment variables are set.
     *
     * Layer 2 — Pre-boot & Post-boot safety assertion in createApplication():
     *   Verifies that the resolved database connection is 'sqlite' and the database is ':memory:'.
     *   If Laravel configuration was cached (e.g. via php artisan config:cache) and points
     *   to a real file database like database.sqlite, this guard immediately throws a RuntimeException
     *   BEFORE setUpTraits() or RefreshDatabase can execute migrate:fresh against real data.
     */
    public function createApplication()
    {
        // Layer 1: set process environment before bootstrapping
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        putenv('DB_URL=');

        $app = parent::createApplication();

        // Layer 2: Guard before any test or trait can run
        $this->enforceTestDatabaseIsolation($app);

        return $app;
    }


    /**
     * Abort immediately if the resolved database is not the in-memory test database.
     */
    protected function enforceTestDatabaseIsolation($app = null): void
    {
        $app = $app ?? $this->app;

        if (! $app) {
            return;
        }

        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "\n\n" .
                "═══════════════════════════════════════════════════════════\n" .
                "  ⛔  TEST ABORTED — REAL DATABASE DETECTED\n" .
                "═══════════════════════════════════════════════════════════\n" .
                "  PHPUnit resolved a non-in-memory database:\n" .
                "  Default Connection: {$connection}\n" .
                "  Resolved Database:  {$database}\n\n" .
                "  This test run has been aborted before any migrations or\n" .
                "  database operations could execute against real data.\n" .
                "  Clear config cache with: php artisan config:clear\n" .
                "═══════════════════════════════════════════════════════════\n\n"
            );
        }
    }
}
