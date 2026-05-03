<?php

namespace Zaeem2396\SchemaLens\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Zaeem2396\SchemaLens\Tests\TestCase;

class SchemaDiffCommandTest extends TestCase
{
    /** @test */
    public function it_fails_when_connections_missing(): void
    {
        $this->artisan('schema:diff')
            ->assertFailed()
            ->expectsOutputToContain('Both connections are required');
    }

    /** @test */
    public function it_fails_for_unknown_connection(): void
    {
        $this->artisan('schema:diff', [
            'from' => 'not_a_real_connection_xyz',
            'to' => 'testing',
        ])
            ->assertFailed()
            ->expectsOutputToContain('Unknown database connection');
    }

    /** @test */
    public function it_fails_when_connection_driver_is_not_supported(): void
    {
        $conn = 'schema_lens_sqlite_non_supported';
        Config::set("database.connections.{$conn}", [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->artisan('schema:diff', [
            'from' => $conn,
            'to' => $conn,
        ])
            ->assertFailed()
            ->expectsOutputToContain('must use mysql, mariadb, or pgsql');
    }

    /** @test */
    public function it_fails_when_connections_use_incompatible_driver_families(): void
    {
        Config::set('database.connections.schema_lens_mysql_side', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'database' => 'x',
            'username' => 'x',
            'password' => 'x',
        ]);
        Config::set('database.connections.schema_lens_pgsql_side', [
            'driver' => 'pgsql',
            'host' => '127.0.0.1',
            'database' => 'x',
            'username' => 'x',
            'password' => 'x',
        ]);

        $this->artisan('schema:diff', [
            'from' => 'schema_lens_mysql_side',
            'to' => 'schema_lens_pgsql_side',
        ])
            ->assertFailed()
            ->expectsOutputToContain('same driver family');
    }
}
