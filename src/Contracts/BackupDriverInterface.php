<?php

namespace Zaeem2396\SchemaLens\Contracts;

/**
 * Creates a logical backup of the application database (e.g. SQL dump).
 */
interface BackupDriverInterface
{
    /**
     * Driver identifier for configuration (e.g. mysqldump, spatie).
     */
    public function name(): string;

    /**
     * Whether this driver can run in the current environment (driver, binaries, optional packages).
     */
    public function supports(): bool;

    /**
     * Write a database dump to the given filesystem path (directory + filename, or full file path).
     *
     * @return array{success: bool, path?: string, message?: string}
     */
    public function createDatabaseDump(string $outputFile, ?string $connectionName = null): array;
}
