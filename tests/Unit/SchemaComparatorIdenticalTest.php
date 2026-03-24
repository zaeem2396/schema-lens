<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Tests\TestCase;
use Zaeem2396\SchemaLens\Tests\Unit\Concerns\BuildsComparatorSchemaFixtures;

class SchemaComparatorIdenticalTest extends TestCase
{
    use BuildsComparatorSchemaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpComparator();
    }

    /** @test */
    public function it_reports_identical_when_schemas_match(): void
    {
        $schema = [
            'users' => $this->tableWithColumns([
                ['name' => 'id', 'type' => 'bigint unsigned', 'nullable' => false],
            ]),
        ];

        $diff = $this->comparator->compare($schema, $schema);

        $this->assertTrue($this->comparator->isIdentical($diff));
    }
}
