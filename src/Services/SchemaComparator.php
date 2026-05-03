<?php

namespace Zaeem2396\SchemaLens\Services;

use Illuminate\Support\Collection;

/**
 * Compare two schemas (from SchemaIntrospector::getCurrentSchema()) for the same driver family.
 *
 * Direction: "from" is the reference (e.g. local); "to" is the target (e.g. production).
 * missing_tables_in_to = present in from, absent in to.
 */
class SchemaComparator
{
    /**
     * @param  array<string, array{columns: Collection, indexes: mixed, foreign_keys: mixed, engine: mixed, charset: mixed, collation: mixed}>  $fromSchema
     * @param  array<string, array{columns: Collection, indexes: mixed, foreign_keys: mixed, engine: mixed, charset: mixed, collation: mixed}>  $toSchema
     * @return array{
     *   missing_tables_in_to: list<string>,
     *   extra_tables_in_to: list<string>,
     *   columns_missing_in_to: list<array{table: string, column: string, type: string, nullable: bool}>,
     *   columns_extra_in_to: list<array{table: string, column: string, type: string}>,
     *   type_mismatches: list<array{table: string, column: string, from_type: string, to_type: string}>,
     *   nullable_mismatches: list<array{table: string, column: string, from_nullable: bool, to_nullable: bool}>
     * }
     */
    public function compare(array $fromSchema, array $toSchema): array
    {
        $fromTables = array_keys($fromSchema);
        $toTables = array_keys($toSchema);

        $missingTablesInTo = array_values(array_diff($fromTables, $toTables));
        $extraTablesInTo = array_values(array_diff($toTables, $fromTables));

        $columnsMissingInTo = [];
        $columnsExtraInTo = [];
        $typeMismatches = [];
        $nullableMismatches = [];

        $commonTables = array_intersect($fromTables, $toTables);
        sort($commonTables);

        foreach ($commonTables as $table) {
            /** @var Collection<int, array{name: string, type: string, nullable: bool}> $fromCols */
            $fromCols = $fromSchema[$table]['columns'];
            /** @var Collection<int, array{name: string, type: string, nullable: bool}> $toCols */
            $toCols = $toSchema[$table]['columns'];

            $fromByName = $fromCols->keyBy('name');
            $toByName = $toCols->keyBy('name');

            foreach ($fromByName as $name => $col) {
                if (! $toByName->has($name)) {
                    $columnsMissingInTo[] = [
                        'table' => $table,
                        'column' => $name,
                        'type' => $col['type'],
                        'nullable' => $col['nullable'],
                    ];

                    continue;
                }

                $toCol = $toByName->get($name);
                if ($col['type'] !== $toCol['type']) {
                    $typeMismatches[] = [
                        'table' => $table,
                        'column' => $name,
                        'from_type' => $col['type'],
                        'to_type' => $toCol['type'],
                    ];
                }

                if ($col['nullable'] !== $toCol['nullable']) {
                    $nullableMismatches[] = [
                        'table' => $table,
                        'column' => $name,
                        'from_nullable' => $col['nullable'],
                        'to_nullable' => $toCol['nullable'],
                    ];
                }
            }

            foreach ($toByName as $name => $col) {
                if (! $fromByName->has($name)) {
                    $columnsExtraInTo[] = [
                        'table' => $table,
                        'column' => $name,
                        'type' => $col['type'],
                    ];
                }
            }
        }

        return [
            'missing_tables_in_to' => $missingTablesInTo,
            'extra_tables_in_to' => $extraTablesInTo,
            'columns_missing_in_to' => $columnsMissingInTo,
            'columns_extra_in_to' => $columnsExtraInTo,
            'type_mismatches' => $typeMismatches,
            'nullable_mismatches' => $nullableMismatches,
        ];
    }

    /**
     * True when compare() result has no differences.
     *
     * @param  array<string, mixed>  $diff
     */
    public function isIdentical(array $diff): bool
    {
        foreach ($diff as $items) {
            if (! is_array($items)) {
                continue;
            }
            if (count($items) > 0) {
                return false;
            }
        }

        return true;
    }
}
