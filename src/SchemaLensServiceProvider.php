<?php

namespace Zaeem2396\SchemaLens;

use Illuminate\Support\ServiceProvider;
use Zaeem2396\SchemaLens\Commands\PreviewMigrationCommand;
use Zaeem2396\SchemaLens\Commands\SafeMigrateCommand;

/**
 * Schema Lens Service Provider
 *
 * Note: The linter may show "Undefined type" for ServiceProvider, but this is a false positive.
 * The Illuminate\Support\ServiceProvider class is available at runtime when installed in Laravel.
 * DO NOT change this to extend SchemaLensServiceProvider (itself) - that would cause a fatal error.
 */
class SchemaLensServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/schema-lens.php',
            'schema-lens'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PreviewMigrationCommand::class,
                SafeMigrateCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/schema-lens.php' => config_path('schema-lens.php'),
        ], 'schema-lens-config');
    }
}
