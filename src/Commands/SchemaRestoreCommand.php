<?php

namespace Zaeem2396\SchemaLens\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SchemaRestoreCommand extends Command
{
    protected $signature = 'schema:restore
                            {file : Path to a .sql dump produced by mysqldump (absolute or relative to base path)}
                            {--connection= : Laravel database connection name to restore into (default connection if omitted)}';

    protected $description = 'Show how to restore a SQL backup into MySQL (does not execute restore by default)';

    public function handle(): int
    {
        $raw = (string) $this->argument('file');
        $path = File::exists($raw) ? $raw : base_path($raw);
        if (! File::exists($path)) {
            $this->error("File not found: {$raw}");

            return self::FAILURE;
        }

        $full = realpath($path);
        if ($full === false) {
            $this->error("Could not resolve path: {$path}");

            return self::FAILURE;
        }
        $connectionName = $this->option('connection');
        $config = config('database.connections.'.($connectionName ?: config('database.default')));
        if (! is_array($config)) {
            $this->error('Invalid database connection.');

            return self::FAILURE;
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 3306);
        $user = $config['username'] ?? 'root';
        $database = $config['database'] ?? '';

        $this->warn('Schema Lens does not execute restores automatically (safety). Use mysql client:');
        $this->newLine();
        $this->line('  mysql --host='.escapeshellarg((string) $host)
            .' --port='.$port
            .' --user='.escapeshellarg((string) $user)
            .' -p '.escapeshellarg((string) $database)
            .' < '.escapeshellarg((string) $full));
        $this->newLine();
        $this->line('You will be prompted for the database password. Review the dump before applying on production.');

        return self::SUCCESS;
    }
}
