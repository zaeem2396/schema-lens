<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        $referencing = DB::table('information_schema.key_column_usage as kcu')
            ->join('information_schema.referential_constraints as rc', function ($join) {
                $join->on('kcu.constraint_name', '=', 'rc.constraint_name')
                    ->on('kcu.table_schema', '=', 'rc.constraint_schema');
            })
            ->where('kcu.referenced_table_schema', DB::connection()->getDatabaseName())
            ->where('kcu.referenced_table_name', $tableName)
            ->distinct()
            ->pluck('kcu.table_name')
            ->toArray();

        return $referencing;
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
        switch ($type) {
            case 'table':
                if ($action === 'drop') {
                    return "DROP TABLE IF EXISTS `{$data['table']}`;";
                }
                break;

            case 'column':
                if ($action === 'drop') {
                    return "ALTER TABLE `{$data['table']}` DROP COLUMN `{$data['column']}`;";
                } elseif ($action === 'rename') {
                    return "ALTER TABLE `{$data['table']}` RENAME COLUMN `{$data['from']}` TO `{$data['to']}`;";
                }
                break;

            case 'index':
                if ($action === 'drop') {
                    $indexName = $data['name'] ?? '';

                    return "ALTER TABLE `{$data['table']}` DROP INDEX `{$indexName}`;";
                }
                break;

            case 'foreign_key':
                if ($action === 'drop') {
                    $fkName = $data['name'] ?? '';

                    return "ALTER TABLE `{$data['table']}` DROP FOREIGN KEY `{$fkName}`;";
                }
                break;
        }

        return null;
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
