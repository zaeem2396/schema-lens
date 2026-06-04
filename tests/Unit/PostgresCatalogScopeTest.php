<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Illuminate\Database\Connection;
use Mockery;
use PHPUnit\Framework\TestCase;
use Zaeem2396\SchemaLens\Services\Introspection\PostgresCatalogScope;

class PostgresCatalogScopeTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function defaults_schema_to_public_when_config_missing(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getConfig')->with('schema')->andReturn(null);
        $connection->shouldReceive('getDatabaseName')->andReturn('TestingDB');

        $scope = PostgresCatalogScope::fromConnection($connection);

        $this->assertSame('public', $scope->schemaName);
        $this->assertSame('testingdb', $scope->normalizedCatalog());
        $this->assertSame('public', $scope->normalizedSchema());
    }

    /** @test */
    public function normalizes_custom_schema_from_connection_config(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getConfig')->with('schema')->andReturn('AppData');
        $connection->shouldReceive('getDatabaseName')->andReturn('my_app');

        $scope = PostgresCatalogScope::fromConnection($connection);

        $this->assertSame('AppData', $scope->schemaName);
        $this->assertSame('appdata', $scope->normalizedSchema());
    }
}
