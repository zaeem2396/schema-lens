<?php

namespace Zaeem2396\SchemaLens\Tests\Feature;

use Zaeem2396\SchemaLens\Tests\TestCase;

class SchemaRestoreCommandTest extends TestCase
{
    /** @test */
    public function it_fails_when_dump_file_missing(): void
    {
        $this->artisan('schema:restore', ['file' => 'storage/app/nonexistent-schema-lens-restore.sql'])
            ->assertFailed();
    }

    /** @test */
    public function it_prints_mysql_restore_hint_for_absolute_path(): void
    {
        $path = storage_path('app/schema-lens-restore-test.sql');
        file_put_contents($path, "-- empty dump for test\n");

        try {
            $this->artisan('schema:restore', ['file' => $path])
                ->assertSuccessful()
                ->expectsOutputToContain('mysql');
        } finally {
            @unlink($path);
        }
    }

    /** @test */
    public function it_resolves_path_relative_to_application_base(): void
    {
        $rel = 'storage/app/schema-lens-restore-rel.sql';
        $full = base_path($rel);
        @mkdir(dirname($full), 0755, true);
        file_put_contents($full, '-- test');

        try {
            $this->artisan('schema:restore', ['file' => $rel])
                ->assertSuccessful()
                ->expectsOutputToContain('mysql');
        } finally {
            @unlink($full);
        }
    }
}
