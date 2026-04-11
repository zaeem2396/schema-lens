<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;
use Zaeem2396\SchemaLens\Commands\SafeMigrateCommand;
use Zaeem2396\SchemaLens\Tests\TestCase;

class SafeMigrateFullBackupPolicyTest extends TestCase
{
    protected SafeMigrateCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = $this->app->make(SafeMigrateCommand::class);
    }

    protected function bindInput(array $flags): void
    {
        $definition = $this->command->getDefinition();
        $input = new ArrayInput($flags, $definition);
        $this->command->setInput($input);
    }

    protected function policyMethod(): ReflectionMethod
    {
        $m = (new ReflectionClass($this->command))->getMethod('shouldCreateFullDatabaseBackup');
        $m->setAccessible(true);

        return $m;
    }

    /** @test */
    public function backup_flag_requests_full_dump_without_destructive_changes(): void
    {
        $this->bindInput(['--backup' => true]);

        $this->assertTrue($this->policyMethod()->invoke($this->command, []));
    }

    /** @test */
    public function no_backup_disables_full_dump_even_when_backup_flag_set(): void
    {
        $this->bindInput(['--backup' => true, '--no-backup' => true]);

        $this->assertFalse($this->policyMethod()->invoke($this->command, []));
    }

    /** @test */
    public function pretend_disables_full_dump_even_when_backup_flag_set(): void
    {
        $this->bindInput(['--backup' => true, '--pretend' => true]);

        $this->assertFalse($this->policyMethod()->invoke($this->command, []));
    }

    /** @test */
    public function auto_backup_requires_destructive_changes(): void
    {
        config(['schema-lens.backup.auto' => true]);
        $this->bindInput([]);

        $this->assertFalse($this->policyMethod()->invoke($this->command, []));
    }

    /** @test */
    public function auto_backup_true_when_configured_and_destructive_present(): void
    {
        config(['schema-lens.backup.auto' => true]);
        $this->bindInput([]);

        $destructive = ['database/migrations/x.php' => []];

        $this->assertTrue($this->policyMethod()->invoke($this->command, $destructive));
    }
}
