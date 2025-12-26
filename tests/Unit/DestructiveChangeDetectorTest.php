<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Services\DestructiveChangeDetector;
use Zaeem2396\SchemaLens\Tests\TestCase;

class DestructiveChangeDetectorTest extends TestCase
{
    protected DestructiveChangeDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new DestructiveChangeDetector;
    }

    /** @test */
    public function it_detects_table_drop_as_destructive(): void
    {
        $operations = collect([
            [
                'type' => 'table',
                'action' => 'drop',
                'direction' => 'up',
                'data' => ['table' => 'users'],
                'line' => 10,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertCount(1, $result);
        $this->assertEquals('critical', $result->first()['risk_level']);
        $this->assertContains('users', $result->first()['affected_tables']);
    }

    /** @test */
    public function it_detects_column_drop_as_destructive(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'drop',
                'direction' => 'up',
                'data' => ['table' => 'users', 'column' => 'email'],
                'line' => 15,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertCount(1, $result);
        $this->assertEquals('high', $result->first()['risk_level']);
        $this->assertContains('users', $result->first()['affected_tables']);
    }

    /** @test */
    public function it_detects_column_rename_as_destructive(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'rename',
                'direction' => 'up',
                'data' => ['table' => 'users', 'from' => 'name', 'to' => 'full_name'],
                'line' => 20,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertCount(1, $result);
        $this->assertEquals('high', $result->first()['risk_level']);
    }

    /** @test */
    public function it_detects_index_drop_as_destructive(): void
    {
        $operations = collect([
            [
                'type' => 'index',
                'action' => 'drop',
                'direction' => 'up',
                'data' => ['table' => 'users', 'name' => 'users_email_index'],
                'line' => 25,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertCount(1, $result);
        $this->assertEquals('low', $result->first()['risk_level']);
    }

    /** @test */
    public function it_detects_foreign_key_drop_as_destructive(): void
    {
        $operations = collect([
            [
                'type' => 'foreign_key',
                'action' => 'drop',
                'direction' => 'up',
                'data' => ['table' => 'posts', 'name' => 'posts_user_id_foreign'],
                'line' => 30,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertCount(1, $result);
        $this->assertEquals('medium', $result->first()['risk_level']);
    }

    /** @test */
    public function it_does_not_flag_create_table_as_destructive(): void
    {
        $operations = collect([
            [
                'type' => 'table',
                'action' => 'create',
                'direction' => 'up',
                'data' => ['table' => 'users'],
                'line' => 10,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertCount(0, $result);
    }

    /** @test */
    public function it_does_not_flag_column_add_as_destructive(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'users', 'column' => 'phone', 'type' => 'string'],
                'line' => 15,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertCount(0, $result);
    }

    /** @test */
    public function it_does_not_flag_index_add_as_destructive(): void
    {
        $operations = collect([
            [
                'type' => 'index',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'users', 'name' => 'users_email_index', 'columns' => ['email']],
                'line' => 20,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertCount(0, $result);
    }

    /** @test */
    public function it_detects_multiple_destructive_changes(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'drop',
                'direction' => 'up',
                'data' => ['table' => 'users', 'column' => 'email'],
                'line' => 15,
            ],
            [
                'type' => 'column',
                'action' => 'rename',
                'direction' => 'up',
                'data' => ['table' => 'users', 'from' => 'name', 'to' => 'full_name'],
                'line' => 20,
            ],
            [
                'type' => 'table',
                'action' => 'drop',
                'direction' => 'up',
                'data' => ['table' => 'posts'],
                'line' => 25,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertCount(3, $result);
    }

    /** @test */
    public function it_returns_affected_columns_for_drop_operations(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'drop',
                'direction' => 'up',
                'data' => ['table' => 'users', 'column' => 'email'],
                'line' => 15,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertCount(1, $result);
        $affectedColumns = $result->first()['affected_columns'];
        $this->assertCount(1, $affectedColumns);
        $this->assertEquals('users', $affectedColumns[0]['table']);
        $this->assertEquals('email', $affectedColumns[0]['column']);
    }

    /** @test */
    public function it_returns_affected_columns_for_rename_operations(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'rename',
                'direction' => 'up',
                'data' => ['table' => 'users', 'from' => 'name', 'to' => 'full_name'],
                'line' => 20,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertCount(1, $result);
        $affectedColumns = $result->first()['affected_columns'];

        // Should include both 'from' and 'to' columns
        $columnNames = collect($affectedColumns)->pluck('column')->toArray();
        $this->assertContains('name', $columnNames);
        $this->assertContains('full_name', $columnNames);
    }

    /** @test */
    public function it_includes_operation_in_result(): void
    {
        $operations = collect([
            [
                'type' => 'table',
                'action' => 'drop',
                'direction' => 'up',
                'data' => ['table' => 'users'],
                'line' => 10,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertArrayHasKey('operation', $result->first());
        $this->assertEquals('table', $result->first()['operation']['type']);
        $this->assertEquals('drop', $result->first()['operation']['action']);
    }

    /** @test */
    public function it_returns_empty_collection_for_non_destructive_operations(): void
    {
        $operations = collect([
            [
                'type' => 'table',
                'action' => 'create',
                'direction' => 'up',
                'data' => ['table' => 'users'],
                'line' => 10,
            ],
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'users', 'column' => 'email', 'type' => 'string'],
                'line' => 15,
            ],
            [
                'type' => 'index',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'users', 'name' => 'users_email_index'],
                'line' => 20,
            ],
        ]);

        $result = $this->detector->detect($operations);

        $this->assertCount(0, $result);
    }
}

