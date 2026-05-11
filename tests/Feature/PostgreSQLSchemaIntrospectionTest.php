<?php

namespace Zaeem2396\SchemaLens\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Zaeem2396\SchemaLens\Services\SchemaIntrospector;
use Zaeem2396\SchemaLens\Tests\TestCase;

/**
 * Smoke tests PostgreSQL introspection when DB_CONNECTION=pgsql (see CI postgres job).
 */
class PostgreSQLSchemaIntrospectionTest extends TestCase
{
    /** @test */
    public function it_lists_columns_and_indexes_on_postgresql(): void
    {
        $this->skipIfNotPostgreSQL();

        Schema::dropIfExists('schema_lens_pg_probe');
        Schema::create('schema_lens_pg_probe', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->index(['label']);
        });

        try {
            $inspector = new SchemaIntrospector;
            $this->assertTrue($inspector->tableExists('schema_lens_pg_probe'));

            $columns = $inspector->getColumns('schema_lens_pg_probe');
            $names = $columns->pluck('name')->all();
            $this->assertContains('label', $names);

            $indexes = $inspector->getIndexes('schema_lens_pg_probe');
            $this->assertGreaterThan(0, $indexes->count());
        } finally {
            Schema::dropIfExists('schema_lens_pg_probe');
        }
    }
}
