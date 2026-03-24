<?php

namespace Zaeem2396\SchemaLens\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Zaeem2396\SchemaLens\Tests\TestCase;

class SchemaDiffCommandListedTest extends TestCase
{
    /** @test */
    public function schema_diff_appears_in_artisan_list_output(): void
    {
        Artisan::call('list');
        $output = Artisan::output();

        $this->assertStringContainsString('schema:diff', $output);
    }
}
