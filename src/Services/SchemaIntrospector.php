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
            ->where('table_schema', $this->database)
            ->where('table_type', 'BASE TABLE')
            ->pluck('table_name');
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
            ->where('table_schema', $this->database)
            ->where('table_name', $tableName)
            ->orderBy('ordinal_position')
            ->get()
            ->map(function ($column) {
                return [
                    'name' => $column->column_name,
                    'type' => $column->column_type,
                    'data_type' => $column->data_type,
                    'nullable' => $column->is_nullable === 'YES',
                    'default' => $column->column_default,
                    'extra' => $column->extra,
                    'comment' => $column->column_comment,
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
            ->where('table_schema', $this->database)
            ->where('table_name', $tableName)
            ->orderBy('index_name')
            ->orderBy('seq_in_index')
            ->get()
            ->groupBy('index_name')
            ->map(function ($indexGroup) {
                $first = $indexGroup->first();
                return [
                    'name' => $first->index_name,
                    'columns' => $indexGroup->pluck('column_name')->toArray(),
                    'unique' => $first->non_unique == 0,
                    'type' => $first->index_type,
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
                $join->on('kcu.constraint_name', '=', 'rc.constraint_name')
                     ->on('kcu.table_schema', '=', 'rc.constraint_schema');
            })
            ->where('kcu.table_schema', $this->database)
            ->where('kcu.table_name', $tableName)
            ->whereNotNull('kcu.referenced_table_name')
            ->select([
                'kcu.constraint_name',
                'kcu.column_name',
                'kcu.referenced_table_name',
                'kcu.referenced_column_name',
                'rc.update_rule',
                'rc.delete_rule',
            ])
            ->get()
            ->groupBy('constraint_name')
            ->map(function ($constraintGroup) {
                $first = $constraintGroup->first();
                return [
                    'name' => $first->constraint_name,
                    'columns' => $constraintGroup->pluck('column_name')->toArray(),
                    'referenced_table' => $first->referenced_table_name,
                    'referenced_columns' => $constraintGroup->pluck('referenced_column_name')->toArray(),
                    'on_update' => $first->update_rule,
                    'on_delete' => $first->delete_rule,
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
            ->where('table_schema', $this->database)
            ->where('table_name', $tableName)
            ->value('engine');

        return $result;
    }

    /**
     * Get table charset.
     */
    public function getTableCharset(string $tableName): ?string
    {
        $result = DB::table('information_schema.tables')
            ->where('table_schema', $this->database)
            ->where('table_name', $tableName)
            ->value('table_collation');

        return $result ? explode('_', $result)[0] : null;
    }

    /**
     * Get table collation.
     */
    public function getTableCollation(string $tableName): ?string
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', $this->database)
            ->where('table_name', $tableName)
            ->value('table_collation');
    }

    /**
     * Check if a table exists.
     */
    public function tableExists(string $tableName): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', $this->database)
            ->where('table_name', $tableName)
            ->exists();
    }

    /**
     * Check if a column exists in a table.
     */
    public function columnExists(string $tableName, string $columnName): bool
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', $this->database)
            ->where('table_name', $tableName)
            ->where('column_name', $columnName)
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

