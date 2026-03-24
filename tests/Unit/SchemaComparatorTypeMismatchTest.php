<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Tests\TestCase;
use Zaeem2396\SchemaLens\Tests\Unit\Concerns\BuildsComparatorSchemaFixtures;

class SchemaComparatorTypeMismatchTest extends TestCase
{
    use BuildsComparatorSchemaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpComparator();
    }

    /** @test */
    public function it_detects_type_mismatches(): void
    {
        $from = [
            'posts' => $this->tableWithColumns([
                ['name' => 'body', 'type' => 'text', 'nullable' => true],
            ]),
        ];
        $to = [
            'posts' => $this->tableWithColumns([
                ['name' => 'body', 'type' => 'longtext', 'nullable' => true],
            ]),
        ];

        $diff = $this->comparator->compare($from, $to);

        $this->assertCount(1, $diff['type_mismatches']);
        $this->assertSame('text', $diff['type_mismatches'][0]['from_type']);
        $this->assertSame('longtext', $diff['type_mismatches'][0]['to_type']);
    }
}
