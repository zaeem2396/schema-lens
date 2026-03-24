<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Services\SchemaComparator;
use Zaeem2396\SchemaLens\Tests\TestCase;

class SchemaComparatorIsIdenticalEmptyDiffTest extends TestCase
{
    /** @test */
    public function is_identical_returns_true_for_empty_diff_arrays(): void
    {
        $c = new SchemaComparator;
        $diff = [
            'missing_tables_in_to' => [],
            'extra_tables_in_to' => [],
            'columns_missing_in_to' => [],
            'columns_extra_in_to' => [],
            'type_mismatches' => [],
            'nullable_mismatches' => [],
        ];

        $this->assertTrue($c->isIdentical($diff));
    }
}
