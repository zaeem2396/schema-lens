<?php

namespace Zaeem2396\SchemaLens\Services;

class DependencyAnalyzer
{
    protected MigrationParser $parser;

    public function __construct(MigrationParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Get migration files from path, sorted by name (Laravel order).
     *
     * @return array<string, string> [ migration_name => full_path ]
     */
    public function getMigrationFiles(string $migrationsPath): array
    {
        if (! is_dir($migrationsPath)) {
            return [];
        }

        $files = glob($migrationsPath.'/*.php');
        if ($files === false) {
            return [];
        }

        $result = [];
        foreach ($files as $path) {
            $name = str_replace('.php', '', basename($path));
            $result[$name] = $path;
        }

        ksort($result);

        return $result;
    }

    /**
     * Extract tables created and referenced by a migration (from up() operations).
     *
     * @return array{creates: array<string>, references: array<string>}
     */
    public function getMigrationTableUsage(string $filePath): array
    {
        $parsed = $this->parser->parse($filePath);
        $upOps = collect($parsed['operations'])->filter(fn ($op) => ($op['direction'] ?? '') === 'up');

        $creates = [];
        $references = [];

        foreach ($upOps as $op) {
            $type = $op['type'] ?? '';
            $action = $op['action'] ?? '';
            $data = $op['data'] ?? [];

            if ($type === 'table' && $action === 'create') {
                $table = $data['table'] ?? null;
                if ($table) {
                    $creates[] = $table;
                }
            }

            if ($type === 'foreign_key' && $action === 'add') {
                $ref = $data['referenced_table'] ?? null;
                if ($ref) {
                    $references[] = $ref;
                }
            }
        }

        return [
            'creates' => array_values(array_unique($creates)),
            'references' => array_values(array_unique($references)),
        ];
    }

    /**
     * Build dependency graph for all migrations in path.
     * Migration A depends on B if A references a table that B creates.
     * Edges are deduplicated (at most one edge per migration pair).
     *
     * @return array{
     *   nodes: array<string, array{creates: array<string>, references: array<string>}>,
     *   edges: array<int, array{from: string, to: string}>,
     *   circular: array<int, array<string>>
     * }
     */
    public function buildGraph(string $migrationsPath): array
    {
        $files = $this->getMigrationFiles($migrationsPath);
        $tableToMigration = []; // table_name => migration_name that creates it
        $nodes = [];
        $edges = [];

        foreach ($files as $name => $path) {
            try {
                $usage = $this->getMigrationTableUsage($path);
            } catch (\Throwable $e) {
                $usage = ['creates' => [], 'references' => []];
            }

            $nodes[$name] = $usage;

            foreach ($usage['creates'] as $table) {
                $tableToMigration[$table] = $name;
            }
        }

        $seenEdges = [];
        foreach ($nodes as $name => $usage) {
            foreach ($usage['references'] as $refTable) {
                $dep = $tableToMigration[$refTable] ?? null;
                if ($dep !== null && $dep !== $name) {
                    $key = $name.'::'.$dep;
                    if (! isset($seenEdges[$key])) {
                        $seenEdges[$key] = true;
                        $edges[] = ['from' => $name, 'to' => $dep];
                    }
                }
            }
        }

        $circular = $this->detectCircularDependencies($nodes, $edges);

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'circular' => $circular,
        ];
    }

    /**
     * Detect circular dependencies (migration A depends on B, B on C, C on A).
     *
     * @param  array<string, array{creates: array<string>, references: array<string>}>  $nodes
     * @param  array<int, array{from: string, to: string}>  $edges
     * @return array<int, array<string>>
     */
    protected function detectCircularDependencies(array $nodes, array $edges): array
    {
        $adj = [];
        foreach (array_keys($nodes) as $n) {
            $adj[$n] = [];
        }
        foreach ($edges as $e) {
            $adj[$e['from']][] = $e['to'];
        }

        $cycles = [];
        $visited = [];
        $stack = [];
        $inStack = [];
        /** @var array<string, int> $cyclePath */
        $cyclePath = [];

        foreach (array_keys($nodes) as $start) {
            $this->findCyclesDfs($start, $adj, $visited, $stack, $inStack, $cyclePath, $cycles);
        }

        return $cycles;
    }

    /**
     * @param  array<string, array<int, string>>  $adj
     * @param  array<string, bool>  $visited
     * @param  array<string>  $stack
     * @param  array<string, bool>  $inStack
     * @param  array<string, int>  $cyclePath
     * @param  array<int, array<string>>  $cycles
     */
    protected function findCyclesDfs(
        string $node,
        array $adj,
        array &$visited,
        array &$stack,
        array &$inStack,
        array &$cyclePath,
        array &$cycles
    ): void {
        $visited[$node] = true;
        $stack[] = $node;
        $inStack[$node] = true;
        $cyclePath[$node] = count($stack) - 1;

        foreach ($adj[$node] ?? [] as $next) {
            if (! isset($visited[$next])) {
                $this->findCyclesDfs($next, $adj, $visited, $stack, $inStack, $cyclePath, $cycles);
            } elseif (isset($inStack[$next]) && $inStack[$next]) {
                $cycle = [];
                $startIdx = $cyclePath[$next];
                for ($i = $startIdx; $i < count($stack); $i++) {
                    $cycle[] = $stack[$i];
                }
                $cycle[] = $next;
                if (! $this->cycleAlreadyFound($cycle, $cycles)) {
                    $cycles[] = $cycle;
                }
            }
        }

        array_pop($stack);
        $inStack[$node] = false;
    }

    /**
     * @param  array<string>  $cycle
     * @param  array<int, array<string>>  $cycles
     */
    protected function cycleAlreadyFound(array $cycle, array $cycles): bool
    {
        $set = array_flip($cycle);
        foreach ($cycles as $c) {
            if (count($c) === count($cycle) && count(array_intersect_key($set, array_flip($c))) === count($set)) {
                return true;
            }
        }

        return false;
    }
}
