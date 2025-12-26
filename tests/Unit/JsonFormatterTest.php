<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Formatters\JsonFormatter;
use Zaeem2396\SchemaLens\Tests\TestCase;

class JsonFormatterTest extends TestCase
{
    protected JsonFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new JsonFormatter;
    }

    /** @test */
    public function it_returns_valid_json(): void
    {
        $diff = $this->getEmptyDiff();
        $output = $this->formatter->format($diff);

        $this->assertIsString($output);
        $this->assertJson($output);
    }

    /** @test */
    public function it_includes_timestamp(): void
    {
        $diff = $this->getEmptyDiff();
        $output = $this->formatter->format($diff);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('timestamp', $data);
        $this->assertNotEmpty($data['timestamp']);
    }

    /** @test */
    public function it_includes_summary(): void
    {
        $diff = $this->getEmptyDiff();
        $output = $this->formatter->format($diff);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('tables', $data['summary']);
        $this->assertArrayHasKey('columns', $data['summary']);
        $this->assertArrayHasKey('indexes', $data['summary']);
        $this->assertArrayHasKey('foreign_keys', $data['summary']);
        $this->assertArrayHasKey('destructive_changes_count', $data['summary']);
        $this->assertArrayHasKey('has_destructive_changes', $data['summary']);
    }

    /** @test */
    public function it_counts_operations_in_summary(): void
    {
        $diff = [
            'tables' => [['action' => 'create', 'table' => 'users']],
            'columns' => [
                ['action' => 'add', 'column' => 'name'],
                ['action' => 'add', 'column' => 'email'],
            ],
            'indexes' => [['action' => 'add', 'name' => 'idx_email']],
            'foreign_keys' => [],
            'engine' => [],
            'charset' => [],
            'collation' => [],
        ];

        $output = $this->formatter->format($diff);
        $data = json_decode($output, true);

        $this->assertEquals(1, $data['summary']['tables']);
        $this->assertEquals(2, $data['summary']['columns']);
        $this->assertEquals(1, $data['summary']['indexes']);
        $this->assertEquals(0, $data['summary']['foreign_keys']);
    }

    /** @test */
    public function it_includes_diff_data(): void
    {
        $diff = [
            'tables' => [['action' => 'create', 'table' => 'users', 'line' => 10]],
            'columns' => [],
            'indexes' => [],
            'foreign_keys' => [],
            'engine' => [],
            'charset' => [],
            'collation' => [],
        ];

        $output = $this->formatter->format($diff);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('diff', $data);
        $this->assertEquals($diff, $data['diff']);
    }

    /** @test */
    public function it_includes_destructive_changes(): void
    {
        $diff = $this->getEmptyDiff();
        $destructiveChanges = [
            [
                'operation' => ['type' => 'column', 'action' => 'drop'],
                'risk_level' => 'high',
                'affected_tables' => ['users'],
                'affected_columns' => [['table' => 'users', 'column' => 'email']],
            ],
        ];

        $output = $this->formatter->format($diff, $destructiveChanges);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('destructive_changes', $data);
        $this->assertCount(1, $data['destructive_changes']);
        $this->assertEquals('high', $data['destructive_changes'][0]['risk_level']);
    }

    /** @test */
    public function it_sets_has_destructive_changes_correctly(): void
    {
        $diff = $this->getEmptyDiff();

        // Without destructive changes
        $output = $this->formatter->format($diff, []);
        $data = json_decode($output, true);
        $this->assertFalse($data['summary']['has_destructive_changes']);
        $this->assertEquals(0, $data['summary']['destructive_changes_count']);

        // With destructive changes
        $destructiveChanges = [
            ['operation' => ['type' => 'column', 'action' => 'drop'], 'risk_level' => 'high', 'affected_tables' => [], 'affected_columns' => []],
        ];
        $output = $this->formatter->format($diff, $destructiveChanges);
        $data = json_decode($output, true);
        $this->assertTrue($data['summary']['has_destructive_changes']);
        $this->assertEquals(1, $data['summary']['destructive_changes_count']);
    }

    /** @test */
    public function it_includes_rollback_data(): void
    {
        $diff = $this->getEmptyDiff();
        $rollback = [
            'has_rollback' => true,
            'operations' => [],
            'dependencies' => [],
            'sql_preview' => [],
            'impact' => ['risk_level' => 'low'],
        ];

        $output = $this->formatter->format($diff, [], $rollback);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('rollback', $data);
        $this->assertTrue($data['rollback']['has_rollback']);
    }

    /** @test */
    public function it_includes_exports_data(): void
    {
        $diff = $this->getEmptyDiff();
        $exports = [
            [
                'table' => 'users',
                'export_path' => '/path/to/exports',
                'version' => '0001',
                'files' => ['json' => '/path/to/exports/users.json'],
            ],
        ];

        $output = $this->formatter->format($diff, [], [], $exports);
        $data = json_decode($output, true);

        $this->assertArrayHasKey('exports', $data);
        $this->assertCount(1, $data['exports']);
        $this->assertEquals('users', $data['exports'][0]['table']);
    }

    /** @test */
    public function it_uses_pretty_print(): void
    {
        $diff = $this->getEmptyDiff();
        $output = $this->formatter->format($diff);

        // Pretty printed JSON has newlines and indentation
        $this->assertStringContainsString("\n", $output);
        $this->assertStringContainsString('    ', $output);
    }

    /** @test */
    public function it_does_not_escape_slashes(): void
    {
        $diff = $this->getEmptyDiff();
        $exports = [
            [
                'table' => 'users',
                'export_path' => '/path/to/exports',
                'version' => '0001',
                'files' => ['json' => '/path/to/exports/users.json'],
            ],
        ];

        $output = $this->formatter->format($diff, [], [], $exports);

        // Slashes should not be escaped
        $this->assertStringContainsString('/path/to/exports', $output);
        $this->assertStringNotContainsString('\\/path', $output);
    }

    /** @test */
    public function json_output_can_be_decoded_and_used(): void
    {
        $diff = [
            'tables' => [['action' => 'create', 'table' => 'products']],
            'columns' => [
                ['action' => 'add', 'table' => 'products', 'column' => 'name'],
                ['action' => 'add', 'table' => 'products', 'column' => 'price'],
            ],
            'indexes' => [],
            'foreign_keys' => [],
            'engine' => [],
            'charset' => [],
            'collation' => [],
        ];

        $destructiveChanges = [
            [
                'operation' => ['type' => 'column', 'action' => 'drop'],
                'risk_level' => 'high',
                'affected_tables' => ['users'],
                'affected_columns' => [],
            ],
        ];

        $output = $this->formatter->format($diff, $destructiveChanges);
        $data = json_decode($output, true);

        // Verify we can use the data programmatically
        $this->assertEquals(1, $data['summary']['tables']);
        $this->assertEquals(2, $data['summary']['columns']);
        $this->assertTrue($data['summary']['has_destructive_changes']);
        $this->assertEquals('high', $data['destructive_changes'][0]['risk_level']);
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
