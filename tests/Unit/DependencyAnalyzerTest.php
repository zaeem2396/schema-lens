<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Services\DependencyAnalyzer;
use Zaeem2396\SchemaLens\Services\MigrationParser;
use Zaeem2396\SchemaLens\Tests\TestCase;

class DependencyAnalyzerTest extends TestCase
{
    protected DependencyAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new DependencyAnalyzer(new MigrationParser);
    }

    /** @test */
    public function it_returns_migration_files_sorted_by_name(): void
    {
        $path = $this->getFixturePath();
        $files = $this->analyzer->getMigrationFiles($path);

        $this->assertIsArray($files);
        $this->assertNotEmpty($files);
        $names = array_keys($files);
        $sorted = $names;
        sort($sorted);
        $this->assertEquals($sorted, $names);
    }

    /** @test */
    public function it_returns_empty_for_non_directory(): void
    {
        $files = $this->analyzer->getMigrationFiles(__DIR__.'/nonexistent');
        $this->assertSame([], $files);
    }

    /** @test */
    public function it_extracts_tables_created_and_referenced(): void
    {
        $path = $this->getFixturePath('2024_01_06_000000_create_posts_with_foreign_key.php');
        $usage = $this->analyzer->getMigrationTableUsage($path);

        $this->assertArrayHasKey('creates', $usage);
        $this->assertArrayHasKey('references', $usage);
        $this->assertContains('posts', $usage['creates']);
        $this->assertContains('users', $usage['references']);
    }

    /** @test */
    public function it_builds_graph_with_nodes_and_edges(): void
    {
        $path = $this->getFixturePath();
        $graph = $this->analyzer->buildGraph($path);

        $this->assertArrayHasKey('nodes', $graph);
        $this->assertArrayHasKey('edges', $graph);
        $this->assertArrayHasKey('circular', $graph);
        $this->assertNotEmpty($graph['nodes']);
        $this->assertIsArray($graph['edges']);
        $this->assertIsArray($graph['circular']);
    }

    /** @test */
    public function it_detects_posts_depends_on_users_from_fixtures(): void
    {
        $path = $this->getFixturePath();
        $graph = $this->analyzer->buildGraph($path);

        $edgesFromPosts = array_filter($graph['edges'], fn ($e) => str_contains($e['from'], 'posts') && str_contains($e['to'], 'users'));
        $this->assertNotEmpty($edgesFromPosts, 'Expected at least one edge from posts migration to users migration');
    }
}
