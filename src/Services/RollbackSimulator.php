<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Support\Collection;
use Zaeem2396\SchemaLens\Services\Introspection\MySqlInformationSchemaDriver;
use Zaeem2396\SchemaLens\Services\Introspection\PostgresInformationSchemaDriver;

class RollbackSimulator
{
    protected SchemaIntrospector $introspector;

    protected MigrationParser $parser;

    public function __construct(SchemaIntrospector $introspector, MigrationParser $parser)
    {
        $this->introspector = $introspector;
        $this->parser = $parser;
    }

    /**
     * Simulate rollback and analyze impact.
     */
    public function simulate(string $migrationFile): array
    {
        $parsed = $this->parser->parse($migrationFile);
        $downOperations = $parsed['operations'];
        $downOps = collect($downOperations)->filter(function ($op) {
            return $op['direction'] === 'down';
        });

        if ($downOps->isEmpty()) {
            return [
                'has_rollback' => false,
                'message' => 'Migration does not have a down() method',
                'operations' => [],
                'dependencies' => [],
                'sql_preview' => [],
            ];
        }

        $dependencies = $this->analyzeDependencies($downOps);
        $sqlPreview = $this->generateSqlPreview($downOps);

        return [
            'has_rollback' => true,
            'operations' => $downOps->toArray(),
            'dependencies' => $dependencies,
            'sql_preview' => $sqlPreview,
            'impact' => $this->analyzeImpact($downOps),
        ];
    }

    /**
     * Analyze dependencies for rollback operations.
     */
    protected function analyzeDependencies(Collection $operations): array
    {
        $dependencies = [];

        foreach ($operations as $operation) {
            $type = $operation['type'];
            $action = $operation['action'];
            $data = $operation['data'];

            if ($type === 'foreign_key' && $action === 'drop') {
                // Dropping foreign key might break referential integrity
                $dependencies[] = [
                    'type' => 'foreign_key_drop',
                    'table' => $data['table'] ?? null,
                    'name' => $data['name'] ?? null,
                    'risk' => 'medium',
                    'message' => 'Dropping foreign key may break referential integrity',
                ];
            }

            if ($type === 'index' && $action === 'drop') {
                // Dropping index might affect query performance
                $dependencies[] = [
                    'type' => 'index_drop',
                    'table' => $data['table'] ?? null,
                    'name' => $data['name'] ?? null,
                    'risk' => 'low',
                    'message' => 'Dropping index may affect query performance',
                ];
            }

            if ($type === 'column' && $action === 'drop') {
                // Dropping column might break dependent views, triggers, or stored procedures
                $dependencies[] = [
                    'type' => 'column_drop',
                    'table' => $data['table'] ?? null,
                    'column' => $data['column'] ?? null,
                    'risk' => 'high',
                    'message' => 'Dropping column may break dependent database objects',
                ];
            }

            if ($type === 'table' && $action === 'drop') {
                // Dropping table will break all foreign keys referencing it
                $referencingTables = $this->findReferencingTables($data['table'] ?? '');
                if (! empty($referencingTables)) {
                    $dependencies[] = [
                        'type' => 'table_drop',
                        'table' => $data['table'] ?? null,
                        'referencing_tables' => $referencingTables,
                        'risk' => 'critical',
                        'message' => 'Dropping table will break foreign keys in: '.implode(', ', $referencingTables),
                    ];
                }
            }
        }

        return $dependencies;
    }

    /**
     * Find tables that have foreign keys referencing a given table.
     */
    protected function findReferencingTables(string $tableName): array
    {
        if (! $this->introspector->tableExists($tableName)) {
            return [];
        }

        $conn = $this->introspector->getConnection();
        $driver = strtolower($conn->getDriverName());

        $query = $conn->table('information_schema.key_column_usage as kcu')
            ->join('information_schema.referential_constraints as rc', function ($join) {
                $join->on('kcu.constraint_name', '=', 'rc.constraint_name')
                    ->on('kcu.constraint_catalog', '=', 'rc.constraint_catalog')
                    ->on('kcu.constraint_schema', '=', 'rc.constraint_schema');
            })
            ->whereNotNull('kcu.referenced_table_name')
            ->whereRaw('LOWER(kcu.referenced_table_name) = ?', [strtolower($tableName)]);

        if (PostgresInformationSchemaDriver::supports($driver)) {
            $schema = (string) ($conn->getConfig('schema') ?? 'public');
            $query->where('kcu.referenced_table_catalog', $conn->getDatabaseName())
                ->whereRaw('LOWER(kcu.referenced_table_schema) = ?', [strtolower($schema)]);
        } elseif (MySqlInformationSchemaDriver::supports($driver)) {
            $query->where('kcu.referenced_table_schema', $conn->getDatabaseName())
                ->where('kcu.referenced_table_name', $tableName);
        } else {
            return [];
        }

        return $query->distinct()
            ->pluck('kcu.table_name')
            ->toArray();
    }

    /**
     * Generate SQL preview for rollback operations.
     */
    protected function generateSqlPreview(Collection $operations): array
    {
        $sqlStatements = [];

        foreach ($operations as $operation) {
            $type = $operation['type'];
            $action = $operation['action'];
            $data = $operation['data'];
            $line = $operation['line'];

            $sql = $this->generateSqlForOperation($type, $action, $data);
            if ($sql) {
                $sqlStatements[] = [
                    'line' => $line,
                    'type' => $type,
                    'action' => $action,
                    'sql' => $sql,
                ];
            }
        }

        return $sqlStatements;
    }

    /**
     * Generate SQL for a specific operation.
     */
    protected function generateSqlForOperation(string $type, string $action, array $data): ?string
    {
        $pg = $this->introspector->isPostgreSql();

        switch ($type) {
            case 'table':
                if ($action === 'drop') {
                    $t = $this->quoteTable($data['table'] ?? '', $pg);

                    return $pg
                        ? "DROP TABLE IF EXISTS {$t};"
                        : "DROP TABLE IF EXISTS {$t};";
                }
                break;

            case 'column':
                if ($action === 'drop') {
                    $t = $this->quoteTable($data['table'] ?? '', $pg);
                    $c = $this->quoteIdent($data['column'] ?? '', $pg);

                    return "ALTER TABLE {$t} DROP COLUMN {$c};";
                } elseif ($action === 'rename') {
                    $t = $this->quoteTable($data['table'] ?? '', $pg);
                    $from = $this->quoteIdent($data['from'] ?? '', $pg);
                    $to = $this->quoteIdent($data['to'] ?? '', $pg);

                    return $pg
                        ? "ALTER TABLE {$t} RENAME COLUMN {$from} TO {$to};"
                        : 'ALTER TABLE '.$t.' RENAME COLUMN '.$from.' TO '.$to.';';
                }
                break;

            case 'index':
                if ($action === 'drop') {
                    $indexName = $data['name'] ?? '';
                    if ($pg) {
                        return 'DROP INDEX IF EXISTS '.$this->quoteIdent($indexName, true).';';
                    }
                    $t = $this->quoteTable($data['table'] ?? '', false);

                    return "ALTER TABLE {$t} DROP INDEX ".$this->quoteIdent($indexName, false).';';
                }
                break;

            case 'foreign_key':
                if ($action === 'drop') {
                    $fkName = $data['name'] ?? '';
                    $t = $this->quoteTable($data['table'] ?? '', $pg);

                    return $pg
                        ? 'ALTER TABLE '.$t.' DROP CONSTRAINT '.$this->quoteIdent($fkName, true).';'
                        : 'ALTER TABLE '.$t.' DROP FOREIGN KEY '.$this->quoteIdent($fkName, false).';';
                }
                break;
        }

        return null;
    }

    protected function quoteIdent(string $name, bool $postgresql): string
    {
        if ($name === '') {
            return $postgresql ? '""' : '``';
        }

        return $postgresql
            ? '"'.str_replace('"', '""', $name).'"'
            : '`'.str_replace('`', '``', $name).'`';
    }

    protected function quoteTable(string $name, bool $postgresql): string
    {
        return $this->quoteIdent($name, $postgresql);
    }

    /**
     * Analyze overall impact of rollback.
     */
    protected function analyzeImpact(Collection $operations): array
    {
        $impact = [
            'tables_affected' => [],
            'columns_affected' => [],
            'indexes_affected' => [],
            'foreign_keys_affected' => [],
            'risk_level' => 'low',
        ];

        foreach ($operations as $operation) {
            $type = $operation['type'];
            $action = $operation['action'];
            $data = $operation['data'];

            if ($type === 'table' && $action === 'drop') {
                $impact['tables_affected'][] = $data['table'] ?? null;
                $impact['risk_level'] = 'critical';
            }

            if ($type === 'column' && $action === 'drop') {
                $impact['columns_affected'][] = [
                    'table' => $data['table'] ?? null,
                    'column' => $data['column'] ?? null,
                ];
                if ($impact['risk_level'] !== 'critical') {
                    $impact['risk_level'] = 'high';
                }
            }

            if ($type === 'index' && $action === 'drop') {
                $impact['indexes_affected'][] = [
                    'table' => $data['table'] ?? null,
                    'name' => $data['name'] ?? null,
                ];
            }

            if ($type === 'foreign_key' && $action === 'drop') {
                $impact['foreign_keys_affected'][] = [
                    'table' => $data['table'] ?? null,
                    'name' => $data['name'] ?? null,
                ];
                if ($impact['risk_level'] === 'low') {
                    $impact['risk_level'] = 'medium';
                }
            }
        }

        return $impact;
    }
}
