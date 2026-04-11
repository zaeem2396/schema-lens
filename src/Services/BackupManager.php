<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Support\Facades\File;
use Zaeem2396\SchemaLens\Contracts\BackupDriverInterface;
use Zaeem2396\SchemaLens\DataTransferObjects\BackupResult;
use Zaeem2396\SchemaLens\Drivers\MysqldumpBackupDriver;
use Zaeem2396\SchemaLens\Drivers\SpatieBackupDriver;

class BackupManager
{
    /**
     * @return array{success: bool, path?: string, message?: string}
     */
    public function createBackup(?string $overridePath = null, ?string $connectionName = null): array
    {
        $driver = $this->resolveDriver();

        if (! $driver->supports()) {
            return BackupResult::failure(
                'Backup driver "'.$driver->name().'" is not available in this environment.'
            );
        }

        $path = $overridePath ?? $this->defaultBackupFilePath();

        return $driver->createDatabaseDump($path, $connectionName);
    }

    /**
     * Remove backup files in the backup directory older than the configured retention period.
     */
    public function pruneOldBackups(?string $backupDirectory = null, ?int $retentionDays = null): void
    {
        $dir = $backupDirectory ?? $this->backupDirectory();
        $days = $retentionDays ?? (int) config('schema-lens.backup.retention_days', 7);
        if ($days <= 0 || ! is_dir($dir)) {
            return;
        }

        $threshold = time() - ($days * 86400);
        $files = File::glob($dir.'/schema-lens-db-*.sql') ?: [];
        foreach ($files as $file) {
            if (is_file($file) && @filemtime($file) !== false && filemtime($file) < $threshold) {
                @unlink($file);
            }
        }
    }

    public function backupDirectory(): string
    {
        $relative = (string) config('schema-lens.backup.directory', 'app/schema-lens/backups');

        return storage_path($relative);
    }

    protected function defaultBackupFilePath(): string
    {
        $this->pruneOldBackups();

        $name = 'schema-lens-db-'.date('Y-m-d_His').'.sql';

        return $this->backupDirectory().'/'.$name;
    }

    protected function resolveDriver(): BackupDriverInterface
    {
        $name = strtolower((string) config('schema-lens.backup.driver', 'mysqldump'));

        return match ($name) {
            'spatie' => new SpatieBackupDriver,
            default => new MysqldumpBackupDriver,
        };
    }
}
