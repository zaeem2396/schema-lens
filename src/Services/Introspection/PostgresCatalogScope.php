<?php

namespace Zaeem2396\SchemaLens\Services\Introspection;

use Illuminate\Database\Connection;

/**
 * Normalized PostgreSQL catalog + schema scope for information_schema queries.
 */
final class PostgresCatalogScope
{
    public function __construct(
        public readonly Connection $connection,
        public readonly string $schemaName,
    ) {}

    public static function fromConnection(Connection $connection): self
    {
        $schema = (string) ($connection->getConfig('schema') ?? 'public');

        return new self($connection, $schema);
    }

    public function catalogName(): string
    {
        return (string) $this->connection->getDatabaseName();
    }

    public function normalizedCatalog(): string
    {
        return strtolower($this->catalogName());
    }

    public function normalizedSchema(): string
    {
        return strtolower($this->schemaName);
    }
}
