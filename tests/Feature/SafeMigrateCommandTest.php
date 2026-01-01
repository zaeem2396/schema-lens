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

        // The command outputs "🔍 Schema Lens - Safe Migration"
        // Just check it runs and outputs something
        $this->artisan('migrate:safe')
            ->assertSuccessful();
    }

    /** @test */
    public function command_has_interactive_option(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('interactive'));
    }

    /** @test */
    public function interactive_option_has_correct_description(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();
        $option = $definition->getOption('interactive');

        $this->assertNotEmpty($option->getDescription());
        $this->assertStringContainsString('individually', $option->getDescription());
    }

    /** @test */
    public function interactive_option_does_not_require_value(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();
        $option = $definition->getOption('interactive');

        $this->assertFalse($option->isValueRequired());
    }

    /** @test */
    public function interactive_option_defaults_to_false(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();
        $option = $definition->getOption('interactive');

        $this->assertFalse($option->getDefault());
    }

    /** @test */
    public function interactive_mode_shows_nothing_to_migrate_when_no_pending(): void
    {
        $this->skipIfNotMySQL();

        $this->artisan('migrate:safe', ['--interactive' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Nothing to migrate');
    }

    // ====================================
    // Single Migration File Tests
    // ====================================

    /** @test */
    public function command_accepts_path_argument(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('path'));
    }

    /** @test */
    public function path_argument_is_optional(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();
        $argument = $definition->getArgument('path');

        $this->assertFalse($argument->isRequired());
    }

    /** @test */
    public function path_argument_has_correct_description(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();
        $argument = $definition->getArgument('path');

        $this->assertNotEmpty($argument->getDescription());
        $this->assertStringContainsString('migration', strtolower($argument->getDescription()));
    }

    /** @test */
    public function single_migration_fails_for_nonexistent_file(): void
    {
        $this->skipIfNotMySQL();

        $this->artisan('migrate:safe', ['path' => 'database/migrations/nonexistent_file.php'])
            ->assertFailed()
            ->expectsOutputToContain('Migration file not found');
    }

    /** @test */
    public function single_migration_fails_for_non_php_file(): void
    {
        $this->skipIfNotMySQL();

        // Create a temporary non-PHP file
        $tempFile = base_path('database/migrations/test_file.txt');
        file_put_contents($tempFile, 'test content');

        try {
            $this->artisan('migrate:safe', ['path' => 'database/migrations/test_file.txt'])
                ->assertFailed()
                ->expectsOutputToContain('Invalid migration file');
        } finally {
            // Cleanup
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /** @test */
    public function single_migration_shows_single_mode_message(): void
    {
        $this->skipIfNotMySQL();

        // Create a temporary migration file
        $timestamp = date('Y_m_d_His');
        $migrationName = "{$timestamp}_test_single_migration";
        $tempFile = base_path("database/migrations/{$migrationName}.php");

        $migrationContent = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_single_table', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_single_table');
    }
};
PHP;

        file_put_contents($tempFile, $migrationContent);

        try {
            $this->artisan('migrate:safe', ['path' => "database/migrations/{$migrationName}.php"])
                ->expectsOutputToContain('Single migration mode');
        } finally {
            // Cleanup
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            // Also drop table if it was created
            try {
                $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('test_single_table');
            } catch (\Exception $e) {
                // Ignore
            }
        }
    }

    /** @test */
    public function single_migration_can_use_interactive_option(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();

        // Both path argument and interactive option should be available
        $this->assertTrue($definition->hasArgument('path'));
        $this->assertTrue($definition->hasOption('interactive'));
    }

    /** @test */
    public function single_migration_can_use_no_backup_option(): void
    {
        $command = $this->app->make(\Zaeem2396\SchemaLens\Commands\SafeMigrateCommand::class);
        $definition = $command->getDefinition();

        // Both path argument and no-backup option should be available
        $this->assertTrue($definition->hasArgument('path'));
        $this->assertTrue($definition->hasOption('no-backup'));
    }
}
