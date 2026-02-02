<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Support\Collection;

class MigrationParser
{
    protected array $operations = [];

    protected array $lineMap = [];

    /**
     * Parse a migration file and extract all operations.
     *
     * @param  string  $filePath  Absolute or relative path to a readable migration file
     * @return array{operations: array<int, array<string, mixed>>, line_map: array<int, array<string, mixed>>}
     *
     * @throws \RuntimeException if the file cannot be read
     */
    public function parse(string $filePath): array
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Cannot read migration file: {$filePath}");
        }
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

            // Column operations - standard column types with name parameter
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

            // Common Laravel column helpers (no explicit column name parameter)
            if ($currentTable) {
                // id() - auto-incrementing big integer primary key
                if (preg_match('/->id\s*\(\s*\)/', $trimmed) || preg_match('/->id\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $trimmed, $idMatches)) {
                    $columnName = $idMatches[1] ?? 'id';
                    $this->addOperation('column', 'add', [
                        'table' => $currentTable,
                        'column' => $columnName,
                        'type' => 'bigIncrements',
                        'definition' => $trimmed,
                    ], $lineNumber, $direction);
                }

                // bigIncrements / increments
                if (preg_match('/->(bigIncrements|increments|tinyIncrements|smallIncrements|mediumIncrements)\s*\(\s*[\'"]?([^\'")\s]+)?[\'"]?\s*\)/', $trimmed, $incMatches)) {
                    $columnName = $incMatches[2] ?? 'id';
                    $this->addOperation('column', 'add', [
                        'table' => $currentTable,
                        'column' => $columnName,
                        'type' => $incMatches[1],
                        'definition' => $trimmed,
                    ], $lineNumber, $direction);
                }

                // timestamps() / nullableTimestamps() / timestampsTz()
                if (preg_match('/->(timestamps|nullableTimestamps|timestampsTz|nullableTimestampsTz)\s*\(/', $trimmed)) {
                    $this->addOperation('column', 'add', [
                        'table' => $currentTable,
                        'column' => 'created_at',
                        'type' => 'timestamp',
                        'definition' => $trimmed,
                    ], $lineNumber, $direction);
                    $this->addOperation('column', 'add', [
                        'table' => $currentTable,
                        'column' => 'updated_at',
                        'type' => 'timestamp',
                        'definition' => $trimmed,
                    ], $lineNumber, $direction);
                }

                // softDeletes() / softDeletesTz()
                if (preg_match('/->(softDeletes|softDeletesTz)\s*\(\s*[\'"]?([^\'")\s]*)?[\'"]?\s*\)/', $trimmed, $sdMatches)) {
                    $columnName = ! empty($sdMatches[2]) ? $sdMatches[2] : 'deleted_at';
                    $this->addOperation('column', 'add', [
                        'table' => $currentTable,
                        'column' => $columnName,
                        'type' => 'timestamp',
                        'definition' => $trimmed,
                    ], $lineNumber, $direction);
                }

                // rememberToken()
                if (preg_match('/->rememberToken\s*\(/', $trimmed)) {
                    $this->addOperation('column', 'add', [
                        'table' => $currentTable,
                        'column' => 'remember_token',
                        'type' => 'string',
                        'definition' => $trimmed,
                    ], $lineNumber, $direction);
                }

                // morphs() / nullableMorphs() / uuidMorphs() / nullableUuidMorphs()
                if (preg_match('/->(morphs|nullableMorphs|uuidMorphs|nullableUuidMorphs)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $trimmed, $morphMatches)) {
                    $morphName = $morphMatches[2];
                    $morphType = $morphMatches[1];
                    // morphs creates {name}_type and {name}_id columns
                    $this->addOperation('column', 'add', [
                        'table' => $currentTable,
                        'column' => $morphName.'_type',
                        'type' => 'string',
                        'definition' => $trimmed,
                    ], $lineNumber, $direction);
                    $this->addOperation('column', 'add', [
                        'table' => $currentTable,
                        'column' => $morphName.'_id',
                        'type' => str_contains($morphType, 'uuid') ? 'uuid' : 'unsignedBigInteger',
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

            // Drop column — single string: dropColumn('name') or array: dropColumn(['a','b'])
            if ($currentTable && preg_match('/->dropColumn\s*\(/', $trimmed)) {
                $columns = $this->extractArrayArgument($trimmed);
                if (! empty($columns)) {
                    // Array syntax: dropColumn(['col1','col2'])
                    foreach ($columns as $columnName) {
                        $this->addOperation('column', 'drop', [
                            'table' => $currentTable,
                            'column' => $columnName,
                        ], $lineNumber, $direction);
                    }
                } elseif (preg_match('/->dropColumn\s*\(\s*[\'"]([^\'"]+)[\'"]/', $trimmed, $matches)) {
                    // Single string: dropColumn('col')
                    $this->addOperation('column', 'drop', [
                        'table' => $currentTable,
                        'column' => $matches[1],
                    ], $lineNumber, $direction);
                }
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
                $isForeignId = strpos($trimmed, '->foreignId') !== false;

                // Check for constrained() on the same line or nearby lines (for foreignId shorthand)
                $constrainedInfo = $this->extractConstrainedInfo($trimmed, $lines, $localIndex);

                if ($constrainedInfo) {
                    // foreignId()->constrained() shorthand syntax
                    $referencedTable = $constrainedInfo['table'];
                    $referencedColumn = $constrainedInfo['column'] ?? 'id';
                    $onUpdate = $constrainedInfo['on_update'];
                    $onDelete = $constrainedInfo['on_delete'];

                    // Also add the column itself for foreignId
                    if ($isForeignId && $column) {
                        $this->addOperation('column', 'add', [
                            'table' => $currentTable,
                            'column' => $column,
                            'type' => 'unsignedBigInteger',
                            'definition' => $trimmed,
                        ], $lineNumber, $direction);
                    }

                    $this->addOperation('foreign_key', 'add', [
                        'table' => $currentTable,
                        'columns' => $column ? [$column] : $columns,
                        'referenced_table' => $referencedTable,
                        'referenced_columns' => [$referencedColumn],
                        'on_update' => $onUpdate,
                        'on_delete' => $onDelete,
                    ], $lineNumber, $direction);
                } else {
                    // Try to find references() call on next lines (traditional syntax)
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
                    } elseif ($isForeignId && $column) {
                        // foreignId without constrained() - just add the column
                        $this->addOperation('column', 'add', [
                            'table' => $currentTable,
                            'column' => $column,
                            'type' => 'unsignedBigInteger',
                            'definition' => $trimmed,
                        ], $lineNumber, $direction);
                    }
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
     * Extract constrained() info for foreignId shorthand.
     * Handles: ->constrained() and ->constrained('table_name')
     *
     * @return array|null Array with 'table', 'column', 'on_update', 'on_delete' or null if not found
     */
    protected function extractConstrainedInfo(string $currentLine, array $lines, int $currentIndex): ?array
    {
        // Combine current line and next few lines to handle multi-line chains
        $combinedLines = $currentLine;
        for ($i = $currentIndex + 1; $i < min($currentIndex + 5, count($lines)); $i++) {
            $combinedLines .= ' '.$lines[$i];
        }

        // Check if constrained() exists
        if (strpos($combinedLines, '->constrained') === false) {
            return null;
        }

        // Extract the column name from foreignId('column_name')
        $columnName = null;
        if (preg_match('/->foreignId\s*\(\s*[\'"]([^\'"]+)[\'"]/', $combinedLines, $colMatch)) {
            $columnName = $colMatch[1];
        }

        // Extract explicit table name from constrained('table_name')
        $referencedTable = null;
        if (preg_match('/->constrained\s*\(\s*[\'"]([^\'"]+)[\'"]/', $combinedLines, $tableMatch)) {
            $referencedTable = $tableMatch[1];
        } elseif ($columnName) {
            // Infer table name from column name (user_id -> users)
            $referencedTable = $this->inferTableFromColumn($columnName);
        }

        // Extract onUpdate and onDelete if present
        $onUpdate = $this->extractOnUpdate($combinedLines);
        $onDelete = $this->extractOnDelete($combinedLines);

        // Also check for cascadeOnUpdate/cascadeOnDelete shortcuts
        if (strpos($combinedLines, '->cascadeOnUpdate') !== false) {
            $onUpdate = 'cascade';
        }
        if (strpos($combinedLines, '->cascadeOnDelete') !== false) {
            $onDelete = 'cascade';
        }
        if (strpos($combinedLines, '->nullOnDelete') !== false) {
            $onDelete = 'set null';
        }
        if (strpos($combinedLines, '->restrictOnDelete') !== false) {
            $onDelete = 'restrict';
        }
        if (strpos($combinedLines, '->restrictOnUpdate') !== false) {
            $onUpdate = 'restrict';
        }

        return [
            'table' => $referencedTable,
            'column' => 'id', // constrained() always references 'id' by default
            'on_update' => $onUpdate,
            'on_delete' => $onDelete,
        ];
    }

    /**
     * Infer referenced table name from column name.
     * E.g., 'user_id' -> 'users', 'post_id' -> 'posts'
     */
    protected function inferTableFromColumn(string $columnName): ?string
    {
        // Remove _id suffix and pluralize
        if (str_ends_with($columnName, '_id')) {
            $singular = substr($columnName, 0, -3);

            // Simple pluralization rules
            if (str_ends_with($singular, 'y')) {
                return substr($singular, 0, -1).'ies';
            } elseif (str_ends_with($singular, 's') || str_ends_with($singular, 'x') || str_ends_with($singular, 'ch') || str_ends_with($singular, 'sh')) {
                return $singular.'es';
            } else {
                return $singular.'s';
            }
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
