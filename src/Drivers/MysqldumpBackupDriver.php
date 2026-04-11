<?php

namespace Zaeem2396\SchemaLens\Drivers;

use Illuminate\Support\Facades\DB;
use Throwable;
use Zaeem2396\SchemaLens\Contracts\BackupDriverInterface;
use Zaeem2396\SchemaLens\DataTransferObjects\BackupResult;

class MysqldumpBackupDriver implements BackupDriverInterface
{
    public function name(): string
    {
        return 'mysqldump';
    }

    public function supports(): bool
    {
        try {
            $driver = strtolower(DB::connection()->getDriverName());

            return $driver === 'mysql' || $driver === 'mariadb';
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createDatabaseDump(string $outputFile, ?string $connectionName = null): array
    {
        if (! $this->supports()) {
            return BackupResult::failure(
                'mysqldump backup requires a MySQL or MariaDB database connection.'
            );
        }

        $connection = DB::connection($connectionName);
        $config = $connection->getConfig();
        $database = $connection->getDatabaseName();

        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 3306);
        $username = $config['username'] ?? 'root';
        $password = (string) ($config['password'] ?? '');
        $unixSocket = $config['unix_socket'] ?? null;

        $dir = dirname($outputFile);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
            return BackupResult::failure("Cannot create backup directory: {$dir}");
        }

        $binary = $this->resolveMysqldumpBinary();
        if ($binary === null) {
            return BackupResult::failure(
                'mysqldump executable not found. Install MySQL client tools or set SCHEMA_LENS_MYSQLDUMP_PATH.'
            );
        }

        $cmd = [$binary];
        if ($unixSocket) {
            $cmd[] = '--socket='.$unixSocket;
        } else {
            $cmd[] = '--host='.$host;
            $cmd[] = '--port='.(string) $port;
        }
        $cmd[] = '--user='.$username;
        if ($password !== '') {
            $cmd[] = '--password='.$password;
        }
        $cmd[] = '--single-transaction';
        $cmd[] = '--quick';
        $cmd[] = '--lock-tables=false';
        $cmd[] = $database;

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['file', $outputFile, 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptorspec, $pipes, null, null);
        if (! is_resource($process)) {
            return BackupResult::failure('Could not start mysqldump process.');
        }

        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || ! is_file($outputFile) || filesize($outputFile) === 0) {
            $msg = 'mysqldump failed (exit '.$exitCode.')';
            if (is_string($stderr) && $stderr !== '') {
                $msg .= ":\n".trim($stderr);
            }

            return BackupResult::failure($msg);
        }

        return BackupResult::success($outputFile);
    }

    protected function resolveMysqldumpBinary(): ?string
    {
        $configured = config('schema-lens.backup.mysqldump_binary');
        if (is_string($configured) && $configured !== '' && is_executable($configured)) {
            return $configured;
        }

        $envPath = env('SCHEMA_LENS_MYSQLDUMP_PATH');
        if (is_string($envPath) && $envPath !== '' && is_executable($envPath)) {
            return $envPath;
        }

        foreach (['mysqldump', '/usr/bin/mysqldump', '/usr/local/bin/mysqldump'] as $candidate) {
            if ($candidate === 'mysqldump') {
                $which = [];
                @exec('command -v mysqldump 2>/dev/null', $which);
                if (! empty($which[0]) && is_executable($which[0])) {
                    return $which[0];
                }
            } elseif (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
