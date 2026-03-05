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
            ->expectsOutputToContain('not found');
    }
}
