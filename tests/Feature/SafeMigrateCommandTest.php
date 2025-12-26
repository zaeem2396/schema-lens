<?php

namespace Zaeem2396\SchemaLens\Tests\Feature;

use Zaeem2396\SchemaLens\Tests\TestCase;

/**
 * SafeMigrateCommand feature tests.
 *
 * Note: These tests require MySQL because the command uses SchemaIntrospector
 * which queries MySQL's information_schema tables.
 */
class SafeMigrateCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Only set up database tables if running on MySQL
        if ($this->isMySQL()) {
            try {
                $this->app['db']->connection()->getSchemaBuilder()->create('migrations', function ($table) {
                    $table->id();
                    $table->string('migration');
                    $table->integer('batch');
                });
            } catch (\Exception $e) {
                // Table might already exist
            }
        }
    }

    protected function tearDown(): void
    {
        if ($this->isMySQL()) {
            try {
                $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('migrations');
                $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('users');
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }

        parent::tearDown();
    }

    /** @test */
    public function it_shows_nothing_to_migrate_when_no_pending_migrations(): void
    {
        $this->skipIfNotMySQL();

        // The command should check for pending migrations
        // Since we don't have any migrations set up in the standard location,
        // this tests the basic command execution
        $this->artisan('migrate:safe')
            ->assertSuccessful()
            ->expectsOutputToContain('Nothing to migrate');
    }

    /** @test */
    public function command_has_correct_signature(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);

        $this->assertStringContainsString('migrate:safe', $command->getName());
    }

    /** @test */
    public function command_has_force_option(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('force'));
    }

    /** @test */
    public function command_has_seed_option(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('seed'));
    }

    /** @test */
    public function command_has_step_option(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('step'));
    }

    /** @test */
    public function command_has_pretend_option(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('pretend'));
    }

    /** @test */
    public function command_has_no_backup_option(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('no-backup'));
    }

    /** @test */
    public function command_shows_schema_lens_header(): void
    {
        $this->skipIfNotMySQL();

        $this->artisan('migrate:safe')
            ->expectsOutputToContain('Schema Lens')
            ->expectsOutputToContain('Safe Migration');
    }
}
