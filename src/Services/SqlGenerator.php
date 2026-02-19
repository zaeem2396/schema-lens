<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Support\Collection;

class SqlGenerator
{
    protected string $tablePrefix = '';

    protected string $charset = 'utf8mb4';

    protected string $collation = 'utf8mb4_unicode_ci';

    protected string $engine = 'InnoDB';

    /**
     * Track columns for CREATE TABLE statements.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected array $tableColumns = [];

    public function __construct()
    {
        // Use the default database connection instead of hardcoding 'mysql'
        $connection = config('database.default', 'mysql');
        $this->tablePrefix = config("database.connections.{$connection}.prefix", '');
        $this->charset = config("database.connections.{$connection}.charset", 'utf8mb4');
        $this->collation = config("database.connections.{$connection}.collation", 'utf8mb4_unicode_ci');
        $this->engine = config('schema-lens.sql.engine')
            ?? config("database.connections.{$connection}.engine", 'InnoDB');
    }

    /**
     * Generate SQL statements from parsed migration operations.
     */
    public function generate(Collection $operations): array
    {
        $statements = [];
        $this->tableColumns = [];

        // First pass: collect columns for each CREATE TABLE operation
        foreach ($operations as $operation) {
            $type = $operation['type'] ?? '';
            $action = $operation['action'] ?? '';
            $data = $operation['data'] ?? [];

            if ($type === 'table' && $action === 'create') {
                $tableName = $data['table'] ?? '';
                $this->tableColumns[$tableName] = [];
            } elseif ($type === 'column' && $action === 'add') {
                $tableName = $data['table'] ?? '';
                if (isset($this->tableColumns[$tableName])) {
                    // This column belongs to a CREATE TABLE in this migration
                    $this->tableColumns[$tableName][] = $data;
                }
            }
        }

        // Second pass: generate SQL statements
        foreach ($operations as $operation) {
            $sql = $this->operationToSql($operation);
            if ($sql) {
                $statements[] = [
                    'operation' => $operation,
                    'sql' => $sql,
                ];
            }
        }

        return $statements;
    }

    /**
     * Convert a single operation to SQL.
     */
    protected function operationToSql(array $operation): ?string
    {
        $type = $operation['type'] ?? '';
        $action = $operation['action'] ?? '';
        $data = $operation['data'] ?? [];

        return match ($type) {
            'table' => $this->generateTableSql($action, $data),
            'column' => $this->generateColumnSql($action, $data),
            'index' => $this->generateIndexSql($action, $data),
            'foreign_key' => $this->generateForeignKeySql($action, $data),
            default => null,
        };
    }

    /**
     * Generate SQL for table operations.
     */
    protected function generateTableSql(string $action, array $data): ?string
    {
        $tableName = $data['table'] ?? '';
        $table = $this->prefixTable($tableName);

        return match ($action) {
            'create' => $this->generateCreateTableSql($tableName, $table),
            'drop' => "DROP TABLE IF EXISTS `{$table}`;",
            'modify' => "-- ALTER TABLE `{$table}` (modifications follow)",
            default => null,
        };
    }

    /**
     * Generate CREATE TABLE SQL with columns.
     */
    protected function generateCreateTableSql(string $tableName, string $prefixedTable): string
    {
        $columns = $this->tableColumns[$tableName] ?? [];

        if (empty($columns)) {
            // Fallback if no columns were collected
            return "CREATE TABLE IF NOT EXISTS `{$prefixedTable}` (\n    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation};";
        }

        $columnDefinitions = [];
        $primaryKey = null;

        foreach ($columns as $col) {
            $colName = $col['column'] ?? '';
            $colType = $col['type'] ?? 'string';
            $sqlType = $this->laravelTypeToSql($colType);
            $definition = $col['definition'] ?? '';

            // Check for auto-increment primary keys
            if (in_array($colType, ['bigIncrements', 'increments', 'tinyIncrements', 'smallIncrements', 'mediumIncrements'])) {
                $sqlType = $this->getIncrementsSqlType($colType);
                $columnDefinitions[] = "    `{$colName}` {$sqlType} NOT NULL AUTO_INCREMENT";
                $primaryKey = $colName;
            } else {
                // Check for nullable in definition
                $nullable = strpos($definition, '->nullable') !== false ? 'NULL' : 'NOT NULL';
                $columnDefinitions[] = "    `{$colName}` {$sqlType} {$nullable}";
            }
        }

        // Add primary key constraint
        if ($primaryKey) {
            $columnDefinitions[] = "    PRIMARY KEY (`{$primaryKey}`)";
        }

        $columnsStr = implode(",\n", $columnDefinitions);

        return "CREATE TABLE IF NOT EXISTS `{$prefixedTable}` (\n{$columnsStr}\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation};";
    }

    /**
     * Get SQL type for auto-incrementing columns.
     */
    protected function getIncrementsSqlType(string $laravelType): string
    {
        return match ($laravelType) {
            'bigIncrements' => 'BIGINT UNSIGNED',
            'increments' => 'INT UNSIGNED',
            'tinyIncrements' => 'TINYINT UNSIGNED',
            'smallIncrements' => 'SMALLINT UNSIGNED',
            'mediumIncrements' => 'MEDIUMINT UNSIGNED',
            default => 'BIGINT UNSIGNED',
        };
    }

    /**
     * Generate SQL for column operations.
     */
    protected function generateColumnSql(string $action, array $data): ?string
    {
        $tableName = $data['table'] ?? '';
        $table = $this->prefixTable($tableName);
        $column = $data['column'] ?? '';
        $type = $data['type'] ?? 'varchar(255)';

        // Skip columns that are part of a CREATE TABLE (already included in the CREATE TABLE SQL)
        if ($action === 'add' && isset($this->tableColumns[$tableName])) {
            return null;
        }

        $sqlType = $this->laravelTypeToSql($type);
        $definition = $data['definition'] ?? '';

        // Check for nullable in definition
        $nullable = ($action === 'add' && strpos($definition, '->nullable') !== false) ? '' : ' NOT NULL';

        return match ($action) {
            'add' => "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$sqlType}{$nullable};",
            'drop' => "ALTER TABLE `{$table}` DROP COLUMN `{$column}`;",
            'modify' => "ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$sqlType};",
            'rename' => "ALTER TABLE `{$table}` RENAME COLUMN `{$data['from']}` TO `{$data['to']}`;",
            default => null,
        };
    }

    /**
     * Generate SQL for index operations.
     */
    protected function generateIndexSql(string $action, array $data): ?string
    {
        $table = $this->prefixTable($data['table'] ?? '');
        $columns = $data['columns'] ?? [];
        $indexType = $data['type'] ?? 'index';
        $indexName = $data['name'] ?? $this->generateIndexName($table, $columns, $indexType);

        $columnList = implode('`, `', $columns);

        if ($action === 'add') {
            return match ($indexType) {
                'primary' => "ALTER TABLE `{$table}` ADD PRIMARY KEY (`{$columnList}`);",
                'unique' => "ALTER TABLE `{$table}` ADD UNIQUE INDEX `{$indexName}` (`{$columnList}`);",
                default => "ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$columnList}`);",
            };
        }

        if ($action === 'drop') {
            return "ALTER TABLE `{$table}` DROP INDEX `{$indexName}`;";
        }

        return null;
    }

    /**
     * Generate SQL for foreign key operations.
     */
    protected function generateForeignKeySql(string $action, array $data): ?string
    {
        $table = $this->prefixTable($data['table'] ?? '');
        $columns = $data['columns'] ?? [];
        $referencedTable = $this->prefixTable($data['referenced_table'] ?? '');
        $referencedColumns = $data['referenced_columns'] ?? ['id'];
        $onDelete = strtoupper($data['on_delete'] ?? 'RESTRICT');
        $onUpdate = strtoupper($data['on_update'] ?? 'RESTRICT');

        $columnList = implode('`, `', $columns);
        $refColumnList = implode('`, `', $referencedColumns);
        $constraintName = $data['name'] ?? $this->generateForeignKeyName($table, $columns);

        if ($action === 'add') {
            return "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$columnList}`) REFERENCES `{$referencedTable}` (`{$refColumnList}`) ON DELETE {$onDelete} ON UPDATE {$onUpdate};";
        }

        if ($action === 'drop') {
            return "ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraintName}`;";
        }

        return null;
    }

    /**
     * Convert Laravel column type to MySQL type.
     */
    protected function laravelTypeToSql(string $laravelType): string
    {
        return match ($laravelType) {
            'bigInteger', 'unsignedBigInteger' => 'BIGINT'.($laravelType === 'unsignedBigInteger' ? ' UNSIGNED' : ''),
            'integer', 'unsignedInteger' => 'INT'.($laravelType === 'unsignedInteger' ? ' UNSIGNED' : ''),
            'tinyInteger', 'unsignedTinyInteger' => 'TINYINT'.($laravelType === 'unsignedTinyInteger' ? ' UNSIGNED' : ''),
            'smallInteger', 'unsignedSmallInteger' => 'SMALLINT'.($laravelType === 'unsignedSmallInteger' ? ' UNSIGNED' : ''),
            'mediumInteger', 'unsignedMediumInteger' => 'MEDIUMINT'.($laravelType === 'unsignedMediumInteger' ? ' UNSIGNED' : ''),
            'string', 'char' => 'VARCHAR(255)',
            'text' => 'TEXT',
            'longText' => 'LONGTEXT',
            'mediumText' => 'MEDIUMTEXT',
            'tinyText' => 'TINYTEXT',
            'boolean' => 'TINYINT(1)',
            'date' => 'DATE',
            'datetime', 'timestamp' => 'DATETIME',
            'decimal' => 'DECIMAL(8,2)',
            'float' => 'FLOAT',
            'double' => 'DOUBLE',
            'enum' => "ENUM('value1', 'value2')",
            'json', 'jsonb' => 'JSON',
            'binary' => 'BLOB',
            'uuid' => 'CHAR(36)',
            'ipAddress' => 'VARCHAR(45)',
            'macAddress' => 'VARCHAR(17)',
            default => 'VARCHAR(255)',
        };
    }

    /**
     * Add table prefix if configured.
     */
    protected function prefixTable(string $table): string
    {
        return $this->tablePrefix.$table;
    }

    /**
     * Generate an index name.
     */
    protected function generateIndexName(string $table, array $columns, string $type): string
    {
        $suffix = match ($type) {
            'unique' => 'unique',
            'primary' => 'primary',
            default => 'index',
        };

        return strtolower($table.'_'.implode('_', $columns).'_'.$suffix);
    }

    /**
     * Generate a foreign key constraint name.
     */
    protected function generateForeignKeyName(string $table, array $columns): string
    {
        return strtolower($table.'_'.implode('_', $columns).'_foreign');
    }

    /**
     * Format SQL statements as a single string with comments.
     */
    public function formatAsSqlScript(array $statements, string $migrationName): string
    {
        $output = [];
        $output[] = '-- ========================================';
        $output[] = '-- Migration: '.basename($migrationName, '.php');
        $output[] = '-- Generated by Schema Lens';
        $output[] = '-- Date: '.date('Y-m-d H:i:s');
        $output[] = '-- ========================================';
        $output[] = '';
        $output[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $output[] = '';

        foreach ($statements as $index => $statement) {
            $op = $statement['operation'];
            $output[] = '-- Operation '.($index + 1).': '.($op['type'] ?? 'unknown').'::'.($op['action'] ?? 'unknown');
            $output[] = $statement['sql'];
            $output[] = '';
        }

        $output[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $output[] = '';
        $output[] = '-- End of migration';

        return implode("\n", $output);
    }
}
