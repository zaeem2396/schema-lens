<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Drivers\SpatieBackupDriver;
use Zaeem2396\SchemaLens\Tests\TestCase;

class SpatieBackupDriverTest extends TestCase
{
    /** @test */
    public function driver_name_is_spatie(): void
    {
        $this->assertSame('spatie', (new SpatieBackupDriver)->name());
    }

    /** @test */
    public function supports_is_false_without_spatie_backup_package(): void
    {
        if (class_exists('Spatie\\Backup\\Commands\\BackupCommand')) {
            $this->markTestSkipped('spatie/laravel-backup is installed.');
        }

        $this->assertFalse((new SpatieBackupDriver)->supports());
    }

    /** @test */
    public function create_dump_returns_failure_when_package_missing(): void
    {
        if (class_exists('Spatie\\Backup\\Commands\\BackupCommand')) {
            $this->markTestSkipped('spatie/laravel-backup is installed.');
        }

        $result = (new SpatieBackupDriver)->createDatabaseDump('/tmp/out.sql');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not installed', (string) ($result['message'] ?? ''));
    }
}
