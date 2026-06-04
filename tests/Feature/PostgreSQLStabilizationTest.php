<?php

namespace Zaeem2396\SchemaLens\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Zaeem2396\SchemaLens\Services\MigrationParser;
use Zaeem2396\SchemaLens\Services\RollbackSimulator;
use Zaeem2396\SchemaLens\Services\SchemaIntrospector;
use Zaeem2396\SchemaLens\Tests\TestCase;

class PostgreSQLStabilizationTest extends TestCase
{
    /** @test */
    public function it_introspects_foreign_keys_on_postgresql(): void
    {
        $this->skipIfNotPostgreSQL();

        Schema::dropIfExists('schema_lens_pg_child');
        Schema::dropIfExists('schema_lens_pg_parent');
        Schema::create('schema_lens_pg_parent', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('schema_lens_pg_child', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('schema_lens_pg_parent')->cascadeOnDelete();
        });

        try {
            $inspector = new SchemaIntrospector;
            $fks = $inspector->getForeignKeys('schema_lens_pg_child');
            $this->assertGreaterThan(0, $fks->count());
            $fk = $fks->first();
            $this->assertContains('parent_id', $fk['columns']);
            $this->assertSame('schema_lens_pg_parent', $fk['referenced_table']);
            $this->assertContains('id', $fk['referenced_columns']);
        } finally {
            Schema::dropIfExists('schema_lens_pg_child');
            Schema::dropIfExists('schema_lens_pg_parent');
        }
    }

    /** @test */
    public function it_marks_primary_key_indexes_on_postgresql(): void
    {
        $this->skipIfNotPostgreSQL();

        Schema::dropIfExists('schema_lens_pg_pk');
        Schema::create('schema_lens_pg_pk', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
        });

        try {
            $indexes = (new SchemaIntrospector)->getIndexes('schema_lens_pg_pk');
            $primary = $indexes->firstWhere('primary', true);
            $this->assertNotNull($primary);
            $this->assertContains('id', $primary['columns']);
        } finally {
            Schema::dropIfExists('schema_lens_pg_pk');
        }
    }

    /** @test */
    public function rollback_simulator_quotes_identifiers_for_postgresql(): void
    {
        $this->skipIfNotPostgreSQL();

        $simulator = new RollbackSimulator(new SchemaIntrospector, new MigrationParser);
        $result = $simulator->simulate($this->getFixturePath('2024_01_01_000000_create_users_table.php'));

        $this->assertTrue($result['has_rollback']);
        $sql = collect($result['sql_preview'])->pluck('sql')->implode("\n");
        $this->assertStringContainsString('DROP TABLE', $sql);
        $this->assertStringContainsString('"users"', $sql);
        $this->assertStringNotContainsString('`users`', $sql);
    }

    /** @test */
    public function schema_diff_accepts_two_pgsql_connections(): void
    {
        $this->skipIfNotPostgreSQL();

        Config::set('database.connections.schema_lens_pg_a', Config::get('database.connections.testing'));
        Config::set('database.connections.schema_lens_pg_b', Config::get('database.connections.testing'));

        $this->artisan('schema:diff', [
            'from' => 'schema_lens_pg_a',
            'to' => 'schema_lens_pg_b',
            '--exit-zero' => true,
        ])->assertSuccessful();
    }
}
