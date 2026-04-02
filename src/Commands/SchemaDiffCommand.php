<?php

namespace Zaeem2396\SchemaLens\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Throwable;
use Zaeem2396\SchemaLens\Services\SchemaComparator;
use Zaeem2396\SchemaLens\Services\SchemaIntrospector;
use Zaeem2396\SchemaLens\Services\SchemaMigrationStubHint;

class SchemaDiffCommand extends Command
{
    protected $signature = 'schema:diff
                            {from? : Source Laravel database connection name (reference schema)}
                            {to? : Target Laravel database connection name (compare against)}
                            {--from= : Source connection (overrides first argument)}
                            {--to= : Target connection (overrides second argument)}
                            {--format=cli : Output format: cli or json}
                            {--stubs : Print suggested migration-style hints for missing tables/columns}
                            {--exit-zero : Always exit 0 when the command succeeds, even if schemas differ}';

    protected $description = 'Compare MySQL schemas between two Laravel database connections';

    public function handle(SchemaComparator $comparator, SchemaMigrationStubHint $stubHint): int
    {
        $from = $this->option('from') ?: $this->argument('from');
        $to = $this->option('to') ?: $this->argument('to');

        if ($from === null || $from === '' || $to === null || $to === '') {
            $this->error('Both connections are required. Example: php artisan schema:diff mysql mysql_secondary');
            $this->line('  Or: php artisan schema:diff --from=mysql --to=mysql_staging');

            return self::FAILURE;
        }

        $connections = Config::get('database.connections', []);
        foreach ([$from => 'from', $to => 'to'] as $name => $label) {
            if (! isset($connections[$name])) {
                $this->error("Unknown database connection \"{$name}\" ({$label}). Check config/database.php.");

                return self::FAILURE;
            }
            $driver = $connections[$name]['driver'] ?? '';
            if (strtolower((string) $driver) !== 'mysql') {
                $this->error("Connection \"{$name}\" must use the mysql driver (found: {$driver}).");

                return self::FAILURE;
            }
        }

        try {
            $fromSchema = (new SchemaIntrospector($from))->getCurrentSchema();
            $toSchema = (new SchemaIntrospector($to))->getCurrentSchema();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('Failed to introspect schema: '.$e->getMessage());

            return self::FAILURE;
        }

        $diff = $comparator->compare($fromSchema, $toSchema);
        $format = $this->option('format') ?? 'cli';

        if ($format === 'json') {
            $this->line(json_encode([
                'from_connection' => $from,
                'to_connection' => $to,
                'identical' => $comparator->isIdentical($diff),
                'diff' => $diff,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderCliDiff($from, $to, $diff);
        }

        if ($this->option('stubs') && ! $comparator->isIdentical($diff)) {
            $this->newLine();
            $this->line($stubHint->build($diff, $fromSchema));
        }

        if ($comparator->isIdentical($diff)) {
            if ($format !== 'json') {
                $this->info("Schemas match for connections \"{$from}\" and \"{$to}\".");
            }

            return self::SUCCESS;
        }

        if ($this->option('exit-zero')) {
            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $diff
     */
    protected function renderCliDiff(string $from, string $to, array $diff): void
    {
        $this->info("Schema differences: {$from} → {$to}");
        $this->line('(Reference = '.$from.'; missing below means absent on '.$to.')');
        $this->newLine();

        if ($diff['missing_tables_in_to'] !== []) {
            $this->warn('MISSING TABLES ON '.$to.':');
            foreach ($diff['missing_tables_in_to'] as $table) {
                $this->line("  ✗ Table: {$table}");
            }
            $this->newLine();
        }

        if ($diff['extra_tables_in_to'] !== []) {
            $this->warn('EXTRA TABLES ON '.$to.' (not on '.$from.'):');
            foreach ($diff['extra_tables_in_to'] as $table) {
                $this->line("  + Table: {$table}");
            }
            $this->newLine();
        }

        if ($diff['columns_missing_in_to'] !== []) {
            $this->warn('MISSING COLUMNS ON '.$to.':');
            foreach ($diff['columns_missing_in_to'] as $row) {
                $this->line("  ✗ {$row['table']}.{$row['column']} ({$row['type']})");
            }
            $this->newLine();
        }

        if ($diff['columns_extra_in_to'] !== []) {
            $this->warn('EXTRA COLUMNS ON '.$to.' (not on '.$from.'):');
            foreach ($diff['columns_extra_in_to'] as $row) {
                $this->line("  + {$row['table']}.{$row['column']} ({$row['type']})");
            }
            $this->newLine();
        }

        if ($diff['type_mismatches'] !== []) {
            $this->warn('TYPE MISMATCH:');
            foreach ($diff['type_mismatches'] as $row) {
                $this->line("  ⚠ {$row['table']}.{$row['column']}: {$row['from_type']} ({$from}) vs {$row['to_type']} ({$to})");
            }
            $this->newLine();
        }

        if ($diff['nullable_mismatches'] !== []) {
            $this->warn('NULLABLE MISMATCH:');
            foreach ($diff['nullable_mismatches'] as $row) {
                $fromN = $row['from_nullable'] ? 'NULL' : 'NOT NULL';
                $toN = $row['to_nullable'] ? 'NULL' : 'NOT NULL';
                $this->line("  ⚠ {$row['table']}.{$row['column']}: {$fromN} ({$from}) vs {$toN} ({$to})");
            }
            $this->newLine();
        }
    }
}
