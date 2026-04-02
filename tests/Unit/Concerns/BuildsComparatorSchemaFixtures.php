<?php

namespace Zaeem2396\SchemaLens\Tests\Unit\Concerns;

use Illuminate\Support\Collection;
use Zaeem2396\SchemaLens\Services\SchemaComparator;

trait BuildsComparatorSchemaFixtures
{
    protected SchemaComparator $comparator;

    protected function setUpComparator(): void
    {
        $this->comparator = new SchemaComparator;
    }

    /**
     * @return array{columns: Collection, indexes: Collection, foreign_keys: Collection, engine: null, charset: null, collation: null}
     */
    protected function emptyTableStructure(): array
    {
        return [
            'columns' => collect(),
            'indexes' => collect(),
            'foreign_keys' => collect(),
            'engine' => null,
            'charset' => null,
            'collation' => null,
        ];
    }

    /**
     * @param  list<array{name: string, type: string, nullable: bool}>  $cols
     * @return array{columns: Collection, indexes: Collection, foreign_keys: Collection, engine: null, charset: null, collation: null}
     */
    protected function tableWithColumns(array $cols): array
    {
        return [
            'columns' => collect($cols),
            'indexes' => collect(),
            'foreign_keys' => collect(),
            'engine' => null,
            'charset' => null,
            'collation' => null,
        ];
    }
}
