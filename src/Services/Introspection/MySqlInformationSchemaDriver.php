<?php

namespace Zaeem2396\SchemaLens\Services\Introspection;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Zaeem2396\SchemaLens\Contracts\SchemaIntrospectionDriverContract;

class MySqlInformationSchemaDriver implements SchemaIntrospectionDriverContract
{
    public function __construct(
        protected Connection $connection,
        protected string $database
    ) {}

    public static function supports(string $driverName): bool
    {
        $d = strtolower($driverName);

        return in_array($d, ['mysql', 'mariadb'], true);
    }

    public function getTables(): Collection
    {
        return $this->connection->table('information_schema.tables')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_TYPE', 'BASE TABLE')
            ->pluck('TABLE_NAME');
    }

    public function getColumns(string $tableName): Collection
    {
        return $this->connection->table('information_schema.columns')
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
    }

    public function getIndexes(string $tableName): Collection
    {
        $indexes = $this->connection->table('information_schema.statistics')
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

    public function getForeignKeys(string $tableName): Collection
    {
        $foreignKeys = $this->connection->table('information_schema.key_column_usage as kcu')
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

    public function getTableEngine(string $tableName): ?string
    {
        return $this->connection->table('information_schema.tables')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_NAME', $tableName)
            ->value('ENGINE');
    }

    public function getTableCharset(string $tableName): ?string
    {
        $result = $this->connection->table('information_schema.tables')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_NAME', $tableName)
            ->value('TABLE_COLLATION');

        return $result ? explode('_', $result)[0] : null;
    }

    public function getTableCollation(string $tableName): ?string
    {
        return $this->connection->table('information_schema.tables')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_NAME', $tableName)
            ->value('TABLE_COLLATION');
    }

    public function tableExists(string $tableName): bool
    {
        return $this->connection->table('information_schema.tables')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_NAME', $tableName)
            ->exists();
    }

    public function columnExists(string $tableName, string $columnName): bool
    {
        return $this->connection->table('information_schema.columns')
            ->where('TABLE_SCHEMA', $this->database)
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->exists();
    }
}
