<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Illuminate\Console\OutputStyle;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Zaeem2396\SchemaLens\Commands\SafeMigrateCommand;
use Zaeem2396\SchemaLens\Tests\TestCase;

/**
 * Unit tests for SafeMigrateCommand.
 *
 * Tests protected methods using reflection to verify
 * interactive confirmation logic without database dependencies.
 */
class SafeMigrateCommandTest extends TestCase
{
    protected SafeMigrateCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = $this->app->make(SafeMigrateCommand::class);
    }

    /** @test */
    public function command_has_all_required_options(): void
    {
        $definition = $this->command->getDefinition();

        $expectedOptions = [
            'force',
            'seed',
            'step',
            'pretend',
            'no-backup',
            'interactive',
        ];

        foreach ($expectedOptions as $option) {
            $this->assertTrue(
                $definition->hasOption($option),
                "Option --{$option} should be defined"
            );
        }
    }

    /** @test */
    public function command_signature_contains_migrate_safe(): void
    {
        $this->assertEquals('migrate:safe', $this->command->getName());
    }

    /** @test */
    public function command_has_proper_description(): void
    {
        $description = $this->command->getDescription();

        $this->assertNotEmpty($description);
        $this->assertStringContainsString('migration', strtolower($description));
    }

    /** @test */
    public function display_destructive_changes_method_exists(): void
    {
        $reflection = new ReflectionClass($this->command);

        $this->assertTrue($reflection->hasMethod('displayDestructiveChanges'));
    }

    /** @test */
    public function handle_interactive_confirmation_method_exists(): void
    {
        $reflection = new ReflectionClass($this->command);

        $this->assertTrue($reflection->hasMethod('handleInteractiveConfirmation'));
    }

    /** @test */
    public function ask_with_options_method_exists(): void
    {
        $reflection = new ReflectionClass($this->command);

        $this->assertTrue($reflection->hasMethod('askWithOptions'));
    }

    /** @test */
    public function run_selective_migrations_method_exists(): void
    {
        $reflection = new ReflectionClass($this->command);

        $this->assertTrue($reflection->hasMethod('runSelectiveMigrations'));
    }

    /** @test */
    public function handle_interactive_confirmation_returns_array(): void
    {
        $method = $this->getProtectedMethod('handleInteractiveConfirmation');

        // Get method return type
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    /** @test */
    public function run_selective_migrations_returns_int(): void
    {
        $method = $this->getProtectedMethod('runSelectiveMigrations');

        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('int', $returnType->getName());
    }

    /** @test */
    public function handle_interactive_confirmation_accepts_correct_parameters(): void
    {
        $method = $this->getProtectedMethod('handleInteractiveConfirmation');
        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('allDestructiveChanges', $parameters[0]->getName());
        $this->assertEquals('pendingMigrations', $parameters[1]->getName());
    }

    /** @test */
    public function display_destructive_changes_accepts_array_parameter(): void
    {
        $method = $this->getProtectedMethod('displayDestructiveChanges');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('changes', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()->getName());
    }

    /** @test */
    public function display_destructive_changes_returns_void(): void
    {
        $method = $this->getProtectedMethod('displayDestructiveChanges');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    /** @test */
    public function ask_with_options_accepts_migration_name_parameter(): void
    {
        $method = $this->getProtectedMethod('askWithOptions');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('migrationName', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    /** @test */
    public function ask_with_options_returns_string(): void
    {
        $method = $this->getProtectedMethod('askWithOptions');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    /** @test */
    public function analyze_migration_method_exists_and_returns_array(): void
    {
        $method = $this->getProtectedMethod('analyzeMigration');

        $this->assertNotNull($method);
        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    /** @test */
    public function get_pending_migrations_method_exists_and_returns_array(): void
    {
        $method = $this->getProtectedMethod('getPendingMigrations');

        $this->assertNotNull($method);
        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    /** @test */
    public function display_destructive_changes_handles_empty_array(): void
    {
        $method = $this->getProtectedMethod('displayDestructiveChanges');

        // Set up output
        $this->setupCommandOutput();

        // Call with empty array - should not throw
        $method->invoke($this->command, []);

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /** @test */
    public function display_destructive_changes_handles_critical_risk_level(): void
    {
        $method = $this->getProtectedMethod('displayDestructiveChanges');

        $this->setupCommandOutput();

        $changes = [
            [
                'operation' => [
                    'type' => 'table',
                    'action' => 'drop',
                ],
                'risk_level' => 'critical',
                'affected_tables' => ['users'],
                'affected_columns' => [],
            ],
        ];

        // Should not throw
        $method->invoke($this->command, $changes);

        $this->assertTrue(true);
    }

    /** @test */
    public function display_destructive_changes_handles_high_risk_level(): void
    {
        $method = $this->getProtectedMethod('displayDestructiveChanges');

        $this->setupCommandOutput();

        $changes = [
            [
                'operation' => [
                    'type' => 'column',
                    'action' => 'drop',
                ],
                'risk_level' => 'high',
                'affected_tables' => ['posts'],
                'affected_columns' => [
                    ['table' => 'posts', 'column' => 'content'],
                ],
            ],
        ];

        $method->invoke($this->command, $changes);

        $this->assertTrue(true);
    }

    /** @test */
    public function display_destructive_changes_handles_medium_risk_level(): void
    {
        $method = $this->getProtectedMethod('displayDestructiveChanges');

        $this->setupCommandOutput();

        $changes = [
            [
                'operation' => [
                    'type' => 'column',
                    'action' => 'modify',
                ],
                'risk_level' => 'medium',
                'affected_tables' => [],
                'affected_columns' => [
                    ['table' => 'users', 'column' => 'email'],
                ],
            ],
        ];

        $method->invoke($this->command, $changes);

        $this->assertTrue(true);
    }

    /** @test */
    public function display_destructive_changes_handles_missing_keys_gracefully(): void
    {
        $method = $this->getProtectedMethod('displayDestructiveChanges');

        $this->setupCommandOutput();

        // Minimal change data with some missing optional keys
        $changes = [
            [
                'operation' => [
                    'type' => 'index',
                    'action' => 'drop',
                ],
                'risk_level' => 'low',
                // No affected_tables or affected_columns
            ],
        ];

        $method->invoke($this->command, $changes);

        $this->assertTrue(true);
    }

    /** @test */
    public function run_selective_migrations_accepts_correct_parameters(): void
    {
        $method = $this->getProtectedMethod('runSelectiveMigrations');
        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('migrationFiles', $parameters[0]->getName());
        $this->assertEquals('options', $parameters[1]->getName());
    }

    // ====================================
    // Single Migration File Tests
    // ====================================

    /** @test */
    public function command_has_path_argument(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue(
            $definition->hasArgument('path'),
            'Command should have path argument'
        );
    }

    /** @test */
    public function path_argument_is_optional(): void
    {
        $definition = $this->command->getDefinition();
        $argument = $definition->getArgument('path');

        $this->assertFalse($argument->isRequired());
    }

    /** @test */
    public function resolve_single_migration_method_exists(): void
    {
        $reflection = new ReflectionClass($this->command);

        $this->assertTrue(
            $reflection->hasMethod('resolveSingleMigration'),
            'Command should have resolveSingleMigration method'
        );
    }

    /** @test */
    public function resolve_single_migration_returns_nullable_array(): void
    {
        $method = $this->getProtectedMethod('resolveSingleMigration');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    /** @test */
    public function resolve_absolute_path_method_exists(): void
    {
        $reflection = new ReflectionClass($this->command);

        $this->assertTrue(
            $reflection->hasMethod('resolveAbsolutePath'),
            'Command should have resolveAbsolutePath method'
        );
    }

    /** @test */
    public function resolve_absolute_path_returns_string(): void
    {
        $method = $this->getProtectedMethod('resolveAbsolutePath');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    /** @test */
    public function resolve_absolute_path_handles_relative_path(): void
    {
        $method = $this->getProtectedMethod('resolveAbsolutePath');

        $relativePath = 'database/migrations/test.php';
        $result = $method->invoke($this->command, $relativePath);

        $this->assertStringEndsWith($relativePath, $result);
        $this->assertStringStartsWith('/', $result);
    }

    /** @test */
    public function resolve_absolute_path_handles_absolute_path(): void
    {
        $method = $this->getProtectedMethod('resolveAbsolutePath');

        $absolutePath = '/var/www/app/database/migrations/test.php';
        $result = $method->invoke($this->command, $absolutePath);

        $this->assertEquals($absolutePath, $result);
    }

    /** @test */
    public function get_migration_name_method_exists(): void
    {
        $reflection = new ReflectionClass($this->command);

        $this->assertTrue(
            $reflection->hasMethod('getMigrationName'),
            'Command should have getMigrationName method'
        );
    }

    /** @test */
    public function get_migration_name_extracts_name_correctly(): void
    {
        $method = $this->getProtectedMethod('getMigrationName');

        $path = '/var/www/app/database/migrations/2024_01_15_123456_create_users_table.php';
        $result = $method->invoke($this->command, $path);

        $this->assertEquals('2024_01_15_123456_create_users_table', $result);
    }

    /** @test */
    public function get_migration_name_removes_php_extension(): void
    {
        $method = $this->getProtectedMethod('getMigrationName');

        $path = 'database/migrations/test_migration.php';
        $result = $method->invoke($this->command, $path);

        $this->assertStringNotContainsString('.php', $result);
    }

    /** @test */
    public function get_relative_migration_path_method_exists(): void
    {
        $reflection = new ReflectionClass($this->command);

        $this->assertTrue(
            $reflection->hasMethod('getRelativeMigrationPath'),
            'Command should have getRelativeMigrationPath method'
        );
    }

    /** @test */
    public function get_relative_migration_path_returns_string(): void
    {
        $method = $this->getProtectedMethod('getRelativeMigrationPath');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    /**
     * Get a protected method from the command for testing.
     */
    protected function getProtectedMethod(string $methodName): ReflectionMethod
    {
        $reflection = new ReflectionClass($this->command);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * Set up command with output for testing output methods.
     */
    protected function setupCommandOutput(): void
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput;
        $outputStyle = new OutputStyle($input, $output);

        $this->command->setOutput($outputStyle);
    }
}
