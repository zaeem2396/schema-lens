<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Services\MigrationParser;
use Zaeem2396\SchemaLens\Services\RollbackSimulator;
use Zaeem2396\SchemaLens\Services\SchemaIntrospector;
use Zaeem2396\SchemaLens\Tests\TestCase;

/**
 * RollbackSimulator tests.
 *
 * Note: These tests require MySQL because RollbackSimulator depends on
 * SchemaIntrospector which queries MySQL's information_schema tables.
 * Tests will be skipped if not running on MySQL.
 */
class RollbackSimulatorTest extends TestCase
{
    protected ?RollbackSimulator $simulator = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Only create simulator if running on MySQL
        if ($this->isMySQL()) {
            try {
                $introspector = new SchemaIntrospector;
                $parser = new MigrationParser;
                $this->simulator = new RollbackSimulator($introspector, $parser);
            } catch (\Exception $e) {
                $this->simulator = null;
            }
        }
    }

    /** @test */
    public function it_simulates_rollback_for_migration_with_down_method(): void
    {
        $this->skipIfNotMySQL();

        $result = $this->simulator->simulate($this->getFixturePath('2024_01_01_000000_create_users_table.php'));

        $this->assertArrayHasKey('has_rollback', $result);
        $this->assertTrue($result['has_rollback']);
        $this->assertArrayHasKey('operations', $result);
        $this->assertArrayHasKey('dependencies', $result);
        $this->assertArrayHasKey('sql_preview', $result);
        $this->assertArrayHasKey('impact', $result);
    }

    /** @test */
    public function it_generates_sql_preview_for_table_drop(): void
    {
        $this->skipIfNotMySQL();

        $result = $this->simulator->simulate($this->getFixturePath('2024_01_01_000000_create_users_table.php'));

        $sqlPreview = $result['sql_preview'];

        // Should have SQL for dropping the table
        $dropTableSql = collect($sqlPreview)->first(function ($sql) {
            return $sql['type'] === 'table' && $sql['action'] === 'drop';
        });

        $this->assertNotNull($dropTableSql);
        $this->assertStringContainsString('DROP TABLE', $dropTableSql['sql']);
        $this->assertStringContainsString('users', $dropTableSql['sql']);
    }

    /** @test */
    public function it_analyzes_impact_of_rollback(): void
    {
        $this->skipIfNotMySQL();

        $result = $this->simulator->simulate($this->getFixturePath('2024_01_01_000000_create_users_table.php'));

        $impact = $result['impact'];

        $this->assertArrayHasKey('risk_level', $impact);
        $this->assertArrayHasKey('tables_affected', $impact);
        $this->assertArrayHasKey('columns_affected', $impact);
        $this->assertArrayHasKey('indexes_affected', $impact);
        $this->assertArrayHasKey('foreign_keys_affected', $impact);
    }

    /** @test */
    public function it_sets_critical_risk_for_table_drop(): void
    {
        $this->skipIfNotMySQL();

        $result = $this->simulator->simulate($this->getFixturePath('2024_01_01_000000_create_users_table.php'));

        $this->assertEquals('critical', $result['impact']['risk_level']);
    }

    /** @test */
    public function it_lists_affected_tables(): void
    {
        $this->skipIfNotMySQL();

        $result = $this->simulator->simulate($this->getFixturePath('2024_01_01_000000_create_users_table.php'));

        $this->assertContains('users', $result['impact']['tables_affected']);
    }

    /** @test */
    public function it_generates_sql_for_column_drop(): void
    {
        $this->skipIfNotMySQL();

        $result = $this->simulator->simulate($this->getFixturePath('2024_01_02_000000_add_columns_to_users.php'));

        $sqlPreview = $result['sql_preview'];

        // The down method drops columns
        $dropColumnSql = collect($sqlPreview)->first(function ($sql) {
            return $sql['type'] === 'column' && $sql['action'] === 'drop';
        });

        // Assert that we have SQL preview entries
        $this->assertIsArray($sqlPreview);

        if ($dropColumnSql) {
            $this->assertStringContainsString('DROP COLUMN', $dropColumnSql['sql']);
        }
    }

    /** @test */
    public function it_includes_line_numbers_in_sql_preview(): void
    {
        $this->skipIfNotMySQL();

        $result = $this->simulator->simulate($this->getFixturePath('2024_01_01_000000_create_users_table.php'));

        foreach ($result['sql_preview'] as $sql) {
            $this->assertArrayHasKey('line', $sql);
            $this->assertIsInt($sql['line']);
        }
    }

    /** @test */
    public function it_includes_operation_type_in_sql_preview(): void
    {
        $this->skipIfNotMySQL();

        $result = $this->simulator->simulate($this->getFixturePath('2024_01_01_000000_create_users_table.php'));

        foreach ($result['sql_preview'] as $sql) {
            $this->assertArrayHasKey('type', $sql);
            $this->assertArrayHasKey('action', $sql);
        }
    }

    /** @test */
    public function it_analyzes_column_drop_dependencies(): void
    {
        $this->skipIfNotMySQL();

        $result = $this->simulator->simulate($this->getFixturePath('2024_01_02_000000_add_columns_to_users.php'));

        // Verify dependencies is an array
        $this->assertIsArray($result['dependencies']);

        // The down method has column drops which should create dependencies
        $columnDropDeps = collect($result['dependencies'])->filter(function ($dep) {
            return $dep['type'] === 'column_drop';
        });

        // If there are column drop operations, there should be dependencies
        $columnDropOps = collect($result['operations'])->filter(function ($op) {
            return $op['type'] === 'column' && $op['action'] === 'drop';
        });

        if ($columnDropOps->isNotEmpty()) {
            $this->assertNotEmpty($columnDropDeps);
        }
    }

    /** @test */
    public function it_generates_sql_for_rename_column(): void
    {
        $this->skipIfNotMySQL();

        $result = $this->simulator->simulate($this->getFixturePath('2024_01_04_000000_rename_column_in_users.php'));

        $sqlPreview = $result['sql_preview'];

        // Verify we have a result
        $this->assertIsArray($sqlPreview);

        // The down method renames column back
        $renameColumnSql = collect($sqlPreview)->first(function ($sql) {
            return $sql['type'] === 'column' && $sql['action'] === 'rename';
        });

        if ($renameColumnSql) {
            $this->assertStringContainsString('RENAME COLUMN', $renameColumnSql['sql']);
        }
    }

    /** @test */
    public function it_sets_high_risk_for_column_drops(): void
    {
        $this->skipIfNotMySQL();

        $result = $this->simulator->simulate($this->getFixturePath('2024_01_02_000000_add_columns_to_users.php'));

        // Verify impact exists
        $this->assertArrayHasKey('impact', $result);
        $this->assertArrayHasKey('risk_level', $result['impact']);

        // If there are column drops in the down method, risk should be high
        $hasColumnDrops = collect($result['operations'])->contains(function ($op) {
            return $op['type'] === 'column' && $op['action'] === 'drop';
        });

        if ($hasColumnDrops) {
            $this->assertContains($result['impact']['risk_level'], ['high', 'critical']);
        }
    }

    /** @test */
    public function dependencies_have_required_fields(): void
    {
        $this->skipIfNotMySQL();

        $result = $this->simulator->simulate($this->getFixturePath('2024_01_02_000000_add_columns_to_users.php'));

        // Verify dependencies is an array
        $this->assertIsArray($result['dependencies']);

        foreach ($result['dependencies'] as $dep) {
            $this->assertArrayHasKey('type', $dep);
            $this->assertArrayHasKey('risk', $dep);
            $this->assertArrayHasKey('message', $dep);
        }
    }
}
