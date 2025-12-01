<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Support\Collection;

class DiffGenerator
{
    protected SchemaIntrospector $introspector;

    public function __construct(SchemaIntrospector $introspector)
    {
        $this->introspector = $introspector;
    }

    /**
     * Generate diff between migration operations and current schema.
     */
    public function generateDiff(array $migrationOperations, array $currentSchema): array
    {
        $diffs = [
            'tables' => [],
            'columns' => [],
            'indexes' => [],
            'foreign_keys' => [],
            'engine' => [],
            'charset' => [],
            'collation' => [],
        ];

        foreach ($migrationOperations as $operation) {
            $type = $operation['type'];
            $action = $operation['action'];
            $data = $operation['data'];
            $line = $operation['line'];

            switch ($type) {
                case 'table':
                    $diffs['tables'][] = $this->diffTable($action, $data, $currentSchema, $line);
                    break;
                case 'column':
                    $diffs['columns'][] = $this->diffColumn($action, $data, $currentSchema, $line);
                    break;
                case 'index':
                    $diffs['indexes'][] = $this->diffIndex($action, $data, $currentSchema, $line);
                    break;
                case 'foreign_key':
                    $diffs['foreign_keys'][] = $this->diffForeignKey($action, $data, $currentSchema, $line);
                    break;
                case 'engine':
                    $diffs['engine'][] = $this->diffEngine($action, $data, $currentSchema, $line);
                    break;
                case 'charset':
                    $diffs['charset'][] = $this->diffCharset($action, $data, $currentSchema, $line);
                    break;
                case 'collation':
                    $diffs['collation'][] = $this->diffCollation($action, $data, $currentSchema, $line);
                    break;
            }
        }

        return $diffs;
    }

    /**
     * Diff table operations.
     */
    protected function diffTable(string $action, array $data, array $currentSchema, int $line): array
    {
        $tableName = $data['table'];
        $exists = $this->introspector->tableExists($tableName);

        if ($action === 'create') {
            return [
                'action' => 'create',
                'table' => $tableName,
                'exists' => $exists,
                'line' => $line,
                'status' => $exists ? 'warning' : 'new',
                'message' => $exists ? "Table '{$tableName}' already exists" : "Will create new table '{$tableName}'",
            ];
        } elseif ($action === 'modify') {
            return [
                'action' => 'modify',
                'table' => $tableName,
                'exists' => $exists,
                'line' => $line,
                'status' => $exists ? 'info' : 'error',
                'message' => $exists ? "Will modify existing table '{$tableName}'" : "Table '{$tableName}' does not exist",
            ];
        } elseif ($action === 'drop') {
            return [
                'action' => 'drop',
                'table' => $tableName,
                'exists' => $exists,
                'line' => $line,
                'status' => $exists ? 'destructive' : 'warning',
                'message' => $exists ? "Will DROP table '{$tableName}' (DESTRUCTIVE)" : "Table '{$tableName}' does not exist",
            ];
        }

        return [];
    }

    /**
     * Diff column operations.
     */
    protected function diffColumn(string $action, array $data, array $currentSchema, int $line): array
    {
        $tableName = $data['table'];
        $columnName = $data['column'] ?? null;
        $tableExists = $this->introspector->tableExists($tableName);
        $currentColumns = $tableExists && isset($currentSchema[$tableName])
            ? $currentSchema[$tableName]['columns']
            : collect();

        if ($action === 'add') {
            $columnExists = $columnName && $this->introspector->columnExists($tableName, $columnName);
            return [
                'action' => 'add',
                'table' => $tableName,
                'column' => $columnName,
                'type' => $data['type'] ?? null,
                'exists' => $columnExists,
                'line' => $line,
                'status' => $columnExists ? 'warning' : 'new',
                'message' => $columnExists
                    ? "Column '{$tableName}.{$columnName}' already exists"
                    : "Will add new column '{$tableName}.{$columnName}'",
            ];
        } elseif ($action === 'modify') {
            $columnExists = $columnName && $this->introspector->columnExists($tableName, $columnName);
            $currentColumn = $currentColumns->firstWhere('name', $columnName);
            return [
                'action' => 'modify',
                'table' => $tableName,
                'column' => $columnName,
                'exists' => $columnExists,
                'current' => $currentColumn,
                'line' => $line,
                'status' => $columnExists ? 'info' : 'error',
                'message' => $columnExists
                    ? "Will modify column '{$tableName}.{$columnName}'"
                    : "Column '{$tableName}.{$columnName}' does not exist",
            ];
        } elseif ($action === 'drop') {
            $columnExists = $columnName && $this->introspector->columnExists($tableName, $columnName);
            $currentColumn = $currentColumns->firstWhere('name', $columnName);
            return [
                'action' => 'drop',
                'table' => $tableName,
                'column' => $columnName,
                'exists' => $columnExists,
                'current' => $currentColumn,
                'line' => $line,
                'status' => $columnExists ? 'destructive' : 'warning',
                'message' => $columnExists
                    ? "Will DROP column '{$tableName}.{$columnName}' (DESTRUCTIVE)"
                    : "Column '{$tableName}.{$columnName}' does not exist",
            ];
        } elseif ($action === 'rename') {
            $fromExists = $this->introspector->columnExists($tableName, $data['from']);
            $toExists = $this->introspector->columnExists($tableName, $data['to']);
            return [
                'action' => 'rename',
                'table' => $tableName,
                'from' => $data['from'],
                'to' => $data['to'],
                'from_exists' => $fromExists,
                'to_exists' => $toExists,
                'line' => $line,
                'status' => $fromExists && !$toExists ? 'destructive' : ($fromExists ? 'warning' : 'error'),
                'message' => $fromExists && !$toExists
                    ? "Will RENAME column '{$tableName}.{$data['from']}' to '{$data['to']}' (DESTRUCTIVE)"
                    : ($fromExists ? "Column '{$data['to']}' already exists" : "Column '{$data['from']}' does not exist"),
            ];
        }

        return [];
    }

    /**
     * Diff index operations.
     */
    protected function diffIndex(string $action, array $data, array $currentSchema, int $line): array
    {
        $tableName = $data['table'];
        $indexName = $data['name'] ?? null;
        $tableExists = $this->introspector->tableExists($tableName);
        $currentIndexes = $tableExists && isset($currentSchema[$tableName])
            ? $currentSchema[$tableName]['indexes']
            : collect();

        if ($action === 'add') {
            $indexExists = $indexName && $currentIndexes->contains(function ($index) use ($indexName) {
                return ($index['name'] ?? null) === $indexName;
            });
            return [
                'action' => 'add',
                'table' => $tableName,
                'name' => $indexName,
                'columns' => $data['columns'] ?? [],
                'type' => $data['type'] ?? null,
                'exists' => $indexExists,
                'line' => $line,
                'status' => $indexExists ? 'warning' : 'new',
                'message' => $indexExists
                    ? "Index '{$indexName}' on '{$tableName}' already exists"
                    : "Will add new index '{$indexName}' on '{$tableName}'",
            ];
        } elseif ($action === 'drop') {
            $currentIndex = $currentIndexes->firstWhere('name', $indexName);
            $indexExists = $indexName && $currentIndex !== null;
            return [
                'action' => 'drop',
                'table' => $tableName,
                'name' => $indexName,
                'exists' => $indexExists,
                'current' => $currentIndex,
                'line' => $line,
                'status' => $indexExists ? 'destructive' : 'warning',
                'message' => $indexExists
                    ? "Will DROP index '{$indexName}' on '{$tableName}' (DESTRUCTIVE)"
                    : "Index '{$indexName}' on '{$tableName}' does not exist",
            ];
        }

        return [];
    }

    /**
     * Diff foreign key operations.
     */
    protected function diffForeignKey(string $action, array $data, array $currentSchema, int $line): array
    {
        $tableName = $data['table'];
        $tableExists = $this->introspector->tableExists($tableName);
        $currentForeignKeys = $tableExists && isset($currentSchema[$tableName])
            ? $currentSchema[$tableName]['foreign_keys']
            : collect();

        if ($action === 'add') {
            // Try to find matching foreign key
            $columns = $data['columns'] ?? [];
            $referencedTable = $data['referenced_table'] ?? null;
            $matching = $currentForeignKeys->first(function ($fk) use ($columns, $referencedTable) {
                return $fk['referenced_table'] === $referencedTable &&
                       empty(array_diff($fk['columns'], $columns));
            });

            return [
                'action' => 'add',
                'table' => $tableName,
                'columns' => $columns,
                'referenced_table' => $referencedTable,
                'exists' => $matching !== null,
                'line' => $line,
                'status' => $matching ? 'warning' : 'new',
                'message' => $matching
                    ? "Foreign key on '{$tableName}' already exists"
                    : "Will add new foreign key on '{$tableName}'",
            ];
        } elseif ($action === 'drop') {
            $fkName = $data['name'] ?? null;
            $currentFk = $currentForeignKeys->firstWhere('name', $fkName);
            $fkExists = $fkName && $currentFk !== null;
            return [
                'action' => 'drop',
                'table' => $tableName,
                'name' => $fkName,
                'exists' => $fkExists,
                'current' => $currentFk,
                'line' => $line,
                'status' => $fkExists ? 'destructive' : 'warning',
                'message' => $fkExists
                    ? "Will DROP foreign key '{$fkName}' on '{$tableName}' (DESTRUCTIVE)"
                    : "Foreign key '{$fkName}' on '{$tableName}' does not exist",
            ];
        }

        return [];
    }

    /**
     * Diff engine operations.
     */
    protected function diffEngine(string $action, array $data, array $currentSchema, int $line): array
    {
        $tableName = $data['table'];
        $newEngine = $data['engine'] ?? null;
        $tableExists = $this->introspector->tableExists($tableName);
        $currentEngine = $tableExists && isset($currentSchema[$tableName])
            ? $currentSchema[$tableName]['engine']
            : null;

        return [
            'action' => 'change',
            'table' => $tableName,
            'current' => $currentEngine,
            'new' => $newEngine,
            'line' => $line,
            'status' => $currentEngine === $newEngine ? 'info' : 'change',
            'message' => $currentEngine === $newEngine
                ? "Engine for '{$tableName}' is already '{$newEngine}'"
                : "Will change engine for '{$tableName}' from '{$currentEngine}' to '{$newEngine}'",
        ];
    }

    /**
     * Diff charset operations.
     */
    protected function diffCharset(string $action, array $data, array $currentSchema, int $line): array
    {
        $tableName = $data['table'];
        $newCharset = $data['charset'] ?? null;
        $tableExists = $this->introspector->tableExists($tableName);
        $currentCharset = $tableExists && isset($currentSchema[$tableName])
            ? $currentSchema[$tableName]['charset']
            : null;

        return [
            'action' => 'change',
            'table' => $tableName,
            'current' => $currentCharset,
            'new' => $newCharset,
            'line' => $line,
            'status' => $currentCharset === $newCharset ? 'info' : 'change',
            'message' => $currentCharset === $newCharset
                ? "Charset for '{$tableName}' is already '{$newCharset}'"
                : "Will change charset for '{$tableName}' from '{$currentCharset}' to '{$newCharset}'",
        ];
    }

    /**
     * Diff collation operations.
     */
    protected function diffCollation(string $action, array $data, array $currentSchema, int $line): array
    {
        $tableName = $data['table'];
        $newCollation = $data['collation'] ?? null;
        $tableExists = $this->introspector->tableExists($tableName);
        $currentCollation = $tableExists && isset($currentSchema[$tableName])
            ? $currentSchema[$tableName]['collation']
            : null;

        return [
            'action' => 'change',
            'table' => $tableName,
            'current' => $currentCollation,
            'new' => $newCollation,
            'line' => $line,
            'status' => $currentCollation === $newCollation ? 'info' : 'change',
            'message' => $currentCollation === $newCollation
                ? "Collation for '{$tableName}' is already '{$newCollation}'"
                : "Will change collation for '{$tableName}' from '{$currentCollation}' to '{$newCollation}'",
        ];
    }
}

