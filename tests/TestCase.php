<?php

namespace Zaeem2396\SchemaLens\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Zaeem2396\SchemaLens\SchemaLensServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            SchemaLensServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Check if MySQL environment variables are set (e.g., in CI)
        $dbConnection = env('DB_CONNECTION', 'sqlite');

        if ($dbConnection === 'mysql') {
            // Use MySQL (for CI with MySQL service)
            $app['config']->set('database.default', 'testing');
            $app['config']->set('database.connections.testing', [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'testing'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]);
        } else {
            // Use SQLite in-memory for local testing
            // Note: MySQL tests require information_schema which SQLite doesn't have
            $app['config']->set('database.default', 'testing');
            $app['config']->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }

        // Schema Lens config
        $app['config']->set('schema-lens.export.row_limit', 1000);
        $app['config']->set('schema-lens.export.storage_path', 'app/schema-lens/exports');
        $app['config']->set('schema-lens.export.compress', false);
        $app['config']->set('schema-lens.output.format', 'cli');
        $app['config']->set('schema-lens.output.show_line_numbers', true);
    }

    /**
     * Get the path to test fixtures.
     */
    protected function getFixturePath(string $filename = ''): string
    {
        return __DIR__.'/Fixtures'.($filename ? '/'.$filename : '');
    }

    /**
     * Check if we're running on MySQL (required for information_schema tests).
     */
    protected function isMySQL(): bool
    {
        try {
            $driver = $this->app['db']->connection()->getDriverName();

            return in_array($driver, ['mysql', 'mariadb']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Skip test if not running on MySQL.
     * SchemaIntrospector requires MySQL's information_schema tables.
     */
    protected function skipIfNotMySQL(): void
    {
        if (! $this->isMySQL()) {
            $this->markTestSkipped(
                'This test requires MySQL. SchemaIntrospector uses information_schema which is MySQL-specific.'
            );
        }
    }
}
