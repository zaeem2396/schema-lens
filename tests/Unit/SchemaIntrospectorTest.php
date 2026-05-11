<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use RuntimeException;
use Zaeem2396\SchemaLens\Services\SchemaIntrospector;
use Zaeem2396\SchemaLens\Tests\TestCase;

class SchemaIntrospectorTest extends TestCase
{
    /** @test */
    public function it_throws_when_driver_is_not_supported(): void
    {
        // Only run when DB is SQLite (default); supported drivers should not throw.
        if ($this->isMySQL() || $this->isPostgreSQL()) {
            $this->markTestSkipped('This test requires SQLite to assert unsupported-driver behavior.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Schema Lens schema introspection requires MySQL, MariaDB, or PostgreSQL');

        $introspector = new SchemaIntrospector;
        $introspector->getTables();
    }

    /** @test */
    public function it_accepts_named_connection_for_constructor(): void
    {
        $introspector = new SchemaIntrospector('testing');

        $this->assertInstanceOf(SchemaIntrospector::class, $introspector);
    }
}
