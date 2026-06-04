<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Zaeem2396\SchemaLens\Contracts\SchemaIntrospectionDriverContract;
use Zaeem2396\SchemaLens\Services\Introspection\MySqlInformationSchemaDriver;
use Zaeem2396\SchemaLens\Services\Introspection\PostgresInformationSchemaDriver;

class SchemaIntrospector
{
    protected ?string $connectionName = null;

    protected ?SchemaIntrospectionDriverContract $introspectionDriver = null;

    public function __construct(?string $connectionName = null)
    {
        $this->connectionName = $connectionName;
    }

    /**
     * Laravel database connection used for catalog queries.
     */
    public function getConnection(): Connection
    {
        return $this->connection();
    }

    protected function connection(): Connection
    {
        return DB::connection($this->connectionName);
    }

    protected function driver(): SchemaIntrospectionDriverContract
    {
        return $this->introspectionDriver ??= $this->makeIntrospectionDriver();
    }

    protected function makeIntrospectionDriver(): SchemaIntrospectionDriverContract
    {
        $conn = $this->connection();
        $driver = strtolower($conn->getDriverName());
        if (MySqlInformationSchemaDriver::supports($driver)) {
            return new MySqlInformationSchemaDriver($conn, (string) $conn->getDatabaseName());
        }
        if (PostgresInformationSchemaDriver::supports($driver)) {
            return PostgresInformationSchemaDriver::fromConnection($conn);
        }

        throw new RuntimeException(
            'Schema Lens schema introspection requires MySQL, MariaDB, or PostgreSQL. Current driver: '.$driver.'. '
            .'Use --sql to preview migrations without connecting to the database.'
        );
    }

    /**
     * Whether the backing connection uses the PostgreSQL driver.
     */
    public function isPostgreSql(): bool
    {
        return PostgresInformationSchemaDriver::supports(strtolower($this->connection()->getDriverName()));
    }

    /**
     * Whether the backing connection uses MySQL or MariaDB.
     */
    public function isMysqlFamily(): bool
    {
        return MySqlInformationSchemaDriver::supports(strtolower($this->connection()->getDriverName()));
    }

    /**
     * Get all tables in the database (current MySQL schema or PostgreSQL search_path schema).
     */
    public function getTables(): Collection
    {
        return $this->driver()->getTables();
    }

    /**
     * Get table structure including columns, indexes, foreign keys, etc.
     */
    public function getTableStructure(string $tableName): array
    {
        $drv = $this->driver();

        return [
            'columns' => $drv->getColumns($tableName),
            'indexes' => $drv->getIndexes($tableName),
            'foreign_keys' => $drv->getForeignKeys($tableName),
            'engine' => $drv->getTableEngine($tableName),
            'charset' => $drv->getTableCharset($tableName),
            'collation' => $drv->getTableCollation($tableName),
        ];
    }

    /**
     * Get all columns for a table.
     */
    public function getColumns(string $tableName): Collection
    {
        return $this->driver()->getColumns($tableName);
    }

    /**
     * Get all indexes for a table.
     */
    public function getIndexes(string $tableName): Collection
    {
        return $this->driver()->getIndexes($tableName);
    }

    /**
     * Get all foreign keys for a table.
     */
    public function getForeignKeys(string $tableName): Collection
    {
        return $this->driver()->getForeignKeys($tableName);
    }

    /**
     * Get table engine.
     */
    public function getTableEngine(string $tableName): ?string
    {
        return $this->driver()->getTableEngine($tableName);
    }

    /**
     * Get table charset.
     */
    public function getTableCharset(string $tableName): ?string
    {
        return $this->driver()->getTableCharset($tableName);
    }

    /**
     * Get table collation.
     */
    public function getTableCollation(string $tableName): ?string
    {
        return $this->driver()->getTableCollation($tableName);
    }

    /**
     * Check if a table exists.
     */
    public function tableExists(string $tableName): bool
    {
        return $this->driver()->tableExists($tableName);
    }

    /**
     * Check if a column exists in a table.
     */
    public function columnExists(string $tableName, string $columnName): bool
    {
        return $this->driver()->columnExists($tableName, $columnName);
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
