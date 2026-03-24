<?php

namespace Zaeem2396\SchemaLens\Tests\Feature;

use Zaeem2396\SchemaLens\Commands\SchemaDiffCommand;
use Zaeem2396\SchemaLens\Tests\TestCase;

class SchemaDiffCommandFromToOptionsTest extends TestCase
{
    /** @test */
    public function schema_diff_command_defines_from_and_to_options(): void
    {
        $cmd = $this->app->make(SchemaDiffCommand::class);
        $def = $cmd->getDefinition();

        $this->assertTrue($def->hasOption('from'));
        $this->assertTrue($def->hasOption('to'));
    }
}
