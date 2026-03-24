<?php

namespace Zaeem2396\SchemaLens\Tests\Feature;

use Zaeem2396\SchemaLens\Tests\TestCase;

class SchemaDiffCommandHelpTest extends TestCase
{
    /** @test */
    public function schema_diff_command_is_registered_with_help(): void
    {
        $this->artisan('help', ['command_name' => 'schema:diff'])
            ->assertSuccessful()
            ->expectsOutputToContain('schema:diff');
    }
}
