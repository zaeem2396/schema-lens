<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Services\SchemaIntrospector;
use Zaeem2396\SchemaLens\Tests\TestCase;

class SchemaIntrospectorTest extends TestCase
{
    /** @test */
    public function it_throws_when_driver_is_not_mysql(): void
    {
        // Only run when DB is SQLite (default); on MySQL getTables() would not throw
        if ($this->isMySQL()) {
            $this->markTestSkipped('This test requires SQLite to assert non-MySQL driver throws.');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Schema Lens schema introspection requires MySQL');

        $introspector = new SchemaIntrospector;
        $introspector->getTables();
    }
}
