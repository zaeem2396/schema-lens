<?php

namespace Zaeem2396\SchemaLens\Commands;

use Illuminate\Console\Command;
use Zaeem2396\SchemaLens\Services\BackupManager;
use Zaeem2396\SchemaLens\Services\DataExporter;
use Zaeem2396\SchemaLens\Services\DestructiveChangeDetector;
use Zaeem2396\SchemaLens\Services\DiffGenerator;
use Zaeem2396\SchemaLens\Services\MigrationParser;
use Zaeem2396\SchemaLens\Services\SchemaIntrospector;

class SafeMigrateCommand extends Command
{
    protected $signature = 'migrate:safe 
                            {path? : Path to a specific migration file to run}
                            {--force : Force the operation to run in production}
                            {--seed : Run seeders after migration}
                            {--step : Run migrations one at a time}
                            {--pretend : Dump the SQL queries that would be run}
                            {--no-backup : Skip data backup for destructive changes}
                            {--interactive : Confirm each destructive change individually}
                            {--backup : Create a full database SQL dump (mysqldump) before running migrations}
                            {--backup-path= : Write the full dump to this path (defaults to configured backup directory)}';

    protected $description = 'Run migrations with automatic destructive change detection and data backup';

    protected SchemaIntrospector $introspector;

    protected MigrationParser $parser;

    protected DiffGenerator $diffGenerator;

    protected DestructiveChangeDetector $detector;

    protected DataExporter $exporter;

    protected ?BackupManager $backupManager = null;

    public function __construct(?BackupManager $backupManager = null)
    {
        parent::__construct();

        $this->backupManager = $backupManager;
        $this->introspector = new SchemaIntrospector;
        $this->parser = new MigrationParser;
        $this->diffGenerator = new DiffGenerator($this->introspector);
        $this->detector = new DestructiveChangeDetector;
        $this->exporter = new DataExporter;
    }

    protected function getBackupManager(): BackupManager
    {
        return $this->backupManager ??= new BackupManager;
    }

    public function handle(): int
    {
        $this->info('🔍 Schema Lens - Safe Migration');
        $this->info('================================');
        $this->newLine();

        // Check if a specific migration file is provided
        $specificMigration = $this->argument('path');

        if ($specificMigration) {
            $pendingMigrations = $this->resolveSingleMigration($specificMigration);

            if ($pendingMigrations === null) {
                return Command::FAILURE;
            }
        } else {
            $pendingMigrations = $this->getPendingMigrations();
        }

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

            // Interactive mode: confirm each change individually
            if ($this->option('interactive')) {
                $approvedMigrations = $this->handleInteractiveConfirmation($allDestructiveChanges, $pendingMigrations);

                if (empty($approvedMigrations)) {
                    $this->info('❌ No migrations approved. Migration cancelled.');

                    return Command::FAILURE;
                }

                // Update pending migrations to only approved ones
                $pendingMigrations = $approvedMigrations;

                // Filter destructive changes to only approved migrations
                $allDestructiveChanges = array_intersect_key($allDestructiveChanges, array_flip($approvedMigrations));
            }

            // Export data before proceeding (unless --no-backup)
            if (! $this->option('no-backup') && ! empty($allDestructiveChanges)) {
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

            // Non-interactive mode: single confirmation for all
            if (! $this->option('interactive')) {
                $this->warn('⚠️  The following data may be permanently lost:');
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
        }

        $fullDumpPath = null;
        if ($this->shouldCreateFullDatabaseBackup($allDestructiveChanges)) {
            $this->info('📦 Creating full database dump (mysqldump)...');
            $override = $this->option('backup-path');
            $path = is_string($override) && $override !== '' ? $override : null;
            $result = $this->getBackupManager()->createBackup($path);
            if ($result['success'] !== true) {
                $message = $result['message'] ?? 'unknown error';
                $this->error('Database backup failed: '.$message);

                return Command::FAILURE;
            }
            $fullDumpPath = $result['path'] ?? null;
            if ($fullDumpPath !== null) {
                $this->info('✅ Full database backup: '.$fullDumpPath);
            }
            $this->newLine();
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

        // Use selective migrations for: single file mode, or interactive mode with partial approval
        $useSingleFileMode = $this->argument('path') !== null;
        $useSelectiveMigrations = $useSingleFileMode || ($this->option('interactive') && ! empty($allDestructiveChanges));

        if ($useSelectiveMigrations) {
            $exitCode = $this->runSelectiveMigrations($pendingMigrations, $options);
        } else {
            $exitCode = $this->call('migrate', $options);
        }

        if ($exitCode === 0) {
            $this->newLine();
            $this->info('✅ Migration completed successfully!');

            if (! empty($allDestructiveChanges) && ! $this->option('no-backup')) {
                $this->info('💾 Row-level exports (if any) are under: storage/app/schema-lens/exports/');
            }
            if ($fullDumpPath !== null) {
                $this->info('📦 Full SQL dump: '.$fullDumpPath);
            }
        }

        return $exitCode;
    }

    /**
     * Whether to run mysqldump (or configured driver) before migrations.
     *
     * @param  array<string, array<int, mixed>>  $allDestructiveChanges
     */
    protected function shouldCreateFullDatabaseBackup(array $allDestructiveChanges): bool
    {
        if ($this->option('pretend')) {
            return false;
        }
        if ($this->option('no-backup')) {
            return false;
        }
        if ($this->option('backup')) {
            return true;
        }

        return (bool) config('schema-lens.backup.auto', false) && ! empty($allDestructiveChanges);
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

    /**
     * Handle interactive confirmation for each destructive change.
     *
     * @return array Approved migration file paths
     */
    protected function handleInteractiveConfirmation(array $allDestructiveChanges, array $pendingMigrations): array
    {
        $approvedMigrations = [];
        $skipAll = false;
        $approveAll = false;

        $this->info('🔄 Interactive Mode: Review each destructive change');
        $this->line('   Options: [y]es, [n]o, [a]ll (approve remaining), [s]kip all, [q]uit');
        $this->newLine();

        foreach ($pendingMigrations as $migrationFile) {
            $migrationName = basename($migrationFile);

            // Check if this migration has destructive changes
            if (! isset($allDestructiveChanges[$migrationFile])) {
                // No destructive changes - auto-approve
                $approvedMigrations[] = $migrationFile;
                $this->line("  ✅ {$migrationName} - No destructive changes (auto-approved)");

                continue;
            }

            // If approve all was selected, auto-approve
            if ($approveAll) {
                $approvedMigrations[] = $migrationFile;
                $this->line("  ✅ {$migrationName} - Auto-approved");

                continue;
            }

            // If skip all was selected, skip
            if ($skipAll) {
                $this->line("  ⏭️  {$migrationName} - Skipped");

                continue;
            }

            $changes = $allDestructiveChanges[$migrationFile];

            $this->newLine();
            $this->warn("📋 Migration: {$migrationName}");
            $this->line('   Destructive changes:');

            foreach ($changes as $change) {
                $op = $change['operation'];
                $risk = strtoupper($change['risk_level']);
                $icon = $risk === 'CRITICAL' ? '🔴' : ($risk === 'HIGH' ? '🟠' : '🟡');

                $this->line("   {$icon} [{$risk}] {$op['type']}::{$op['action']}");

                if (! empty($change['affected_tables'])) {
                    $this->line('      Tables: '.implode(', ', $change['affected_tables']));
                }

                if (! empty($change['affected_columns'])) {
                    $cols = array_map(function ($col) {
                        return ($col['table'] ?? '').'.'.($col['column'] ?? '');
                    }, $change['affected_columns']);
                    $this->line('      Columns: '.implode(', ', $cols));
                }
            }

            $this->newLine();
            $response = $this->askWithOptions($migrationName);

            switch (strtolower($response)) {
                case 'y':
                case 'yes':
                    $approvedMigrations[] = $migrationFile;
                    $this->info("  ✅ Approved: {$migrationName}");
                    break;

                case 'n':
                case 'no':
                    $this->warn("  ⏭️  Skipped: {$migrationName}");
                    break;

                case 'a':
                case 'all':
                    $approveAll = true;
                    $approvedMigrations[] = $migrationFile;
                    $this->info("  ✅ Approved: {$migrationName}");
                    $this->info('  ℹ️  All remaining migrations will be auto-approved');
                    break;

                case 's':
                case 'skip':
                    $skipAll = true;
                    $this->warn("  ⏭️  Skipped: {$migrationName}");
                    $this->warn('  ℹ️  All remaining migrations will be skipped');
                    break;

                case 'q':
                case 'quit':
                    $this->info('  ❌ Quitting interactive mode.');

                    return [];

                default:
                    // Default to no
                    $this->warn("  ⏭️  Skipped: {$migrationName} (invalid input)");
                    break;
            }
        }

        $this->newLine();
        $this->info('📊 Interactive Review Complete');
        $this->line('   Approved: '.count($approvedMigrations).' migration(s)');
        $this->line('   Skipped: '.(count($pendingMigrations) - count($approvedMigrations)).' migration(s)');
        $this->newLine();

        if (! empty($approvedMigrations) && ! $this->confirm('Proceed with approved migrations?', true)) {
            return [];
        }

        return $approvedMigrations;
    }

    /**
     * Ask for user input with available options.
     */
    protected function askWithOptions(string $migrationName): string
    {
        return $this->ask(
            "   Approve '{$migrationName}'? [y/n/a/s/q]",
            'n'
        );
    }

    /**
     * Run only specific migrations (used in interactive mode).
     */
    protected function runSelectiveMigrations(array $migrationFiles, array $options): int
    {
        $exitCode = Command::SUCCESS;

        foreach ($migrationFiles as $path) {
            $migrationName = $this->getMigrationName($path);
            $this->line("  Running: {$migrationName}");

            try {
                // Get the relative path from database/migrations
                $relativePath = $this->getRelativeMigrationPath($path);

                $result = $this->call('migrate', array_merge($options, [
                    '--path' => $relativePath,
                ]));

                if ($result !== 0) {
                    $exitCode = $result;
                    break; // Stop on first error
                }
            } catch (\Exception $e) {
                $this->error("  Error running {$migrationName}: ".$e->getMessage());
                $exitCode = Command::FAILURE;
                break;
            }
        }

        return $exitCode;
    }

    /**
     * Get relative migration path for Laravel's migrate command.
     */
    protected function getRelativeMigrationPath(string $absolutePath): string
    {
        $basePath = base_path();

        // If the path starts with base_path, make it relative
        if (str_starts_with($absolutePath, $basePath)) {
            return ltrim(str_replace($basePath, '', $absolutePath), DIRECTORY_SEPARATOR);
        }

        // If it's an absolute path outside the project, use realpath flag
        return $absolutePath;
    }

    /**
     * Resolve and validate a single migration file path.
     *
     * @param  string  $path  The migration file path (relative or absolute)
     * @return array|null Array with single migration path, or null on failure
     */
    protected function resolveSingleMigration(string $path): ?array
    {
        // Resolve to absolute path
        $absolutePath = $this->resolveAbsolutePath($path);

        // Check if file exists
        if (! file_exists($absolutePath)) {
            $this->error("❌ Migration file not found: {$path}");
            $this->line('');
            $this->line('  Make sure the path is correct. Examples:');
            $this->line('  - database/migrations/2024_01_15_create_posts_table.php');
            $this->line('  - /var/www/app/database/migrations/2024_01_15_create_posts_table.php');

            return null;
        }

        // Verify it's a PHP file
        if (pathinfo($absolutePath, PATHINFO_EXTENSION) !== 'php') {
            $this->error("❌ Invalid migration file: {$path}");
            $this->line('  Migration files must have a .php extension.');

            return null;
        }

        // Check if this migration is pending (not already run)
        $migrator = app('migrator');
        $ran = $migrator->getRepository()->getRan();
        $migrationName = $this->getMigrationName($absolutePath);

        if (in_array($migrationName, $ran)) {
            $this->warn("⚠️  Migration already executed: {$migrationName}");
            $this->line('');
            $this->line('  This migration has already been run.');
            $this->line('  Use `php artisan migrate:rollback` to undo it first if needed.');

            return null;
        }

        $this->info("📄 Single migration mode: {$migrationName}");
        $this->newLine();

        return [$absolutePath];
    }

    /**
     * Resolve a path to an absolute path.
     */
    protected function resolveAbsolutePath(string $path): string
    {
        // If already absolute, return as-is
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path)) {
            return $path;
        }

        // Resolve relative to base path
        return base_path($path);
    }

    /**
     * Extract migration name from file path.
     */
    protected function getMigrationName(string $path): string
    {
        return str_replace('.php', '', basename($path));
    }
}
