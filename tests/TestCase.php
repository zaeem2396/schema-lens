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
        // Use SQLite in-memory for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

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
}
