<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Tests\TestCase;
use Zaeem2396\SchemaLens\Tests\Unit\Concerns\BuildsComparatorSchemaFixtures;

class SchemaComparatorExtraColumnsTest extends TestCase
{
    use BuildsComparatorSchemaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpComparator();
    }

    /** @test */
    public function it_detects_extra_columns_in_target(): void
    {
        $from = [
            'users' => $this->tableWithColumns([
                ['name' => 'id', 'type' => 'bigint unsigned', 'nullable' => false],
            ]),
        ];
        $to = [
            'users' => $this->tableWithColumns([
                ['name' => 'id', 'type' => 'bigint unsigned', 'nullable' => false],
                ['name' => 'legacy_flag', 'type' => 'tinyint(1)', 'nullable' => true],
            ]),
        ];

        $diff = $this->comparator->compare($from, $to);

        $this->assertCount(1, $diff['columns_extra_in_to']);
        $this->assertSame('legacy_flag', $diff['columns_extra_in_to'][0]['column']);
    }
}
