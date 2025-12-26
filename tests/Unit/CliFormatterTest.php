<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Formatters\CliFormatter;
use Zaeem2396\SchemaLens\Tests\TestCase;

class CliFormatterTest extends TestCase
{
    protected CliFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new CliFormatter;
    }

    /** @test */
    public function it_formats_empty_diff(): void
    {
        $diff = [
            'tables' => [],
            'columns' => [],
            'indexes' => [],
            'foreign_keys' => [],
            'engine' => [],
            'charset' => [],
            'collation' => [],
        ];

        $output = $this->formatter->format($diff);

        $this->assertIsString($output);
        $this->assertStringContainsString('Schema Lens', $output);
        $this->assertStringContainsString('SUMMARY', $output);
    }

    /** @test */
    public function it_includes_header(): void
    {
        $diff = $this->getEmptyDiff();
        $output = $this->formatter->format($diff);

        $this->assertStringContainsString('Schema Lens - Migration Preview Report', $output);
    }

    /** @test */
    public function it_shows_summary_counts(): void
    {
        $diff = [
            'tables' => [['action' => 'create', 'table' => 'users', 'line' => 10, 'status' => 'new', 'message' => 'test']],
            'columns' => [
                ['action' => 'add', 'table' => 'users', 'column' => 'name', 'line' => 15, 'status' => 'new', 'message' => 'test'],
                ['action' => 'add', 'table' => 'users', 'column' => 'email', 'line' => 16, 'status' => 'new', 'message' => 'test'],
            ],
            'indexes' => [],
            'foreign_keys' => [],
            'engine' => [],
            'charset' => [],
            'collation' => [],
        ];

        $output = $this->formatter->format($diff);

        $this->assertStringContainsString('Tables:', $output);
        $this->assertStringContainsString('Columns:', $output);
    }

    /** @test */
    public function it_shows_destructive_changes_warning(): void
    {
        $diff = $this->getEmptyDiff();
        $destructiveChanges = [
            [
                'operation' => ['type' => 'column', 'action' => 'drop', 'line' => 20],
                'risk_level' => 'high',
                'affected_tables' => ['users'],
                'affected_columns' => [['table' => 'users', 'column' => 'email']],
            ],
        ];

        $output = $this->formatter->format($diff, $destructiveChanges);

        $this->assertStringContainsString('DESTRUCTIVE CHANGES DETECTED', $output);
        $this->assertStringContainsString('HIGH', $output);
        $this->assertStringContainsString('column::drop', $output);
    }

    /** @test */
    public function it_shows_detailed_changes(): void
    {
        $diff = [
            'tables' => [
                ['action' => 'create', 'table' => 'users', 'line' => 10, 'status' => 'new', 'message' => "Will create new table 'users'"],
            ],
            'columns' => [],
            'indexes' => [],
            'foreign_keys' => [],
            'engine' => [],
            'charset' => [],
            'collation' => [],
        ];

        $output = $this->formatter->format($diff);

        $this->assertStringContainsString('DETAILED CHANGES', $output);
        $this->assertStringContainsString('TABLES', $output);
        $this->assertStringContainsString("Will create new table 'users'", $output);
    }

    /** @test */
    public function it_shows_rollback_simulation(): void
    {
        $diff = $this->getEmptyDiff();
        $rollback = [
            'has_rollback' => true,
            'operations' => [],
            'dependencies' => [],
            'sql_preview' => [],
            'impact' => [
                'risk_level' => 'high',
                'tables_affected' => ['users'],
                'columns_affected' => [['table' => 'users', 'column' => 'email']],
                'indexes_affected' => [],
                'foreign_keys_affected' => [],
            ],
        ];

        $output = $this->formatter->format($diff, [], $rollback);

        $this->assertStringContainsString('ROLLBACK SIMULATION', $output);
        $this->assertStringContainsString('Risk Level', $output);
    }

    /** @test */
    public function it_shows_export_information(): void
    {
        $diff = $this->getEmptyDiff();
        $exports = [
            [
                'table' => 'users',
                'export_path' => '/path/to/exports',
                'version' => '0001',
                'files' => [
                    'json' => '/path/to/exports/users.json',
                    'csv' => '/path/to/exports/users.csv',
                ],
            ],
        ];

        $output = $this->formatter->format($diff, [], [], $exports);

        $this->assertStringContainsString('DATA EXPORTS', $output);
        $this->assertStringContainsString('users', $output);
        $this->assertStringContainsString('/path/to/exports', $output);
    }

    /** @test */
    public function it_shows_correct_status_icons(): void
    {
        $diff = [
            'tables' => [],
            'columns' => [
                ['action' => 'add', 'table' => 'users', 'column' => 'name', 'line' => 15, 'status' => 'new', 'message' => 'Adding column'],
                ['action' => 'drop', 'table' => 'users', 'column' => 'old_col', 'line' => 20, 'status' => 'destructive', 'message' => 'Dropping column'],
            ],
            'indexes' => [],
            'foreign_keys' => [],
            'engine' => [],
            'charset' => [],
            'collation' => [],
        ];

        $output = $this->formatter->format($diff);

        // Check for status icons
        $this->assertStringContainsString('➕', $output); // new
        $this->assertStringContainsString('🔴', $output); // destructive
    }

    /** @test */
    public function it_shows_line_numbers(): void
    {
        $diff = [
            'tables' => [
                ['action' => 'create', 'table' => 'users', 'line' => 42, 'status' => 'new', 'message' => 'Creating table'],
            ],
            'columns' => [],
            'indexes' => [],
            'foreign_keys' => [],
            'engine' => [],
            'charset' => [],
            'collation' => [],
        ];

        $output = $this->formatter->format($diff);

        $this->assertStringContainsString('[Line 42]', $output);
    }

    /** @test */
    public function it_shows_affected_tables_in_destructive_changes(): void
    {
        $diff = $this->getEmptyDiff();
        $destructiveChanges = [
            [
                'operation' => ['type' => 'table', 'action' => 'drop', 'line' => 10],
                'risk_level' => 'critical',
                'affected_tables' => ['users', 'posts'],
                'affected_columns' => [],
            ],
        ];

        $output = $this->formatter->format($diff, $destructiveChanges);

        $this->assertStringContainsString('Tables:', $output);
        $this->assertStringContainsString('users', $output);
        $this->assertStringContainsString('posts', $output);
    }

    /** @test */
    public function it_shows_dependency_warnings_in_rollback(): void
    {
        $diff = $this->getEmptyDiff();
        $rollback = [
            'has_rollback' => true,
            'operations' => [],
            'dependencies' => [
                [
                    'type' => 'foreign_key_drop',
                    'table' => 'posts',
                    'risk' => 'medium',
                    'message' => 'Dropping foreign key may break referential integrity',
                ],
            ],
            'sql_preview' => [],
            'impact' => [
                'risk_level' => 'medium',
                'tables_affected' => [],
                'columns_affected' => [],
                'indexes_affected' => [],
                'foreign_keys_affected' => [],
            ],
        ];

        $output = $this->formatter->format($diff, [], $rollback);

        $this->assertStringContainsString('Dependency Warnings', $output);
        $this->assertStringContainsString('referential integrity', $output);
    }

    /** @test */
    public function it_shows_sql_preview_in_rollback(): void
    {
        $diff = $this->getEmptyDiff();
        $rollback = [
            'has_rollback' => true,
            'operations' => [],
            'dependencies' => [],
            'sql_preview' => [
                [
                    'line' => 25,
                    'type' => 'table',
                    'action' => 'drop',
                    'sql' => 'DROP TABLE IF EXISTS `users`;',
                ],
            ],
            'impact' => [
                'risk_level' => 'critical',
                'tables_affected' => ['users'],
                'columns_affected' => [],
                'indexes_affected' => [],
                'foreign_keys_affected' => [],
            ],
        ];

        $output = $this->formatter->format($diff, [], $rollback);

        $this->assertStringContainsString('SQL Preview', $output);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `users`', $output);
    }

    protected function getEmptyDiff(): array
    {
        return [
            'tables' => [],
            'columns' => [],
            'indexes' => [],
            'foreign_keys' => [],
            'engine' => [],
            'charset' => [],
            'collation' => [],
        ];
    }
}
