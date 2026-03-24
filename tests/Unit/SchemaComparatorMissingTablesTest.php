<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Tests\TestCase;
use Zaeem2396\SchemaLens\Tests\Unit\Concerns\BuildsComparatorSchemaFixtures;

class SchemaComparatorMissingTablesTest extends TestCase
{
    use BuildsComparatorSchemaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpComparator();
    }

    /** @test */
    public function it_detects_missing_tables_in_target(): void
    {
        $from = [
            'users' => $this->emptyTableStructure(),
            'posts' => $this->emptyTableStructure(),
        ];
        $to = [
            'users' => $this->emptyTableStructure(),
        ];

        $diff = $this->comparator->compare($from, $to);

        $this->assertSame(['posts'], $diff['missing_tables_in_to']);
        $this->assertSame([], $diff['extra_tables_in_to']);
        $this->assertFalse($this->comparator->isIdentical($diff));
    }
}
