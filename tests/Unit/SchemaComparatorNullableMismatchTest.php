<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Tests\TestCase;
use Zaeem2396\SchemaLens\Tests\Unit\Concerns\BuildsComparatorSchemaFixtures;

class SchemaComparatorNullableMismatchTest extends TestCase
{
    use BuildsComparatorSchemaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpComparator();
    }

    /** @test */
    public function it_detects_nullable_mismatches(): void
    {
        $from = [
            't' => $this->tableWithColumns([
                ['name' => 'x', 'type' => 'varchar(10)', 'nullable' => false],
            ]),
        ];
        $to = [
            't' => $this->tableWithColumns([
                ['name' => 'x', 'type' => 'varchar(10)', 'nullable' => true],
            ]),
        ];

        $diff = $this->comparator->compare($from, $to);

        $this->assertCount(1, $diff['nullable_mismatches']);
        $this->assertFalse($diff['nullable_mismatches'][0]['from_nullable']);
        $this->assertTrue($diff['nullable_mismatches'][0]['to_nullable']);
    }
}
