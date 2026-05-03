<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Zaeem2396\SchemaLens\Services\Introspection\PostgresColumnTypeFormatter;

/**
 * PostgresColumnTypeFormatter is pure (no DB) — runnable on SQLite default CI matrix.
 */
class PostgresColumnTypeFormatterTest extends TestCase
{
    /** @test */
    public function formats_character_varying_with_length_as_varchar(): void
    {
        $col = (object) [
            'data_type' => 'character varying',
            'udt_name' => 'varchar',
            'character_maximum_length' => '255',
        ];
        $this->assertSame('varchar(255)', PostgresColumnTypeFormatter::format($col));
    }

    /** @test */
    public function marks_nextval_defaults_as_extra_auto_increment_placeholder(): void
    {
        $this->assertSame(
            'auto_increment',
            PostgresColumnTypeFormatter::extraFromDefault('nextval(\'users_id_seq\'::regclass)')
        );
    }

    /** @test */
    public function formats_numeric_with_precision_and_scale(): void
    {
        $col = (object) [
            'data_type' => 'numeric',
            'udt_name' => 'numeric',
            'numeric_precision' => 10,
            'numeric_scale' => 2,
        ];
        $this->assertSame('decimal(10,2)', PostgresColumnTypeFormatter::format($col));
    }

    /** @test */
    public function resolves_user_defined_type_via_udt_name(): void
    {
        $col = (object) [
            'data_type' => 'USER-DEFINED',
            'udt_name' => 'citext',
        ];
        $this->assertSame('citext', PostgresColumnTypeFormatter::format($col));
    }
}
