<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Support\Collection;

class DestructiveChangeDetector
{
    /**
     * Detect destructive changes in migration operations.
     */
    public function detect(Collection $operations): Collection
    {
        $destructive = collect();

        foreach ($operations as $operation) {
            if ($this->isDestructive($operation)) {
                $destructive->push([
                    'operation' => $operation,
                    'risk_level' => $this->getRiskLevel($operation),
                    'affected_tables' => $this->getAffectedTables($operation),
                    'affected_columns' => $this->getAffectedColumns($operation),
                ]);
            }
        }

        return $destructive;
    }

    /**
     * Check if an operation is destructive.
     */
    protected function isDestructive(array $operation): bool
    {
        $type = $operation['type'];
        $action = $operation['action'];

        // Destructive operations
        $destructiveActions = [
            'table' => ['drop'],
            'column' => ['drop', 'rename'],
            'index' => ['drop'],
            'foreign_key' => ['drop'],
        ];

        return isset($destructiveActions[$type]) &&
               in_array($action, $destructiveActions[$type]);
    }

    /**
     * Get risk level for a destructive operation.
     */
    protected function getRiskLevel(array $operation): string
    {
        $type = $operation['type'];
        $action = $operation['action'];

        if ($type === 'table' && $action === 'drop') {
            return 'critical';
        }

        if ($type === 'column' && $action === 'drop') {
            return 'high';
        }

        if ($type === 'column' && $action === 'rename') {
            return 'high';
        }

        if ($type === 'foreign_key' && $action === 'drop') {
            return 'medium';
        }

        if ($type === 'index' && $action === 'drop') {
            return 'low';
        }

        return 'medium';
    }

    /**
     * Get affected tables for an operation.
     */
    protected function getAffectedTables(array $operation): array
    {
        $data = $operation['data'];
        $tables = [];

        if (isset($data['table'])) {
            $tables[] = $data['table'];
        }

        if (isset($data['referenced_table'])) {
            $tables[] = $data['referenced_table'];
        }

        return array_unique($tables);
    }

    /**
     * Get affected columns for an operation.
     */
    protected function getAffectedColumns(array $operation): array
    {
        $data = $operation['data'];
        $columns = [];

        if (isset($data['column'])) {
            $columns[] = [
                'table' => $data['table'] ?? null,
                'column' => $data['column'],
            ];
        }

        if (isset($data['from'])) {
            $columns[] = [
                'table' => $data['table'] ?? null,
                'column' => $data['from'],
            ];
        }

        if (isset($data['to'])) {
            $columns[] = [
                'table' => $data['table'] ?? null,
                'column' => $data['to'],
            ];
        }

        if (isset($data['columns']) && is_array($data['columns'])) {
            foreach ($data['columns'] as $column) {
                $columns[] = [
                    'table' => $data['table'] ?? null,
                    'column' => $column,
                ];
            }
        }

        return $columns;
    }
}
