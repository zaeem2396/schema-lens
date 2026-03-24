<?php

namespace Zaeem2396\SchemaLens\Tests\Feature;

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
    public function it_fails_when_connection_is_not_mysql(): void
    {
        $this->artisan('schema:diff', [
            'from' => 'testing',
            'to' => 'testing',
        ])
            ->assertFailed()
            ->expectsOutputToContain('mysql driver');
    }
}
