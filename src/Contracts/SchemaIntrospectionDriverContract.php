<?php

namespace Zaeem2396\SchemaLens\Contracts;

use Illuminate\Support\Collection;

interface SchemaIntrospectionDriverContract
{
    public static function supports(string $driverName): bool;

    public function getTables(): Collection;

    /**
     * @return Collection<int, array{name: string, type: string, data_type: string, nullable: bool, default: mixed, extra: string, comment: string}>
     */
    public function getColumns(string $tableName): Collection;

    /**
     * @return Collection<int, array{name: string, columns: list<string>, unique: bool, type: string}>
     */
    public function getIndexes(string $tableName): Collection;

    /**
     * @return Collection<int, array{name: string, columns: list<string>, referenced_table: string, referenced_columns: list<string>, on_update: string, on_delete: string}>
     */
    public function getForeignKeys(string $tableName): Collection;

    public function getTableEngine(string $tableName): ?string;

    public function getTableCharset(string $tableName): ?string;

    public function getTableCollation(string $tableName): ?string;

    public function tableExists(string $tableName): bool;

    public function columnExists(string $tableName, string $columnName): bool;
}
