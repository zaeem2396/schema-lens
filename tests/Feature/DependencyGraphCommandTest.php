<?php

namespace Zaeem2396\SchemaLens\Tests\Feature;

use Zaeem2396\SchemaLens\Tests\TestCase;

class DependencyGraphCommandTest extends TestCase
{
    /** @test */
    public function it_runs_successfully_with_fixture_path(): void
    {
        $this->artisan('schema:graph', [
            '--path' => $this->getFixturePath(),
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Migration Dependency Graph');
    }

    /** @test */
    public function it_outputs_json_when_format_json(): void
    {
        $this->artisan('schema:graph', [
            '--path' => $this->getFixturePath(),
            '--format' => 'json',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('"migrations"');
    }

    /** @test */
    public function it_fails_gracefully_for_missing_path(): void
    {
        $this->artisan('schema:graph', [
            '--path' => __DIR__.'/nonexistent_migrations_dir',
        ])
            ->assertFailed()
            ->expectsOutputToContain('not found')
            ->expectsOutputToContain('Check that the directory');
    }

    /** @test */
    public function it_fails_when_path_given_and_no_migration_files(): void
    {
        $emptyPath = sys_get_temp_dir().'/schema-lens-empty-'.uniqid();
        mkdir($emptyPath, 0755, true);
        try {
            $this->artisan('schema:graph', [
                '--path' => $emptyPath,
            ])
                ->assertFailed()
                ->expectsOutputToContain('No migration files found')
                ->expectsOutputToContain('Add .php migration files');
        } finally {
            rmdir($emptyPath);
        }
    }
}
