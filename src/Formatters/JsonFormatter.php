<?php

namespace Zaeem2396\SchemaLens\Formatters;

class JsonFormatter
{
    /**
     * Format diff output as JSON.
     */
    public function format(array $diff, array $destructiveChanges = [], array $rollback = [], array $exports = []): string
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'summary' => $this->generateSummary($diff, $destructiveChanges),
            'diff' => $diff,
            'destructive_changes' => $destructiveChanges,
            'rollback' => $rollback,
            'exports' => $exports,
        ];

        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate summary for JSON report.
     */
    protected function generateSummary(array $diff, array $destructiveChanges): array
    {
        return [
            'tables' => count($diff['tables'] ?? []),
            'columns' => count($diff['columns'] ?? []),
            'indexes' => count($diff['indexes'] ?? []),
            'foreign_keys' => count($diff['foreign_keys'] ?? []),
            'engine' => count($diff['engine'] ?? []),
            'charset' => count($diff['charset'] ?? []),
            'collation' => count($diff['collation'] ?? []),
            'destructive_changes_count' => count($destructiveChanges),
            'has_destructive_changes' => ! empty($destructiveChanges),
        ];
    }
}
