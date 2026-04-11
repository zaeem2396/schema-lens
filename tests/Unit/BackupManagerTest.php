<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Services\BackupManager;
use Zaeem2396\SchemaLens\Tests\TestCase;

class BackupManagerTest extends TestCase
{
    /** @test */
    public function prune_removes_old_schema_lens_dump_files(): void
    {
        $relative = 'app/schema-lens-prune-'.uniqid('', true);
        $dir = storage_path($relative);
        $this->assertNotFalse(@mkdir($dir, 0755, true));

        $oldFile = $dir.'/schema-lens-db-1999-01-01_000000.sql';
        $newFile = $dir.'/schema-lens-db-'.date('Y-m-d_His').'.sql';
        touch($oldFile, time() - (10 * 86400));
        touch($newFile, time());

        $manager = new BackupManager;
        $manager->pruneOldBackups($dir, 7);

        $this->assertFileDoesNotExist($oldFile);
        $this->assertFileExists($newFile);

        @unlink($newFile);
        @rmdir($dir);
    }

    /** @test */
    public function prune_with_zero_retention_does_not_delete(): void
    {
        $relative = 'app/schema-lens-prune-zero-'.uniqid('', true);
        $dir = storage_path($relative);
        $this->assertNotFalse(@mkdir($dir, 0755, true));
        $file = $dir.'/schema-lens-db-1990-01-01_000000.sql';
        touch($file, time() - (365 * 86400));

        $manager = new BackupManager;
        $manager->pruneOldBackups($dir, 0);

        $this->assertFileExists($file);

        @unlink($file);
        @rmdir($dir);
    }
}
