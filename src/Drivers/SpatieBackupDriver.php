<?php

namespace Zaeem2396\SchemaLens\Drivers;

use Zaeem2396\SchemaLens\Contracts\BackupDriverInterface;
use Zaeem2396\SchemaLens\DataTransferObjects\BackupResult;

/**
 * Optional integration with spatie/laravel-backup when installed in the host application.
 */
class SpatieBackupDriver implements BackupDriverInterface
{
    public function name(): string
    {
        return 'spatie';
    }

    public function supports(): bool
    {
        return class_exists('Spatie\\Backup\\Commands\\BackupCommand');
    }

    /**
     * {@inheritdoc}
     */
    public function createDatabaseDump(string $outputFile, ?string $connectionName = null): array
    {
        if (! $this->supports()) {
            return BackupResult::failure(
                'spatie/laravel-backup is not installed. Run composer require spatie/laravel-backup or use backup_driver=mysqldump.'
            );
        }

        return BackupResult::failure(
            'Spatie backup driver is registered for future use. Use backup_driver=mysqldump for a portable SQL dump, or run php artisan backup:run from the Spatie package.'
        );
    }
}
