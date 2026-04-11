<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\DataTransferObjects\BackupResult;
use Zaeem2396\SchemaLens\Tests\TestCase;

class BackupResultTest extends TestCase
{
    /** @test */
    public function success_payload_has_path(): void
    {
        $payload = BackupResult::success('/tmp/schema-lens-dump.sql');

        $this->assertTrue($payload['success']);
        $this->assertSame('/tmp/schema-lens-dump.sql', $payload['path']);
    }

    /** @test */
    public function failure_payload_has_message(): void
    {
        $payload = BackupResult::failure('mysqldump missing');

        $this->assertFalse($payload['success']);
        $this->assertSame('mysqldump missing', $payload['message']);
    }

    /** @test */
    public function dto_helpers_reflect_payload(): void
    {
        $ok = new BackupResult(BackupResult::success('/data/dump.sql'));
        $this->assertTrue($ok->succeeded());
        $this->assertSame('/data/dump.sql', $ok->path());
        $this->assertNull($ok->message());

        $bad = new BackupResult(BackupResult::failure('err'));
        $this->assertFalse($bad->succeeded());
        $this->assertNull($bad->path());
        $this->assertSame('err', $bad->message());
    }
}
