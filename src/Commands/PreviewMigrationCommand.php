<?php

namespace Zaeem2396\SchemaLens\Commands;

use Illuminate\Console\Command as BaseCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Zaeem2396\SchemaLens\Formatters\CliFormatter;
use Zaeem2396\SchemaLens\Formatters\JsonFormatter;
use Zaeem2396\SchemaLens\Services\DataExporter;
use Zaeem2396\SchemaLens\Services\DestructiveChangeDetector;
use Zaeem2396\SchemaLens\Services\DiffGenerator;
use Zaeem2396\SchemaLens\Services\MigrationParser;
use Zaeem2396\SchemaLens\Services\RollbackSimulator;
use Zaeem2396\SchemaLens\Services\SchemaIntrospector;
use Zaeem2396\SchemaLens\Services\SqlGenerator;

/**
 * Preview Migration Command
 *
 * @method string argument(string $key, mixed $default = null)
 * @method mixed option(string $key, mixed $default = null)
 * @method void info(string $string)
 * @method void error(string $string)
 * @method void warn(string $string)
 * @method void line(string $string)
 * @method void newLine(int $count = 1)
 */
class PreviewMigrationCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:preview 
                            {migration : Path to the migration file to preview}
                            {--format=cli : Output format (cli or json)}
                            {--export-path= : Custom path for exports}
                            {--no-export : Skip data export even if destructive changes are detected}
                            {--output= : Output file path for sql format}
                            {--sql : Generate SQL statements instead of diff analysis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Preview a migration file against the current MySQL schema';

    protected SchemaIntrospector $introspector;

    protected MigrationParser $parser;

    protected DiffGenerator $diffGenerator;

    protected DestructiveChangeDetector $detector;

    protected DataExporter $exporter;

    protected RollbackSimulator $rollbackSimulator;

    protected SqlGenerator $sqlGenerator;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->introspector = new SchemaIntrospector;
        $this->parser = new MigrationParser;
        $this->diffGenerator = new DiffGenerator($this->introspector);
        $this->detector = new DestructiveChangeDetector;
        $this->exporter = new DataExporter;
        $this->rollbackSimulator = new RollbackSimulator($this->introspector, $this->parser);
        $this->sqlGenerator = new SqlGenerator;
    }

    /**
     * Execute the console command.
     */
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $migrationPath = $this->argument('migration');
        $format = $this->option('sql') ? 'sql' : ($this->option('format') ?? Config::get('schema-lens.output.format', 'cli'));

        // Resolve migration file path
        $migrationFile = $this->resolveMigrationPath($migrationPath);

        if (! $migrationFile || ! File::exists($migrationFile)) {
            $this->error("Migration file not found: {$migrationPath}");

            return \Illuminate\Console\Command::FAILURE;
        }

        $this->info("Analyzing migration: {$migrationFile}");
        $this->newLine();

        try {
            // Parse migration
            $this->info('Parsing migration file...');
            $parsed = $this->parser->parse($migrationFile);
            $upOperations = $this->parser->getOperations('up');

            // SQL format - generate ad output SQL
            if ($format === 'sql') {
                return $this->handleSqlFormat($upOperations, $migrationFile);
            }

            // Get current schema
            $this->info('Introspecting current database schema...');
            $currentSchema = $this->introspector->getCurrentSchema();

            // Generate diff
            $this->info('Generating schema diff...');
            $diff = $this->diffGenerator->generateDiff($upOperations->toArray(), $currentSchema);

            // Detect destructive changes
            $this->info('Detecting destructive changes...');
            $destructiveChanges = $this->detector->detect($upOperations);

            // Export data if destructive changes found
            $exports = [];
            if (! $this->option('no-export') && $destructiveChanges->isNotEmpty()) {
                $this->warn('⚠️  Destructive changes detected! Exporting data...');
                $exports = $this->exporter->exportDestructiveChanges(
                    $destructiveChanges->toArray(),
                    $migrationFile
                );
                $this->info('✓ Data exported successfully');
            }

            // Simulate rollback
            $this->info('Simulating rollback...');
            $rollback = $this->rollbackSimulator->simulate($migrationFile);

            // Format output
            $formatter = $format === 'json' ? new JsonFormatter : new CliFormatter;
            $output = $formatter->format(
                $diff,
                $destructiveChanges->toArray(),
                $rollback,
                $exports
            );

            // Display or save output
            if ($format === 'json') {
                $exportPath = $this->option('export-path');
                if ($exportPath) {
                    $outputFile = $exportPath.'/report.json';
                } else {
                    // Use Laravel's storage path helper if available, otherwise construct path
                    /** @var callable|null $storagePathFunc */
                    $storagePathFunc = function_exists('storage_path') ? 'storage_path' : null;
                    $outputFile = $storagePathFunc
                        ? $storagePathFunc('app/schema-lens/report.json')
                        : getcwd().'/storage/app/schema-lens/report.json';
                }

                File::ensureDirectoryExists(dirname($outputFile));
                File::put($outputFile, $output);
                $this->info("JSON report saved to: {$outputFile}");
            } else {
                $this->line($output);
            }

            // Exit code based on destructive changes
            if ($destructiveChanges->isNotEmpty()) {
                $this->newLine();
                $this->warn('⚠️  Migration contains destructive changes!');

                return \Illuminate\Console\Command::FAILURE;
            }

            return \Illuminate\Console\Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return \Illuminate\Console\Command::FAILURE;
        }
    }

    /**
     * Handle SQL format output.
     */
    protected function handleSqlFormat(\Illuminate\Support\Collection $operations, string $migrationFile): int
    {
        $this->info('Generating SQL statements...');
        $this->newLine();

        $statements = $this->sqlGenerator->generate($operations);

        if (empty($statements)) {
            $this->warn('No SQL statements generated. The migration may not contain schema operations.');

            return \Illuminate\Console\Command::SUCCESS;
        }

        $sqlScript = $this->sqlGenerator->formatAsSqlScript($statements, $migrationFile);

        // Check if output file is specified
        $outputPath = $this->option('output');

        if ($outputPath) {
            // Save to file
            $outputFile = str_ends_with($outputPath, '.sql') ? $outputPath : $outputPath.'/'.basename($migrationFile, '.php').'.sql';

            File::ensureDirectoryExists(dirname($outputFile));
            File::put($outputFile, $sqlScript);

            $this->info("✅ SQL script saved to: {$outputFile}");
            $this->newLine();

            // Also display summary
            $this->displaySqlSummary($statements);
        } else {
            // Display to console with syntax highlighting
            $this->displaySqlOutput($sqlScript, $statements);
        }

        return \Illuminate\Console\Command::SUCCESS;
    }

    /**
     * Display SQL output to console with formatting.
     */
    protected function displaySqlOutput(string $sqlScript, array $statements): void
    {
        $this->line('╔══════════════════════════════════════════════════════════════╗');
        $this->line('║               📄 GENERATED SQL STATEMENTS                    ║');
        $this->line('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Display each statement with formatting
        foreach ($statements as $index => $statement) {
            $op = $statement['operation'];
            $type = $op['type'] ?? 'unknown';
            $action = $op['action'] ?? 'unknown';

            // Color-code by operation type
            $icon = match ($action) {
                'drop' => '🔴',
                'modify', 'rename' => '🟡',
                'add', 'create' => '🟢',
                default => '⚪',
            };

            $this->line("{$icon} <comment>[".($index + 1)."] {$type}::{$action}</comment>");
            $this->line('<fg=cyan>'.$statement['sql'].'</>');
            $this->newLine();
        }

        $this->displaySqlSummary($statements);
    }

    /**
     * Display SQL generation summary.
     */
    protected function displaySqlSummary(array $statements): void
    {
        $this->line('─────────────────────────────────────────────────────────────────');
        $this->info('📊 Summary:');

        $counts = [
            'create' => 0,
            'add' => 0,
            'modify' => 0,
            'drop' => 0,
            'rename' => 0,
        ];

        foreach ($statements as $statement) {
            $action = $statement['operation']['action'] ?? 'unknown';
            if (isset($counts[$action])) {
                $counts[$action]++;
            }
        }

        $summary = [];
        if ($counts['create'] > 0) {
            $summary[] = "🟢 {$counts['create']} create";
        }
        if ($counts['add'] > 0) {
            $summary[] = "🟢 {$counts['add']} add";
        }
        if ($counts['modify'] > 0) {
            $summary[] = "🟡 {$counts['modify']} modify";
        }
        if ($counts['rename'] > 0) {
            $summary[] = "🟡 {$counts['rename']} rename";
        }
        if ($counts['drop'] > 0) {
            $summary[] = "🔴 {$counts['drop']} drop";
        }

        $this->line('   Total statements: '.count($statements));
        $this->line('   Operations: '.implode(', ', $summary));
        $this->newLine();

        $this->line('<fg=gray>💡 Tip: Use --output=path/to/file.sql to save SQL to a file</>');
    }

    /**
     * Resolve migration file path.
     */
    protected function resolveMigrationPath(string $path): ?string
    {
        // If absolute path, use as is
        if (File::exists($path)) {
            return $path;
        }

        // Try relative to database/migrations
        // Use Laravel's base_path helper if available, otherwise construct path
        /** @var callable|null $basePathFunc */
        $basePathFunc = function_exists('base_path') ? 'base_path' : null;
        $migrationsPath = $basePathFunc
            ? $basePathFunc('database/migrations')
            : getcwd().'/database/migrations';
        $fullPath = $migrationsPath.'/'.$path;
        if (File::exists($fullPath)) {
            return $fullPath;
        }

        // Try to find by filename
        $files = File::glob($migrationsPath.'/*'.$path.'*');
        if (! empty($files)) {
            return $files[0];
        }

        // Try exact match in migrations directory
        $files = File::glob($migrationsPath.'/'.$path);
        if (! empty($files)) {
            return $files[0];
        }

        return null;
    }
}
