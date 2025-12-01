<?php

namespace Zaeem2396\SchemaLens\Commands;

use Illuminate\Console\Command as BaseCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Zaeem2396\SchemaLens\Services\SchemaIntrospector;
use Zaeem2396\SchemaLens\Services\MigrationParser;
use Zaeem2396\SchemaLens\Services\DiffGenerator;
use Zaeem2396\SchemaLens\Services\DestructiveChangeDetector;
use Zaeem2396\SchemaLens\Services\DataExporter;
use Zaeem2396\SchemaLens\Services\RollbackSimulator;
use Zaeem2396\SchemaLens\Formatters\CliFormatter;
use Zaeem2396\SchemaLens\Formatters\JsonFormatter;

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
                            {--no-export : Skip data export even if destructive changes are detected}';

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

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->introspector = new SchemaIntrospector();
        $this->parser = new MigrationParser();
        $this->diffGenerator = new DiffGenerator($this->introspector);
        $this->detector = new DestructiveChangeDetector();
        $this->exporter = new DataExporter();
        $this->rollbackSimulator = new RollbackSimulator($this->introspector, $this->parser);
    }

    /**
     * Execute the console command.
     */
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $migrationPath = $this->argument('migration');
        $format = $this->option('format') ?? Config::get('schema-lens.output.format', 'cli');

        // Resolve migration file path
        $migrationFile = $this->resolveMigrationPath($migrationPath);
        
        if (!$migrationFile || !File::exists($migrationFile)) {
            $this->error("Migration file not found: {$migrationPath}");
            return \Illuminate\Console\Command::FAILURE;
        }

        $this->info("Analyzing migration: {$migrationFile}");
        $this->newLine();

        try {
            // Parse migration
            $this->info("Parsing migration file...");
            $parsed = $this->parser->parse($migrationFile);
            $upOperations = $this->parser->getOperations('up');

            // Get current schema
            $this->info("Introspecting current database schema...");
            $currentSchema = $this->introspector->getCurrentSchema();

            // Generate diff
            $this->info("Generating schema diff...");
            $diff = $this->diffGenerator->generateDiff($upOperations->toArray(), $currentSchema);

            // Detect destructive changes
            $this->info("Detecting destructive changes...");
            $destructiveChanges = $this->detector->detect($upOperations);

            // Export data if destructive changes found
            $exports = [];
            if (!$this->option('no-export') && $destructiveChanges->isNotEmpty()) {
                $this->warn("⚠️  Destructive changes detected! Exporting data...");
                $exports = $this->exporter->exportDestructiveChanges(
                    $destructiveChanges->toArray(),
                    $migrationFile
                );
                $this->info("✓ Data exported successfully");
            }

            // Simulate rollback
            $this->info("Simulating rollback...");
            $rollback = $this->rollbackSimulator->simulate($migrationFile);

            // Format output
            $formatter = $format === 'json' ? new JsonFormatter() : new CliFormatter();
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
                    $outputFile = $exportPath . '/report.json';
                } else {
                    // Use Laravel's storage path helper if available, otherwise construct path
                    /** @var callable|null $storagePathFunc */
                    $storagePathFunc = function_exists('storage_path') ? 'storage_path' : null;
                    $outputFile = $storagePathFunc 
                        ? $storagePathFunc('app/schema-lens/report.json')
                        : getcwd() . '/storage/app/schema-lens/report.json';
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
                $this->warn("⚠️  Migration contains destructive changes!");
                return \Illuminate\Console\Command::FAILURE;
            }

            return \Illuminate\Console\Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return \Illuminate\Console\Command::FAILURE;
        }
    }

    /**
     * Resolve migration file path.
     *
     * @param string $path
     * @return string|null
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
            : getcwd() . '/database/migrations';
        $fullPath = $migrationsPath . '/' . $path;
        if (File::exists($fullPath)) {
            return $fullPath;
        }

        // Try to find by filename
        $files = File::glob($migrationsPath . '/*' . $path . '*');
        if (!empty($files)) {
            return $files[0];
        }

        // Try exact match in migrations directory
        $files = File::glob($migrationsPath . '/' . $path);
        if (!empty($files)) {
            return $files[0];
        }

        return null;
    }
}

