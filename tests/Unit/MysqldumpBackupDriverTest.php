<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Drivers\MysqldumpBackupDriver;
use Zaeem2396\SchemaLens\Tests\TestCase;

class MysqldumpBackupDriverTest extends TestCase
{
    /** @test */
    public function driver_name_is_mysqldump(): void
    {
        $driver = new MysqldumpBackupDriver;

        $this->assertSame('mysqldump', $driver->name());
    }

    /** @test */
    public function supports_returns_false_when_default_connection_is_sqlite(): void
    {
        if ($this->isMySQL()) {
            $this->markTestSkipped('Default connection is MySQL in this environment.');
        }

        $driver = new MysqldumpBackupDriver;

        $this->assertFalse($driver->supports());
    }

    /** @test */
    public function create_dump_returns_failure_when_not_supported(): void
    {
        if ($this->isMySQL()) {
            $this->markTestSkipped('Requires non-MySQL default to assert failure path.');
        }

        $driver = new MysqldumpBackupDriver;
        $result = $driver->createDatabaseDump(sys_get_temp_dir().'/schema-lens-test-dump.sql');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }
}
