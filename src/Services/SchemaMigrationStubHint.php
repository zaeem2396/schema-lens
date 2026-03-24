<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Support\Collection;

/**
 * Build rough Laravel migration hints from introspected column metadata (MySQL COLUMN_TYPE).
 */
class SchemaMigrationStubHint
{
    /**
     * @param  array<string, mixed>  $diff  Result of SchemaComparator::compare()
     * @param  array<string, array{columns: Collection<int, array{name: string, type: string, nullable: bool}>}>  $fromSchema
     */
    public function build(array $diff, array $fromSchema): string
    {
        $lines = ['// Suggested migration hints (review and adjust before running):', ''];

        foreach ($diff['missing_tables_in_to'] as $table) {
            if (! isset($fromSchema[$table])) {
                continue;
            }
            $lines[] = "// Missing table `{$table}` — create migration, e.g.:";
            $lines[] = "Schema::create('{$table}', function (Blueprint \$table) {";
            foreach ($fromSchema[$table]['columns'] as $col) {
                $lines[] = '    $table->'.$this->columnToBlueprintLine($col).';';
            }
            $lines[] = '});';
            $lines[] = '';
        }

        foreach ($diff['columns_missing_in_to'] as $row) {
            $table = $row['table'];
            $colName = $row['column'];
            $type = $row['type'];
            $nullable = $row['nullable'];
            $lines[] = "// Add column `{$table}`.`{$colName}`:";
            $hint = $this->typeToMethod($type, $colName);
            $nullPart = $nullable ? '->nullable()' : '';
            $lines[] = "Schema::table('{$table}', function (Blueprint \$table) {";
            $lines[] = '    $table->'.$hint.$nullPart.';';
            $lines[] = '});';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array{name: string, type: string, nullable: bool}  $col
     */
    protected function columnToBlueprintLine(array $col): string
    {
        $hint = $this->typeToMethod($col['type'], $col['name']);
        $null = $col['nullable'] ? '->nullable()' : '';

        return $hint.$null;
    }

    /**
     * Map MySQL COLUMN_TYPE to a blueprint chain (without leading $table->).
     */
    protected function typeToMethod(string $columnType, string $columnName): string
    {
        $t = strtolower($columnType);

        if (str_contains($t, 'unsigned')) {
            if (str_starts_with($t, 'bigint')) {
                return "unsignedBigInteger('{$columnName}')";
            }
            if (str_starts_with($t, 'int')) {
                return "unsignedInteger('{$columnName}')";
            }
        }

        if (str_starts_with($t, 'bigint')) {
            return "bigInteger('{$columnName}')";
        }
        if (str_starts_with($t, 'int') || str_starts_with($t, 'mediumint') || str_starts_with($t, 'smallint') || str_starts_with($t, 'tinyint')) {
            return "integer('{$columnName}')";
        }
        if (str_starts_with($t, 'varchar')) {
            if (preg_match('/varchar\((\d+)\)/', $t, $m)) {
                return "string('{$columnName}', {$m[1]})";
            }

            return "string('{$columnName}')";
        }
        if (str_starts_with($t, 'char(')) {
            if (preg_match('/char\((\d+)\)/', $t, $m)) {
                return "char('{$columnName}', {$m[1]})";
            }
        }
        if (str_contains($t, 'text') || str_contains($t, 'blob')) {
            if (str_contains($t, 'long')) {
                return "longText('{$columnName}')";
            }
            if (str_contains($t, 'medium')) {
                return "mediumText('{$columnName}')";
            }

            return "text('{$columnName}')";
        }
        if (str_contains($t, 'decimal') || str_contains($t, 'numeric')) {
            return "decimal('{$columnName}', 8, 2) /* adjust precision */";
        }
        if (str_contains($t, 'double') || str_contains($t, 'float')) {
            return "double('{$columnName}')";
        }
        if (str_contains($t, 'datetime') || $t === 'timestamp') {
            return "dateTime('{$columnName}')";
        }
        if ($t === 'date') {
            return "date('{$columnName}')";
        }
        if (str_contains($t, 'json')) {
            return "json('{$columnName}')";
        }
        if (str_contains($t, 'enum(')) {
            return "string('{$columnName}') /* was {$columnType} */";
        }

        return "string('{$columnName}') /* {$columnType} */";
    }
}
