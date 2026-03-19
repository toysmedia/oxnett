<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * In the SQLite test environment (DB_CONNECTION=sqlite) we configure:
     *
     *   testing.sqlite        → 'sqlite' (default) + 'tenant' connections
     *   testing_system.sqlite → 'mysql' connection (system-level tables)
     *
     * Both files are wiped and recreated each time the application is booted,
     * which keeps tests idempotent between runs.
     *
     * Note: RefreshDatabase handles transaction rollbacks between individual tests;
     * we only need to reset the files when the test PROCESS starts.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if ($app['config']->get('database.default') === 'sqlite') {
            $dir = storage_path('framework/testing');

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $tenantDb = $dir . '/testing.sqlite';
            $systemDb = $dir . '/testing_system.sqlite';

            // Wipe and recreate so each php artisan test run starts clean.
            // RefreshDatabase wraps individual tests in transactions, so this
            // only runs once per process, not once per test.
            file_put_contents($tenantDb, '');
            file_put_contents($systemDb, '');

            $sqliteBase = $app['config']->get('database.connections.sqlite');

            // Default (tenant) connection
            $app['config']->set('database.connections.sqlite.database', $tenantDb);

            // 'tenant' connection → same file as the default (tenant) DB
            $app['config']->set('database.connections.tenant', array_merge(
                $sqliteBase,
                ['database' => $tenantDb]
            ));

            // 'mysql' connection → separate system DB file
            $app['config']->set('database.connections.mysql', array_merge(
                $sqliteBase,
                ['database' => $systemDb]
            ));
        }

        return $app;
    }
}
