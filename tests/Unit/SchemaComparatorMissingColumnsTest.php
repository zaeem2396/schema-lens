<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Tests\TestCase;
use Zaeem2396\SchemaLens\Tests\Unit\Concerns\BuildsComparatorSchemaFixtures;

class SchemaComparatorMissingColumnsTest extends TestCase
{
    use BuildsComparatorSchemaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpComparator();
    }

    /** @test */
    public function it_detects_missing_columns_in_target(): void
    {
        $from = [
            'users' => $this->tableWithColumns([
                ['name' => 'id', 'type' => 'bigint unsigned', 'nullable' => false],
                ['name' => 'email', 'type' => 'varchar(255)', 'nullable' => false],
            ]),
        ];
        $to = [
            'users' => $this->tableWithColumns([
                ['name' => 'id', 'type' => 'bigint unsigned', 'nullable' => false],
            ]),
        ];

        $diff = $this->comparator->compare($from, $to);

        $this->assertCount(1, $diff['columns_missing_in_to']);
        $this->assertSame('email', $diff['columns_missing_in_to'][0]['column']);
        $this->assertSame('users', $diff['columns_missing_in_to'][0]['table']);
    }
}
