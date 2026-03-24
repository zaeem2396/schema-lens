<?php

namespace Zaeem2396\SchemaLens\Tests\Feature;

use Zaeem2396\SchemaLens\Commands\SchemaDiffCommand;
use Zaeem2396\SchemaLens\Tests\TestCase;

class SchemaDiffCommandExitZeroOptionTest extends TestCase
{
    /** @test */
    public function schema_diff_command_defines_exit_zero_option(): void
    {
        $cmd = $this->app->make(SchemaDiffCommand::class);
        $this->assertTrue($cmd->getDefinition()->hasOption('exit-zero'));
    }
}
