<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class SchemaIntrospector
{
    protected string $database;

    public function __construct()
    {
        $this->database = DB::connection()->getDatabaseName();
    }

    /**
     * Get all tables in the database.
     */
    public function getTables(): Collection
    {
        return DB::table('information_schema.tables')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->pluck('TABLE_NAME');
    }

    /**
     * Get table structure including columns, indexes, foreign keys, etc.
     */
    public function getTableStructure(string $tableName): array
    {
        return [
            'columns' => $this->getColumns($tableName),
            'indexes' => $this->getIndexes($tableName),
            'foreign_keys' => $this->getForeignKeys($tableName),
            'engine' => $this->getTableEngine($tableName),
            'charset' => $this->getTableCharset($tableName),
            'collation' => $this->getTableCollation($tableName),
        ];
    }

    /**
     * Get all columns for a table.
     */
    public function getColumns(string $tableName): Collection
    {
        $columns = DB::table('information_schema.columns')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_NAME', $tableName)
            ->orderBy('ORDINAL_POSITION')
            ->get()
            ->map(function ($column) {
                return [
                    'name' => $column->COLUMN_NAME,
                    'type' => $column->COLUMN_TYPE,
                    'data_type' => $column->DATA_TYPE,
                    'nullable' => $column->IS_NULLABLE === 'YES',
                    'default' => $column->COLUMN_DEFAULT,
                    'extra' => $column->EXTRA,
                    'comment' => $column->COLUMN_COMMENT,
                ];
            });

        return $columns;
    }

    /**
     * Get all indexes for a table.
     */
    public function getIndexes(string $tableName): Collection
    {
        $indexes = DB::table('information_schema.statistics')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_NAME', $tableName)
            ->orderBy('INDEX_NAME')
            ->orderBy('SEQ_IN_INDEX')
            ->get()
            ->groupBy('INDEX_NAME')
            ->map(function ($indexGroup) {
                $first = $indexGroup->first();
                return [
                    'name' => $first->INDEX_NAME,
                    'columns' => $indexGroup->pluck('COLUMN_NAME')->toArray(),
                    'unique' => $first->NON_UNIQUE == 0,
                    'type' => $first->INDEX_TYPE,
                ];
            });

        return $indexes->values();
    }

    /**
     * Get all foreign keys for a table.
     */
    public function getForeignKeys(string $tableName): Collection
    {
        $foreignKeys = DB::table('information_schema.key_column_usage as kcu')
            ->join('information_schema.referential_constraints as rc', function ($join) {
                $join->on('kcu.CONSTRAINT_NAME', '=', 'rc.CONSTRAINT_NAME')
                    ->on('kcu.TABLE_SCHEMA', '=', 'rc.CONSTRAINT_SCHEMA');
            })
            ->where('kcu.TABLE_SCHEMA', $this->database)
            ->where('kcu.TABLE_NAME', $tableName)
            ->whereNotNull('kcu.REFERENCED_TABLE_NAME')
            ->select([
                'kcu.CONSTRAINT_NAME',
                'kcu.COLUMN_NAME',
                'kcu.REFERENCED_TABLE_NAME',
                'kcu.REFERENCED_COLUMN_NAME',
                'rc.UPDATE_RULE',
                'rc.DELETE_RULE',
            ])
            ->get()
            ->groupBy('CONSTRAINT_NAME')
            ->map(function ($constraintGroup) {
                $first = $constraintGroup->first();
                return [
                    'name' => $first->CONSTRAINT_NAME,
                    'columns' => $constraintGroup->pluck('COLUMN_NAME')->toArray(),
                    'referenced_table' => $first->REFERENCED_TABLE_NAME,
                    'referenced_columns' => $constraintGroup->pluck('REFERENCED_COLUMN_NAME')->toArray(),
                    'on_update' => $first->UPDATE_RULE,
                    'on_delete' => $first->DELETE_RULE,
                ];
            });

        return $foreignKeys->values();
    }

    /**
     * Get table engine.
     */
    public function getTableEngine(string $tableName): ?string
    {
        $result = DB::table('information_schema.tables')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_NAME', $tableName)
            ->value('ENGINE');

        return $result;
    }

    /**
     * Get table charset.
     */
    public function getTableCharset(string $tableName): ?string
    {
        $result = DB::table('information_schema.tables')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_NAME', $tableName)
            ->value('TABLE_COLLATION');

        return $result ? explode('_', $result)[0] : null;
    }

    /**
     * Get table collation.
     */
    public function getTableCollation(string $tableName): ?string
    {
        return DB::table('information_schema.tables')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_NAME', $tableName)
            ->value('TABLE_COLLATION');
    }

    /**
     * Check if a table exists.
     */
    public function tableExists(string $tableName): bool
    {
        return DB::table('information_schema.tables')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_NAME', $tableName)
            ->exists();
    }

    /**
     * Check if a column exists in a table.
     */
    public function columnExists(string $tableName, string $columnName): bool
    {
        return DB::table('information_schema.columns')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->exists();
    }

    /**
     * Get current schema state for all tables.
     */
    public function getCurrentSchema(): array
    {
        $tables = $this->getTables();
        $schema = [];

        foreach ($tables as $table) {
            $schema[$table] = $this->getTableStructure($table);
        }

        return $schema;
    }
}
