<?php

namespace Zaeem2396\SchemaLens\Commands;

use Illuminate\Console\Command;
use Zaeem2396\SchemaLens\Services\DataExporter;
use Zaeem2396\SchemaLens\Services\DestructiveChangeDetector;
use Zaeem2396\SchemaLens\Services\DiffGenerator;
use Zaeem2396\SchemaLens\Services\MigrationParser;
use Zaeem2396\SchemaLens\Services\SchemaIntrospector;

class SafeMigrateCommand extends Command
{
    protected $signature = 'migrate:safe 
                            {--force : Force the operation to run in production}
                            {--seed : Run seeders after migration}
                            {--step : Run migrations one at a time}
                            {--pretend : Dump the SQL queries that would be run}
                            {--no-backup : Skip data backup for destructive changes}';

    protected $description = 'Run migrations with automatic destructive change detection and data backup';

    protected SchemaIntrospector $introspector;

    protected MigrationParser $parser;

    protected DiffGenerator $diffGenerator;

    protected DestructiveChangeDetector $detector;

    protected DataExporter $exporter;

    public function __construct()
    {
        parent::__construct();

        $this->introspector = new SchemaIntrospector;
        $this->parser = new MigrationParser;
        $this->diffGenerator = new DiffGenerator($this->introspector);
        $this->detector = new DestructiveChangeDetector;
        $this->exporter = new DataExporter;
    }

    public function handle(): int
    {
        $this->info('🔍 Schema Lens - Safe Migration');
        $this->info('================================');
        $this->newLine();

        // Get pending migrations
        $pendingMigrations = $this->getPendingMigrations();

        if (empty($pendingMigrations)) {
            $this->info('✅ Nothing to migrate.');

            return Command::SUCCESS;
        }

        $this->info('Found '.count($pendingMigrations).' pending migration(s):');
        foreach ($pendingMigrations as $migration) {
            $this->line("  - {$migration}");
        }
        $this->newLine();

        // Analyze each migration for destructive changes
        $allDestructiveChanges = [];
        $migrationAnalysis = [];

        foreach ($pendingMigrations as $migrationFile) {
            $this->info('📋 Analyzing: '.basename($migrationFile));

            $analysis = $this->analyzeMigration($migrationFile);
            $migrationAnalysis[$migrationFile] = $analysis;

            if (! empty($analysis['destructive_changes'])) {
                $allDestructiveChanges[$migrationFile] = $analysis['destructive_changes'];
                $this->displayDestructiveChanges($analysis['destructive_changes']);
            } else {
                $this->info('  ✅ No destructive changes detected');
            }
            $this->newLine();
        }

        // If destructive changes found, warn and confirm
        if (! empty($allDestructiveChanges)) {
            $this->newLine();
            $this->error('╔══════════════════════════════════════════════════════════════╗');
            $this->error('║           ⚠️  DESTRUCTIVE CHANGES DETECTED!                  ║');
            $this->error('╚══════════════════════════════════════════════════════════════╝');
            $this->newLine();

            $totalDestructive = array_sum(array_map('count', $allDestructiveChanges));
            $this->warn("Total destructive operations: {$totalDestructive}");
            $this->newLine();

            // Export data before proceeding (unless --no-backup)
            if (! $this->option('no-backup')) {
                $this->info('💾 Creating backup of affected data...');
                foreach ($allDestructiveChanges as $migrationFile => $changes) {
                    $exports = $this->exporter->exportDestructiveChanges($changes, $migrationFile);
                    if (! empty($exports)) {
                        foreach ($exports as $export) {
                            $this->line("  📁 Exported: {$export['table']} → {$export['export_path']}");
                        }
                    }
                }
                $this->info('✅ Backup completed');
                $this->newLine();
            }

            // Ask for confirmation
            $this->warn('The following data may be permanently lost:');
            foreach ($allDestructiveChanges as $migrationFile => $changes) {
                $this->line('  '.basename($migrationFile).':');
                foreach ($changes as $change) {
                    $op = $change['operation'];
                    $tables = implode(', ', $change['affected_tables'] ?? []);
                    $this->line("    - {$op['type']}::{$op['action']} on {$tables}");
                }
            }
            $this->newLine();

            if (! $this->confirm('⚠️  Do you want to proceed with migration?', false)) {
                $this->info('❌ Migration cancelled by user.');

                return Command::FAILURE;
            }
        }

        // Run the actual migration
        $this->newLine();
        $this->info('🚀 Running migrations...');
        $this->newLine();

        $options = [
            '--force' => $this->option('force'),
            '--seed' => $this->option('seed'),
            '--step' => $this->option('step'),
            '--pretend' => $this->option('pretend'),
        ];

        // Filter out false/null options
        $options = array_filter($options);

        $exitCode = $this->call('migrate', $options);

        if ($exitCode === 0) {
            $this->newLine();
            $this->info('✅ Migration completed successfully!');

            if (! empty($allDestructiveChanges) && ! $this->option('no-backup')) {
                $this->info('💾 Your data backups are available in: storage/app/schema-lens/exports/');
            }
        }

        return $exitCode;
    }

    /**
     * Get list of pending migration files.
     */
    protected function getPendingMigrations(): array
    {
        $migrator = app('migrator');
        $migrationsPath = database_path('migrations');

        // Get all migration files
        $files = $migrator->getMigrationFiles($migrationsPath);

        // Get already run migrations
        $ran = $migrator->getRepository()->getRan();

        // Filter to get only pending migrations
        $pending = [];
        foreach ($files as $name => $path) {
            if (! in_array($name, $ran)) {
                $pending[] = $path;
            }
        }

        return $pending;
    }

    /**
     * Analyze a single migration for destructive changes.
     */
    protected function analyzeMigration(string $migrationFile): array
    {
        try {
            // Parse migration
            $this->parser->parse($migrationFile);
            $upOperations = $this->parser->getOperations('up');

            // Get current schema
            $currentSchema = $this->introspector->getCurrentSchema();

            // Generate diff
            $diff = $this->diffGenerator->generateDiff($upOperations->toArray(), $currentSchema);

            // Detect destructive changes
            $destructiveChanges = $this->detector->detect($upOperations);

            return [
                'diff' => $diff,
                'destructive_changes' => $destructiveChanges->toArray(),
                'operations' => $upOperations->toArray(),
            ];
        } catch (\Exception $e) {
            $this->error('  ❌ Error analyzing migration: '.$e->getMessage());

            return [
                'diff' => [],
                'destructive_changes' => [],
                'operations' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Display destructive changes for a migration.
     */
    protected function displayDestructiveChanges(array $changes): void
    {
        foreach ($changes as $change) {
            $op = $change['operation'];
            $risk = strtoupper($change['risk_level']);
            $icon = $risk === 'CRITICAL' ? '🔴' : ($risk === 'HIGH' ? '🟠' : '🟡');

            $this->line("  {$icon} [{$risk}] {$op['type']}::{$op['action']}");

            if (! empty($change['affected_tables'])) {
                $this->line('     Tables: '.implode(', ', $change['affected_tables']));
            }

            if (! empty($change['affected_columns'])) {
                $cols = array_map(function ($col) {
                    return ($col['table'] ?? '').'.'.($col['column'] ?? '');
                }, $change['affected_columns']);
                $this->line('     Columns: '.implode(', ', $cols));
            }
        }
    }
}
