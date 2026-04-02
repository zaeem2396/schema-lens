<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Services\SchemaMigrationStubHint;
use Zaeem2396\SchemaLens\Tests\TestCase;

class SchemaMigrationStubHintTest extends TestCase
{
    /** @test */
    public function it_includes_create_stub_for_missing_table(): void
    {
        $hint = new SchemaMigrationStubHint;
        $fromSchema = [
            'prefs' => [
                'columns' => collect([
                    ['name' => 'id', 'type' => 'bigint unsigned', 'nullable' => false],
                ]),
            ],
        ];
        $diff = [
            'missing_tables_in_to' => ['prefs'],
            'columns_missing_in_to' => [],
        ];

        $out = $hint->build($diff, $fromSchema);

        $this->assertStringContainsString("Schema::create('prefs'", $out);
        $this->assertStringContainsString('unsignedBigInteger', $out);
    }

    /** @test */
    public function it_includes_table_stub_for_missing_column(): void
    {
        $hint = new SchemaMigrationStubHint;
        $fromSchema = [];
        $diff = [
            'missing_tables_in_to' => [],
            'columns_missing_in_to' => [
                ['table' => 'users', 'column' => 'avatar', 'type' => 'varchar(255)', 'nullable' => true],
            ],
        ];

        $out = $hint->build($diff, $fromSchema);

        $this->assertStringContainsString("Schema::table('users'", $out);
        $this->assertStringContainsString('avatar', $out);
    }
}
