<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Support\Collection;

class MigrationParser
{
    protected array $operations = [];

    protected array $lineMap = [];

    /**
     * Parse a migration file and extract all operations.
     */
    public function parse(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        $this->operations = [];
        $this->lineMap = [];

        // Parse up() method
        $upMethod = $this->extractMethod($content, 'up');
        if ($upMethod) {
            $this->parseMethod($upMethod, $lines, 'up');
        }

        // Parse down() method
        $downMethod = $this->extractMethod($content, 'down');
        if ($downMethod) {
            $this->parseMethod($downMethod, $lines, 'down');
        }

        return [
            'operations' => $this->operations,
            'line_map' => $this->lineMap,
        ];
    }

    /**
     * Extract a method from the migration file.
     */
    protected function extractMethod(string $content, string $methodName): ?string
    {
        // Find method start
        $pattern = '/public\s+function\s+'.preg_quote($methodName, '/').'\s*\([^)]*\)(?:\s*:\s*\S+)?\s*\{/';

        if (! preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $startPos = $matches[0][1] + strlen($matches[0][0]) - 1; // Position of opening brace
        $braceCount = 0;
        $inString = false;
        $stringChar = null;
        $pos = $startPos;

        // Find matching closing brace
        while ($pos < strlen($content)) {
            $char = $content[$pos];

            // Handle string literals
            if (($char === '"' || $char === "'") && ($pos === 0 || $content[$pos - 1] !== '\\')) {
                if (! $inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                    $stringChar = null;
                }
            }

            // Count braces only when not in string
            if (! $inString) {
                if ($char === '{') {
                    $braceCount++;
                } elseif ($char === '}') {
                    $braceCount--;
                    if ($braceCount === 0) {
                        // Found matching closing brace
                        $methodContent = substr($content, $startPos + 1, $pos - $startPos - 1);

                        return $methodContent;
                    }
                }
            }

            $pos++;
        }

        return null;
    }

    /**
     * Parse a method and extract operations.
     */
    protected function parseMethod(string $methodContent, array $allLines, string $direction): void
    {
        $lines = explode("\n", $methodContent);
        $currentTable = null;
        $lineOffset = $this->findMethodStartLine($allLines, $direction === 'up' ? 'up' : 'down');

        foreach ($lines as $localIndex => $line) {
            $lineNumber = $lineOffset + $localIndex + 1;
            $trimmed = trim($line);

            // Skip empty lines and comments
            if (empty($trimmed) || strpos($trimmed, '//') === 0 || strpos($trimmed, '/*') === 0) {
                continue;
            }

            // Detect Schema operations
            if (preg_match('/Schema::(create|table|drop|dropIfExists)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $trimmed, $matches)) {
                $operation = $matches[1];
                $tableName = $matches[2];

                if ($operation === 'create' || $operation === 'table') {
                    $currentTable = $tableName;
                    $this->addOperation('table', $operation === 'create' ? 'create' : 'modify', [
                        'table' => $tableName,
                    ], $lineNumber, $direction);
                } else {
                    $this->addOperation('table', 'drop', [
                        'table' => $tableName,
                    ], $lineNumber, $direction);
                }
            }

            // Column operations
            if ($currentTable && preg_match('/->(string|integer|bigInteger|text|boolean|date|datetime|timestamp|decimal|float|double|enum|json|binary|char|tinyInteger|smallInteger|mediumInteger|unsignedInteger|unsignedBigInteger|unsignedTinyInteger|unsignedSmallInteger|unsignedMediumInteger|longText|mediumText|tinyText|jsonb|uuid|ipAddress|macAddress|geometry|point|lineString|polygon|geometryCollection|multiPoint|multiLineString|multiPolygon|multiPolygonZ)\s*\(/', $trimmed, $matches)) {
                $columnType = $matches[1];
                $columnName = $this->extractColumnName($trimmed);

                if ($columnName) {
                    $this->addOperation('column', 'add', [
                        'table' => $currentTable,
                        'column' => $columnName,
                        'type' => $columnType,
                        'definition' => $trimmed,
                    ], $lineNumber, $direction);
                }
            }

            // Column modifications
            if ($currentTable && preg_match('/->(change|modify)\s*\(/', $trimmed)) {
                $columnName = $this->extractColumnName($trimmed);
                if ($columnName) {
                    $this->addOperation('column', 'modify', [
                        'table' => $currentTable,
                        'column' => $columnName,
                        'definition' => $trimmed,
                    ], $lineNumber, $direction);
                }
            }

            // Drop column
            if ($currentTable && preg_match('/->dropColumn\s*\(\s*[\'"]([^\'"]+)[\'"]/', $trimmed, $matches)) {
                $this->addOperation('column', 'drop', [
                    'table' => $currentTable,
                    'column' => $matches[1],
                ], $lineNumber, $direction);
            }

            // Rename column
            if ($currentTable && preg_match('/->renameColumn\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]/', $trimmed, $matches)) {
                $this->addOperation('column', 'rename', [
                    'table' => $currentTable,
                    'from' => $matches[1],
                    'to' => $matches[2],
                ], $lineNumber, $direction);
            }

            // Index operations
            if ($currentTable && preg_match('/->(index|unique|primary)\s*\(/', $trimmed, $matches)) {
                $indexType = $matches[1];
                $columns = $this->extractArrayArgument($trimmed);
                $indexName = $this->extractIndexName($trimmed);

                $this->addOperation('index', 'add', [
                    'table' => $currentTable,
                    'type' => $indexType,
                    'columns' => $columns,
                    'name' => $indexName,
                ], $lineNumber, $direction);
            }

            // Drop index
            if ($currentTable && preg_match('/->dropIndex\s*\(/', $trimmed)) {
                $indexName = $this->extractIndexName($trimmed) ?? $this->extractStringArgument($trimmed);
                if ($indexName) {
                    $this->addOperation('index', 'drop', [
                        'table' => $currentTable,
                        'name' => $indexName,
                    ], $lineNumber, $direction);
                }
            }

            // Foreign key operations
            if ($currentTable && preg_match('/->(foreign|foreignId)\s*\(/', $trimmed)) {
                $columns = $this->extractArrayArgument($trimmed);
                $column = $this->extractStringArgument($trimmed);

                // Try to find references() call on next lines
                $referencesLine = $this->findReferencesLine($lines, $localIndex);
                if ($referencesLine) {
                    $referencedTable = $this->extractReferencedTable($referencesLine);
                    $referencedColumn = $this->extractReferencedColumn($referencesLine);
                    $onUpdate = $this->extractOnUpdate($referencesLine);
                    $onDelete = $this->extractOnDelete($referencesLine);

                    $this->addOperation('foreign_key', 'add', [
                        'table' => $currentTable,
                        'columns' => $column ? [$column] : $columns,
                        'referenced_table' => $referencedTable,
                        'referenced_columns' => $referencedColumn ? [$referencedColumn] : null,
                        'on_update' => $onUpdate,
                        'on_delete' => $onDelete,
                    ], $lineNumber, $direction);
                }
            }

            // Drop foreign key
            if ($currentTable && preg_match('/->dropForeign\s*\(/', $trimmed)) {
                $foreignKeyName = $this->extractStringArgument($trimmed) ?? $this->extractArrayArgument($trimmed);
                if ($foreignKeyName) {
                    $this->addOperation('foreign_key', 'drop', [
                        'table' => $currentTable,
                        'name' => is_array($foreignKeyName) ? $foreignKeyName[0] : $foreignKeyName,
                    ], $lineNumber, $direction);
                }
            }

            // Engine change
            if ($currentTable && preg_match('/->engine\s*\(\s*[\'"]([^\'"]+)[\'"]/', $trimmed, $matches)) {
                $this->addOperation('engine', 'change', [
                    'table' => $currentTable,
                    'engine' => $matches[1],
                ], $lineNumber, $direction);
            }

            // Charset change
            if ($currentTable && preg_match('/->charset\s*\(\s*[\'"]([^\'"]+)[\'"]/', $trimmed, $matches)) {
                $this->addOperation('charset', 'change', [
                    'table' => $currentTable,
                    'charset' => $matches[1],
                ], $lineNumber, $direction);
            }

            // Collation change
            if ($currentTable && preg_match('/->collation\s*\(\s*[\'"]([^\'"]+)[\'"]/', $trimmed, $matches)) {
                $this->addOperation('collation', 'change', [
                    'table' => $currentTable,
                    'collation' => $matches[1],
                ], $lineNumber, $direction);
            }
        }
    }

    /**
     * Find the line number where a method starts.
     */
    protected function findMethodStartLine(array $lines, string $methodName): int
    {
        foreach ($lines as $index => $line) {
            if (preg_match('/public\s+function\s+'.$methodName.'\s*\(/', $line)) {
                return $index;
            }
        }

        return 0;
    }

    /**
     * Extract column name from a line.
     */
    protected function extractColumnName(string $line): ?string
    {
        if (preg_match('/->\w+\s*\(\s*[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract string argument from a line.
     */
    protected function extractStringArgument(string $line): ?string
    {
        if (preg_match('/\(\s*[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract array argument from a line.
     */
    protected function extractArrayArgument(string $line): array
    {
        if (preg_match('/\[\s*([^\]]+)\s*\]/', $line, $matches)) {
            $content = $matches[1];
            $items = [];
            if (preg_match_all('/[\'"]([^\'"]+)[\'"]/', $content, $itemMatches)) {
                $items = $itemMatches[1];
            }

            return $items;
        }

        return [];
    }

    /**
     * Extract index name from a line.
     */
    protected function extractIndexName(string $line): ?string
    {
        if (preg_match('/->\w+\s*\([^,]+,\s*[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Find references() call in subsequent lines.
     */
    protected function findReferencesLine(array $lines, int $startIndex): ?string
    {
        for ($i = $startIndex + 1; $i < min($startIndex + 10, count($lines)); $i++) {
            if (strpos($lines[$i], 'references(') !== false || strpos($lines[$i], '->references(') !== false) {
                return $lines[$i];
            }
        }

        return null;
    }

    /**
     * Extract referenced table from references() call.
     */
    protected function extractReferencedTable(string $line): ?string
    {
        if (preg_match('/->references\s*\(\s*[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract referenced column from references() call.
     */
    protected function extractReferencedColumn(string $line): ?string
    {
        if (preg_match('/->references\s*\([^,]+,\s*[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract onUpdate from a line.
     */
    protected function extractOnUpdate(string $line): ?string
    {
        if (preg_match('/->onUpdate\s*\(\s*[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract onDelete from a line.
     */
    protected function extractOnDelete(string $line): ?string
    {
        if (preg_match('/->onDelete\s*\(\s*[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Add an operation to the list.
     */
    protected function addOperation(string $type, string $action, array $data, int $lineNumber, string $direction): void
    {
        $this->operations[] = [
            'type' => $type,
            'action' => $action,
            'direction' => $direction,
            'data' => $data,
            'line' => $lineNumber,
        ];

        $this->lineMap[$lineNumber] = [
            'type' => $type,
            'action' => $action,
            'direction' => $direction,
            'data' => $data,
        ];
    }

    /**
     * Get operations for a specific direction.
     */
    public function getOperations(string $direction = 'up'): Collection
    {
        return collect($this->operations)->filter(function ($op) use ($direction) {
            return $op['direction'] === $direction;
        });
    }
}
