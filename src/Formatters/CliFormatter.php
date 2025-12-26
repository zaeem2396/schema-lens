<?php

namespace Zaeem2396\SchemaLens\Formatters;

class CliFormatter
{
    /**
     * Format diff output for CLI.
     */
    public function format(array $diff, array $destructiveChanges = [], array $rollback = [], array $exports = []): string
    {
        $output = [];
        $output[] = $this->header();
        $output[] = '';

        // Summary
        $output[] = $this->formatSummary($diff, $destructiveChanges);
        $output[] = '';

        // Destructive changes warning
        if (! empty($destructiveChanges)) {
            $output[] = $this->formatDestructiveChanges($destructiveChanges);
            $output[] = '';
        }

        // Detailed diffs
        $output[] = $this->formatDiffs($diff);
        $output[] = '';

        // Rollback simulation
        if (! empty($rollback) && $rollback['has_rollback']) {
            $output[] = $this->formatRollback($rollback);
            $output[] = '';
        }

        // Export information
        if (! empty($exports)) {
            $output[] = $this->formatExports($exports);
            $output[] = '';
        }

        return implode("\n", $output);
    }

    /**
     * Format header.
     */
    protected function header(): string
    {
        return "╔══════════════════════════════════════════════════════════════╗\n".
               "║          Schema Lens - Migration Preview Report            ║\n".
               '╚══════════════════════════════════════════════════════════════╝';
    }

    /**
     * Format summary.
     */
    protected function formatSummary(array $diff, array $destructiveChanges): string
    {
        $summary = ['📊 SUMMARY'];
        $summary[] = str_repeat('─', 60);

        $counts = [
            'tables' => count($diff['tables'] ?? []),
            'columns' => count($diff['columns'] ?? []),
            'indexes' => count($diff['indexes'] ?? []),
            'foreign_keys' => count($diff['foreign_keys'] ?? []),
            'engine' => count($diff['engine'] ?? []),
            'charset' => count($diff['charset'] ?? []),
            'collation' => count($diff['collation'] ?? []),
        ];

        $summary[] = "Tables:        {$counts['tables']}";
        $summary[] = "Columns:       {$counts['columns']}";
        $summary[] = "Indexes:       {$counts['indexes']}";
        $summary[] = "Foreign Keys:  {$counts['foreign_keys']}";
        $summary[] = "Engine:        {$counts['engine']}";
        $summary[] = "Charset:       {$counts['charset']}";
        $summary[] = "Collation:     {$counts['collation']}";

        if (! empty($destructiveChanges)) {
            $summary[] = '';
            $summary[] = '⚠️  DESTRUCTIVE CHANGES: '.count($destructiveChanges);
        }

        return implode("\n", $summary);
    }

    /**
     * Format destructive changes.
     */
    protected function formatDestructiveChanges(array $destructiveChanges): string
    {
        $output = ['⚠️  DESTRUCTIVE CHANGES DETECTED'];
        $output[] = str_repeat('═', 60);

        foreach ($destructiveChanges as $change) {
            $op = $change['operation'];
            $risk = strtoupper($change['risk_level']);
            $line = $op['line'] ?? 'N/A';

            $output[] = '';
            $output[] = "  Risk Level: {$risk}";
            $output[] = "  Operation:  {$op['type']}::{$op['action']}";
            $output[] = "  Line:       {$line}";

            if (! empty($change['affected_tables'])) {
                $output[] = '  Tables:     '.implode(', ', $change['affected_tables']);
            }

            if (! empty($change['affected_columns'])) {
                $cols = array_map(function ($col) {
                    return ($col['table'] ?? '').'.'.($col['column'] ?? '');
                }, $change['affected_columns']);
                $output[] = '  Columns:    '.implode(', ', $cols);
            }
        }

        return implode("\n", $output);
    }

    /**
     * Format diffs.
     */
    protected function formatDiffs(array $diff): string
    {
        $output = ['📋 DETAILED CHANGES'];
        $output[] = str_repeat('─', 60);

        // Tables
        if (! empty($diff['tables'])) {
            $output[] = "\n📦 TABLES:";
            foreach ($diff['tables'] as $tableDiff) {
                $output[] = $this->formatDiffItem($tableDiff, 'table');
            }
        }

        // Columns
        if (! empty($diff['columns'])) {
            $output[] = "\n📝 COLUMNS:";
            foreach ($diff['columns'] as $columnDiff) {
                $output[] = $this->formatDiffItem($columnDiff, 'column');
            }
        }

        // Indexes
        if (! empty($diff['indexes'])) {
            $output[] = "\n🔍 INDEXES:";
            foreach ($diff['indexes'] as $indexDiff) {
                $output[] = $this->formatDiffItem($indexDiff, 'index');
            }
        }

        // Foreign Keys
        if (! empty($diff['foreign_keys'])) {
            $output[] = "\n🔗 FOREIGN KEYS:";
            foreach ($diff['foreign_keys'] as $fkDiff) {
                $output[] = $this->formatDiffItem($fkDiff, 'foreign_key');
            }
        }

        // Engine
        if (! empty($diff['engine'])) {
            $output[] = "\n⚙️  ENGINE:";
            foreach ($diff['engine'] as $engineDiff) {
                $output[] = $this->formatDiffItem($engineDiff, 'engine');
            }
        }

        // Charset
        if (! empty($diff['charset'])) {
            $output[] = "\n🔤 CHARSET:";
            foreach ($diff['charset'] as $charsetDiff) {
                $output[] = $this->formatDiffItem($charsetDiff, 'charset');
            }
        }

        // Collation
        if (! empty($diff['collation'])) {
            $output[] = "\n🔤 COLLATION:";
            foreach ($diff['collation'] as $collationDiff) {
                $output[] = $this->formatDiffItem($collationDiff, 'collation');
            }
        }

        return implode("\n", $output);
    }

    /**
     * Format a single diff item.
     */
    protected function formatDiffItem(array $diff, string $type): string
    {
        $status = $diff['status'] ?? 'info';
        $line = $diff['line'] ?? 'N/A';
        $message = $diff['message'] ?? '';

        $icon = $this->getStatusIcon($status);
        $prefix = "[Line {$line}] ";

        return "  {$icon} {$prefix}{$message}";
    }

    /**
     * Get icon for status.
     */
    protected function getStatusIcon(string $status): string
    {
        return match ($status) {
            'destructive' => '🔴',
            'error' => '❌',
            'warning' => '⚠️ ',
            'new' => '➕',
            'change' => '🔄',
            'info' => 'ℹ️ ',
            default => '  ',
        };
    }

    /**
     * Format rollback information.
     */
    protected function formatRollback(array $rollback): string
    {
        $output = ['🔄 ROLLBACK SIMULATION'];
        $output[] = str_repeat('─', 60);

        if (! $rollback['has_rollback']) {
            $output[] = '  No rollback method defined';

            return implode("\n", $output);
        }

        $impact = $rollback['impact'] ?? [];
        $risk = strtoupper($impact['risk_level'] ?? 'low');

        $output[] = "  Risk Level: {$risk}";
        $output[] = '';

        if (! empty($impact['tables_affected'])) {
            $output[] = '  Tables Affected: '.implode(', ', array_filter($impact['tables_affected']));
        }

        if (! empty($impact['columns_affected'])) {
            $cols = array_map(function ($col) {
                return ($col['table'] ?? '').'.'.($col['column'] ?? '');
            }, $impact['columns_affected']);
            $output[] = '  Columns Affected: '.implode(', ', $cols);
        }

        if (! empty($rollback['dependencies'])) {
            $output[] = '';
            $output[] = '  ⚠️  Dependency Warnings:';
            foreach ($rollback['dependencies'] as $dep) {
                $output[] = "    - [{$dep['risk']}] {$dep['message']}";
            }
        }

        if (! empty($rollback['sql_preview'])) {
            $output[] = '';
            $output[] = '  SQL Preview:';
            foreach ($rollback['sql_preview'] as $sql) {
                $output[] = "    Line {$sql['line']}: {$sql['sql']}";
            }
        }

        return implode("\n", $output);
    }

    /**
     * Format export information.
     */
    protected function formatExports(array $exports): string
    {
        $output = ['💾 DATA EXPORTS'];
        $output[] = str_repeat('─', 60);

        foreach ($exports as $export) {
            $output[] = '';
            $output[] = "  Table: {$export['table']}";
            $output[] = "  Export Path: {$export['export_path']}";
            $output[] = "  Version: {$export['version']}";
            $output[] = '  Files:';
            foreach ($export['files'] as $type => $path) {
                if ($path) {
                    $output[] = "    - {$type}: {$path}";
                }
            }
        }

        return implode("\n", $output);
    }
}
