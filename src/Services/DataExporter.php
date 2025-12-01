<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DataExporter
{
    protected string $exportPath;
    protected bool $compress;
    protected ?int $rowLimit;

    public function __construct()
    {
        $storagePath = config('schema-lens.export.storage_path', 'app/schema-lens/exports');
        // Resolve storage path - handle both relative and absolute paths
        if (function_exists('storage_path') && !str_starts_with($storagePath, '/')) {
            $this->exportPath = storage_path($storagePath);
        } else {
            $this->exportPath = $storagePath;
        }
        $this->compress = config('schema-lens.export.compress', true);
        $this->rowLimit = config('schema-lens.export.row_limit', 1000);

        // Ensure export directory exists
        if (!File::exists($this->exportPath)) {
            File::makeDirectory($this->exportPath, 0755, true);
        }
    }

    /**
     * Export data for destructive changes.
     */
    public function exportDestructiveChanges(array $destructiveChanges, string $migrationFile): array
    {
        $exports = [];
        $timestamp = now()->format('Y-m-d_H-i-s');
        $migrationName = basename($migrationFile, '.php');
        $version = $this->generateVersion();

        foreach ($destructiveChanges as $change) {
            $operation = $change['operation'];
            $tables = $change['affected_tables'];
            $columns = $change['affected_columns'];

            foreach ($tables as $table) {
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    continue;
                }

                $exportDir = $this->exportPath . '/' . $migrationName . '_' . $timestamp . '_v' . $version;
                File::makeDirectory($exportDir, 0755, true);

                // Export full table or specific columns
                if (empty($columns)) {
                    // Export entire table
                    $data = $this->exportTable($table, $exportDir);
                } else {
                    // Export specific columns
                    $tableColumns = array_filter($columns, function ($col) use ($table) {
                        return $col['table'] === $table;
                    });

                    if (!empty($tableColumns)) {
                        $columnNames = array_column($tableColumns, 'column');
                        $data = $this->exportTableColumns($table, $columnNames, $exportDir);
                    } else {
                        $data = $this->exportTable($table, $exportDir);
                    }
                }

                if ($data) {
                    $exports[] = [
                        'table' => $table,
                        'operation' => $operation,
                        'export_path' => $exportDir,
                        'files' => $data,
                        'version' => $version,
                        'timestamp' => $timestamp,
                    ];
                }
            }
        }

        // Create restore metadata
        if (!empty($exports)) {
            $this->createRestoreMetadata($exports, $migrationFile, $version, $timestamp);
        }

        return $exports;
    }

    /**
     * Export entire table data.
     */
    protected function exportTable(string $table, string $exportDir): array
    {
        $query = DB::table($table);

        if ($this->rowLimit !== null) {
            $query->limit($this->rowLimit);
        }

        $data = $query->get()->toArray();
        $dataArray = array_map(function ($item) {
            return (array) $item;
        }, $data);

        // Export as JSON
        $jsonFile = $exportDir . '/' . $table . '.json';
        File::put($jsonFile, json_encode($dataArray, JSON_PRETTY_PRINT));

        // Export as CSV
        $csvFile = $exportDir . '/' . $table . '.csv';
        $this->exportToCsv($dataArray, $csvFile);

        $files = [
            'json' => $jsonFile,
            'csv' => $csvFile,
        ];

        // Compress if enabled
        if ($this->compress) {
            $files['compressed'] = $this->compressExport($exportDir, $table);
        }

        return $files;
    }

    /**
     * Export specific columns from a table.
     */
    protected function exportTableColumns(string $table, array $columns, string $exportDir): array
    {
        $query = DB::table($table)->select($columns);

        if ($this->rowLimit !== null) {
            $query->limit($this->rowLimit);
        }

        $data = $query->get()->toArray();
        $dataArray = array_map(function ($item) {
            return (array) $item;
        }, $data);

        $columnSuffix = implode('_', $columns);
        $safeSuffix = Str::slug($columnSuffix, '_');

        // Export as JSON
        $jsonFile = $exportDir . '/' . $table . '_' . $safeSuffix . '.json';
        File::put($jsonFile, json_encode($dataArray, JSON_PRETTY_PRINT));

        // Export as CSV
        $csvFile = $exportDir . '/' . $table . '_' . $safeSuffix . '.csv';
        $this->exportToCsv($dataArray, $csvFile);

        $files = [
            'json' => $jsonFile,
            'csv' => $csvFile,
        ];

        // Compress if enabled
        if ($this->compress) {
            $files['compressed'] = $this->compressExport($exportDir, $table . '_' . $safeSuffix);
        }

        return $files;
    }

    /**
     * Export data to CSV.
     */
    protected function exportToCsv(array $data, string $filePath): void
    {
        if (empty($data)) {
            File::put($filePath, '');
            return;
        }

        $handle = fopen($filePath, 'w');

        // Write headers
        $headers = array_keys($data[0]);
        fputcsv($handle, $headers);

        // Write data
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
    }

    /**
     * Compress export directory.
     */
    protected function compressExport(string $exportDir, string $prefix): ?string
    {
        if (!extension_loaded('zip')) {
            return null;
        }

        $zipFile = $exportDir . '/' . $prefix . '.zip';
        
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $files = File::allFiles($exportDir);
            foreach ($files as $file) {
                if ($file->getExtension() !== 'zip') {
                    $zip->addFile($file->getPathname(), $file->getRelativePathname());
                }
            }
            $zip->close();
            return $zipFile;
        }

        return null;
    }

    /**
     * Generate version number for export.
     */
    protected function generateVersion(): string
    {
        $versionFile = $this->exportPath . '/.version';
        
        if (File::exists($versionFile)) {
            $version = (int) File::get($versionFile);
            $version++;
        } else {
            $version = 1;
        }

        File::put($versionFile, $version);

        return str_pad($version, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create restore metadata file.
     */
    protected function createRestoreMetadata(array $exports, string $migrationFile, string $version, string $timestamp): void
    {
        $metadata = [
            'version' => $version,
            'timestamp' => $timestamp,
            'migration_file' => $migrationFile,
            'migration_name' => basename($migrationFile, '.php'),
            'exports' => $exports,
            'restore_instructions' => $this->generateRestoreInstructions($exports),
        ];

        $exportDir = dirname($exports[0]['export_path']);
        $metadataFile = $exportDir . '/restore_metadata.json';
        File::put($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
    }

    /**
     * Generate restore instructions.
     */
    protected function generateRestoreInstructions(array $exports): array
    {
        $instructions = [];

        foreach ($exports as $export) {
            $table = $export['table'];
            $operation = $export['operation'];
            $files = $export['files'];

            $instruction = [
                'table' => $table,
                'operation' => $operation['type'] . ':' . $operation['action'],
                'restore_method' => $this->getRestoreMethod($operation),
                'data_files' => [
                    'json' => $files['json'] ?? null,
                    'csv' => $files['csv'] ?? null,
                    'compressed' => $files['compressed'] ?? null,
                ],
            ];

            $instructions[] = $instruction;
        }

        return $instructions;
    }

    /**
     * Get restore method for an operation.
     */
    protected function getRestoreMethod(array $operation): string
    {
        $type = $operation['type'];
        $action = $operation['action'];

        if ($type === 'table' && $action === 'drop') {
            return 'Import JSON/CSV data and recreate table structure, then insert data';
        }

        if ($type === 'column' && $action === 'drop') {
            return 'Recreate column with same structure, then import data';
        }

        if ($type === 'column' && $action === 'rename') {
            return 'Rename column back to original name, data should be preserved';
        }

        return 'Review exported data and restore manually';
    }
}

