<?php

namespace Zaeem2396\SchemaLens\Commands;

use Illuminate\Console\Command;
use Zaeem2396\SchemaLens\Services\DependencyAnalyzer;

class DependencyGraphCommand extends Command
{
    protected $signature = 'schema:graph
                            {--path= : Path to migrations directory}
                            {--format=cli : Output format (cli or json)}';

    protected $description = 'Show migration dependency graph (tables and foreign keys)';

    public function handle(DependencyAnalyzer $analyzer): int
    {
        $path = $this->option('path') ?: database_path('migrations');

        if (! is_dir($path)) {
            $this->error("Migrations path not found: {$path}");

            return self::FAILURE;
        }

        $this->info('Building migration dependency graph...');
        $this->newLine();

        try {
            $graph = $analyzer->buildGraph($path);
        } catch (\Throwable $e) {
            $this->error('Error building graph: '.$e->getMessage());

            return self::FAILURE;
        }

        if (empty($graph['nodes'])) {
            $this->warn('No migration files found in '.$path);
            $this->line('  Add .php migration files or use --path to point to another directory.');

            return $this->option('path') ? self::FAILURE : self::SUCCESS;
        }

        if (! empty($graph['circular'])) {
            $this->warn('Circular dependencies detected:');
            foreach ($graph['circular'] as $cycle) {
                $this->line('  → '.implode(' → ', $cycle));
            }
            $this->newLine();
        }

        $format = $this->option('format');
        if ($format === 'json') {
            $this->line($this->formatAsJson($graph));

            return self::SUCCESS;
        }

        $this->line($this->formatAsAscii($graph));

        return self::SUCCESS;
    }

    /**
     * @param  array{nodes: array<string, array{creates: array<string>, references: array<string>}>, edges: array<int, array{from: string, to: string}>, circular: array<int, array<string>>}  $graph
     */
    protected function formatAsAscii(array $graph): string
    {
        $nodes = $graph['nodes'];
        $edges = $graph['edges'];

        $dependents = [];
        foreach (array_keys($nodes) as $name) {
            $dependents[$name] = [];
        }
        foreach ($edges as $e) {
            $dependents[$e['to']][] = $e['from'];
        }

        $lines = [];
        $lines[] = 'Migration Dependency Graph';
        $lines[] = '';

        $froms = array_unique(array_column($edges, 'from'));
        $roots = array_values(array_diff(array_keys($nodes), $froms));
        if (empty($roots)) {
            $roots = array_keys($nodes);
        }

        $this->appendTree($roots, $nodes, $dependents, $lines, '', true);

        return implode("\n", $lines);
    }

    /**
     * @param  array<string>  $names
     * @param  array<string, array{creates: array<string>, references: array<string>}>  $nodes
     * @param  array<string, array<string>>  $dependents
     * @param  array<int, string>  $lines
     */
    protected function appendTree(
        array $names,
        array $nodes,
        array $dependents,
        array &$lines,
        string $prefix,
        bool $isLast
    ): void {
        $count = count($names);
        foreach (array_values($names) as $i => $name) {
            $last = $i === $count - 1;
            $conn = $last ? '└── ' : '├── ';
            $lines[] = $prefix.$conn.$name;

            $children = $dependents[$name] ?? [];
            if ($children !== []) {
                $ext = $last ? '    ' : '│   ';
                $this->appendTree($children, $nodes, $dependents, $lines, $prefix.$ext, $last);
            }
        }
    }

    /**
     * @param  array{nodes: array<string, array{creates: array<string>, references: array<string>}>, edges: array<int, array{from: string, to: string}>, circular: array<int, array<string>>}  $graph
     */
    protected function formatAsJson(array $graph): string
    {
        $export = [
            'migrations' => array_keys($graph['nodes']),
            'nodes' => $graph['nodes'],
            'edges' => $graph['edges'],
            'circular' => $graph['circular'],
        ];

        return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
