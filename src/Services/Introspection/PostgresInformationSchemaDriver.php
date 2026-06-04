<?php

namespace Zaeem2396\SchemaLens\Services\Introspection;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Zaeem2396\SchemaLens\Contracts\SchemaIntrospectionDriverContract;

class PostgresInformationSchemaDriver implements SchemaIntrospectionDriverContract
{
    public function __construct(
        protected PostgresCatalogScope $scope
    ) {}

    public static function fromConnection(Connection $connection): self
    {
        return new self(PostgresCatalogScope::fromConnection($connection));
    }

    public static function supports(string $driverName): bool
    {
        return strtolower($driverName) === 'pgsql';
    }

    public function getTables(): Collection
    {
        return $this->connection()->table('information_schema.tables')
            ->whereRaw('LOWER(table_catalog) = ?', [$this->scope->normalizedCatalog()])
            ->whereRaw('LOWER(table_schema) = ?', [$this->scope->normalizedSchema()])
            ->where('table_type', 'BASE TABLE')
            ->orderBy('table_name')
            ->pluck('table_name');
    }

    public function getColumns(string $tableName): Collection
    {
        return $this->connection()->table('information_schema.columns')
            ->whereRaw('LOWER(table_catalog) = ?', [$this->scope->normalizedCatalog()])
            ->whereRaw('LOWER(table_schema) = ?', [$this->scope->normalizedSchema()])
            ->whereRaw('LOWER(table_name) = ?', [strtolower($tableName)])
            ->orderBy('ordinal_position')
            ->get()
            ->map(function ($column) {
                $dataType = strtolower((string) $this->columnAttr($column, 'data_type'));
                $udt = strtolower((string) $this->columnAttr($column, 'udt_name'));
                $default = $this->columnAttr($column, 'column_default');

                return [
                    'name' => (string) $this->columnAttr($column, 'column_name'),
                    'type' => PostgresColumnTypeFormatter::format($column),
                    'data_type' => $dataType !== '' ? $dataType : $udt,
                    'nullable' => strtoupper((string) $this->columnAttr($column, 'is_nullable')) === 'YES',
                    'default' => $default,
                    'extra' => PostgresColumnTypeFormatter::extraFromDefault($default),
                    'comment' => '',
                ];
            });
    }

    public function getIndexes(string $tableName): Collection
    {
        $rows = $this->connection()->select(
            <<<'SQL'
            SELECT
                ix.relname AS index_name,
                bool_or(ind.indisunique) AS is_unique,
                bool_or(ind.indisprimary) AS is_primary,
                am.amname AS index_method,
                string_agg(att.attname::text, ',' ORDER BY ar.ordinality) AS column_names
            FROM pg_class tbl
            JOIN pg_namespace ns ON ns.oid = tbl.relnamespace
            JOIN pg_index ind ON ind.indrelid = tbl.oid
            JOIN pg_class ix ON ix.oid = ind.indexrelid
            JOIN pg_am am ON am.oid = ix.relam
            JOIN LATERAL unnest(ind.indkey) WITH ORDINALITY AS ar(attnum, ordinality) ON true
            JOIN pg_attribute att ON att.attrelid = tbl.oid AND att.attnum = ar.attnum AND att.attnum > 0
            WHERE tbl.relkind = 'r'
              AND ns.nspname = ?
              AND tbl.relname = ?
              AND ix.relname NOT LIKE 'pg_%'
            GROUP BY ix.relname, am.amname
            ORDER BY ix.relname
            SQL,
            [$this->scope->schemaName, $tableName]
        );

        return collect($rows)
            ->map(function ($row) {
                $cols = array_values(array_filter(explode(',', $row->column_names ?? '')));

                $unique = filter_var($row->is_unique, FILTER_VALIDATE_BOOLEAN);
                if ($row->is_unique === true || $row->is_unique === 't') {
                    $unique = true;
                }

                $primary = filter_var($row->is_primary, FILTER_VALIDATE_BOOLEAN);
                if ($row->is_primary === true || $row->is_primary === 't') {
                    $primary = true;
                }

                return [
                    'name' => $row->index_name,
                    'columns' => $cols,
                    'unique' => $unique,
                    'primary' => $primary,
                    'type' => (string) $row->index_method,
                ];
            })
            ->filter(fn (array $index) => $index['columns'] !== [] || $index['primary'])
            ->values();
    }

    public function getForeignKeys(string $tableName): Collection
    {
        // PostgreSQL exposes referenced columns via constraint_column_usage, not key_column_usage.
        $rows = $this->connection()->select(
            <<<'SQL'
            SELECT
                tc.constraint_name AS constraint_name,
                kcu.column_name AS column_name,
                ccu.table_name AS referenced_table_name,
                ccu.column_name AS referenced_column_name,
                rc.update_rule AS update_rule,
                rc.delete_rule AS delete_rule
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
              ON tc.constraint_catalog = kcu.constraint_catalog
             AND tc.constraint_schema = kcu.constraint_schema
             AND tc.constraint_name = kcu.constraint_name
             AND tc.table_catalog = kcu.table_catalog
             AND tc.table_schema = kcu.table_schema
             AND tc.table_name = kcu.table_name
            JOIN information_schema.referential_constraints AS rc
              ON rc.constraint_catalog = tc.constraint_catalog
             AND rc.constraint_schema = tc.constraint_schema
             AND rc.constraint_name = tc.constraint_name
            JOIN LATERAL (
                SELECT ccu.table_name, ccu.column_name
                FROM information_schema.constraint_column_usage AS ccu
                WHERE ccu.constraint_catalog = tc.constraint_catalog
                  AND ccu.constraint_schema = tc.constraint_schema
                  AND ccu.constraint_name = tc.constraint_name
                ORDER BY ccu.column_name
                OFFSET GREATEST(COALESCE(kcu.position_in_unique_constraint, kcu.ordinal_position) - 1, 0)
                LIMIT 1
            ) AS ccu ON true
            WHERE tc.constraint_type = 'FOREIGN KEY'
              AND LOWER(tc.constraint_catalog) = ?
              AND LOWER(tc.table_schema) = ?
              AND LOWER(tc.table_name) = ?
            ORDER BY tc.constraint_name, kcu.ordinal_position
            SQL,
            [
                $this->scope->normalizedCatalog(),
                $this->scope->normalizedSchema(),
                strtolower($tableName),
            ]
        );

        return collect($rows)
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
            })
            ->values();
    }

    public function getTableEngine(string $tableName): ?string
    {
        return null;
    }

    public function getTableCharset(string $tableName): ?string
    {
        return null;
    }

    public function getTableCollation(string $tableName): ?string
    {
        return null;
    }

    public function tableExists(string $tableName): bool
    {
        return $this->connection()->table('information_schema.tables')
            ->whereRaw('LOWER(table_catalog) = ?', [$this->scope->normalizedCatalog()])
            ->whereRaw('LOWER(table_schema) = ?', [$this->scope->normalizedSchema()])
            ->whereRaw('LOWER(table_name) = ?', [strtolower($tableName)])
            ->where('table_type', 'BASE TABLE')
            ->exists();
    }

    public function columnExists(string $tableName, string $columnName): bool
    {
        return $this->connection()->table('information_schema.columns')
            ->whereRaw('LOWER(table_catalog) = ?', [$this->scope->normalizedCatalog()])
            ->whereRaw('LOWER(table_schema) = ?', [$this->scope->normalizedSchema()])
            ->whereRaw('LOWER(table_name) = ?', [strtolower($tableName)])
            ->whereRaw('LOWER(column_name) = ?', [strtolower($columnName)])
            ->exists();
    }

    protected function connection(): Connection
    {
        return $this->scope->connection;
    }

    /**
     * @param  object|array<string,mixed>  $column
     */
    protected function columnAttr(object|array $column, string $key): mixed
    {
        if (is_array($column)) {
            foreach ($column as $k => $value) {
                if (strcasecmp((string) $k, $key) === 0) {
                    return $value;
                }
            }

            return null;
        }

        foreach (array_keys(get_object_vars($column)) as $prop) {
            if (strcasecmp((string) $prop, $key) === 0) {
                return $column->{$prop};
            }
        }

        return null;
    }
}
