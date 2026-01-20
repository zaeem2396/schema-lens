<?php

namespace Zaeem2396\SchemaLens\Tests\Unit;

use Zaeem2396\SchemaLens\Services\SqlGenerator;
use Zaeem2396\SchemaLens\Tests\TestCase;

class SqlGeneratorTest extends TestCase
{
    protected SqlGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new SqlGenerator;
    }

    // ========================================
    // TABLE OPERATIONS
    // ========================================

    /** @test */
    public function it_generates_create_table_sql(): void
    {
        $operations = collect([
            [
                'type' => 'table',
                'action' => 'create',
                'direction' => 'up',
                'data' => ['table' => 'posts'],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `posts`', $result[0]['sql']);
        $this->assertStringContainsString('ENGINE=InnoDB', $result[0]['sql']);
        $this->assertStringContainsString('utf8mb4', $result[0]['sql']);
    }

    /** @test */
    public function it_generates_drop_table_sql(): void
    {
        $operations = collect([
            [
                'type' => 'table',
                'action' => 'drop',
                'direction' => 'up',
                'data' => ['table' => 'posts'],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertEquals('DROP TABLE IF EXISTS `posts`;', $result[0]['sql']);
    }

    /** @test */
    public function it_generates_modify_table_comment(): void
    {
        $operations = collect([
            [
                'type' => 'table',
                'action' => 'modify',
                'direction' => 'up',
                'data' => ['table' => 'users'],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('ALTER TABLE `users`', $result[0]['sql']);
    }

    // ========================================
    // COLUMN OPERATIONS
    // ========================================

    /** @test */
    public function it_generates_add_column_sql(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => [
                    'table' => 'users',
                    'column' => 'email',
                    'type' => 'string',
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('ALTER TABLE `users` ADD COLUMN `email`', $result[0]['sql']);
        $this->assertStringContainsString('VARCHAR(255)', $result[0]['sql']);
    }

    /** @test */
    public function it_generates_drop_column_sql(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'drop',
                'direction' => 'up',
                'data' => [
                    'table' => 'users',
                    'column' => 'legacy_field',
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertEquals('ALTER TABLE `users` DROP COLUMN `legacy_field`;', $result[0]['sql']);
    }

    /** @test */
    public function it_generates_modify_column_sql(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'modify',
                'direction' => 'up',
                'data' => [
                    'table' => 'users',
                    'column' => 'name',
                    'type' => 'text',
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('ALTER TABLE `users` MODIFY COLUMN `name` TEXT', $result[0]['sql']);
    }

    /** @test */
    public function it_generates_rename_column_sql(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'rename',
                'direction' => 'up',
                'data' => [
                    'table' => 'users',
                    'from' => 'old_name',
                    'to' => 'new_name',
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('RENAME COLUMN `old_name` TO `new_name`', $result[0]['sql']);
    }

    // ========================================
    // INDEX OPERATIONS
    // ========================================

    /** @test */
    public function it_generates_add_regular_index_sql(): void
    {
        $operations = collect([
            [
                'type' => 'index',
                'action' => 'add',
                'direction' => 'up',
                'data' => [
                    'table' => 'users',
                    'columns' => ['email'],
                    'type' => 'index',
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('ADD INDEX', $result[0]['sql']);
        $this->assertStringContainsString('`email`', $result[0]['sql']);
    }

    /** @test */
    public function it_generates_add_unique_index_sql(): void
    {
        $operations = collect([
            [
                'type' => 'index',
                'action' => 'add',
                'direction' => 'up',
                'data' => [
                    'table' => 'users',
                    'columns' => ['email'],
                    'type' => 'unique',
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('ADD UNIQUE INDEX', $result[0]['sql']);
    }

    /** @test */
    public function it_generates_add_primary_key_sql(): void
    {
        $operations = collect([
            [
                'type' => 'index',
                'action' => 'add',
                'direction' => 'up',
                'data' => [
                    'table' => 'users',
                    'columns' => ['id'],
                    'type' => 'primary',
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('ADD PRIMARY KEY', $result[0]['sql']);
    }

    /** @test */
    public function it_generates_composite_index_sql(): void
    {
        $operations = collect([
            [
                'type' => 'index',
                'action' => 'add',
                'direction' => 'up',
                'data' => [
                    'table' => 'orders',
                    'columns' => ['user_id', 'created_at'],
                    'type' => 'index',
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('`user_id`, `created_at`', $result[0]['sql']);
    }

    /** @test */
    public function it_generates_drop_index_sql(): void
    {
        $operations = collect([
            [
                'type' => 'index',
                'action' => 'drop',
                'direction' => 'up',
                'data' => [
                    'table' => 'users',
                    'name' => 'users_email_unique',
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('DROP INDEX `users_email_unique`', $result[0]['sql']);
    }

    // ========================================
    // FOREIGN KEY OPERATIONS
    // ========================================

    /** @test */
    public function it_generates_add_foreign_key_sql(): void
    {
        $operations = collect([
            [
                'type' => 'foreign_key',
                'action' => 'add',
                'direction' => 'up',
                'data' => [
                    'table' => 'posts',
                    'columns' => ['user_id'],
                    'referenced_table' => 'users',
                    'referenced_columns' => ['id'],
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('FOREIGN KEY (`user_id`)', $result[0]['sql']);
        $this->assertStringContainsString('REFERENCES `users` (`id`)', $result[0]['sql']);
    }

    /** @test */
    public function it_generates_foreign_key_with_on_delete_cascade(): void
    {
        $operations = collect([
            [
                'type' => 'foreign_key',
                'action' => 'add',
                'direction' => 'up',
                'data' => [
                    'table' => 'posts',
                    'columns' => ['user_id'],
                    'referenced_table' => 'users',
                    'referenced_columns' => ['id'],
                    'on_delete' => 'cascade',
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('ON DELETE CASCADE', $result[0]['sql']);
    }

    /** @test */
    public function it_generates_foreign_key_with_on_update(): void
    {
        $operations = collect([
            [
                'type' => 'foreign_key',
                'action' => 'add',
                'direction' => 'up',
                'data' => [
                    'table' => 'posts',
                    'columns' => ['user_id'],
                    'referenced_table' => 'users',
                    'referenced_columns' => ['id'],
                    'on_update' => 'cascade',
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('ON UPDATE CASCADE', $result[0]['sql']);
    }

    /** @test */
    public function it_generates_drop_foreign_key_sql(): void
    {
        $operations = collect([
            [
                'type' => 'foreign_key',
                'action' => 'drop',
                'direction' => 'up',
                'data' => [
                    'table' => 'posts',
                    'name' => 'posts_user_id_foreign',
                ],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('DROP FOREIGN KEY `posts_user_id_foreign`', $result[0]['sql']);
    }

    // ========================================
    // LARAVEL TYPE CONVERSIONS
    // ========================================

    /** @test */
    public function it_converts_biginteger_type(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'test', 'column' => 'id', 'type' => 'bigInteger'],
            ],
        ]);

        $result = $this->generator->generate($operations);
        $this->assertStringContainsString('BIGINT', $result[0]['sql']);
    }

    /** @test */
    public function it_converts_unsigned_biginteger_type(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'test', 'column' => 'id', 'type' => 'unsignedBigInteger'],
            ],
        ]);

        $result = $this->generator->generate($operations);
        $this->assertStringContainsString('BIGINT UNSIGNED', $result[0]['sql']);
    }

    /** @test */
    public function it_converts_string_type(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'test', 'column' => 'name', 'type' => 'string'],
            ],
        ]);

        $result = $this->generator->generate($operations);
        $this->assertStringContainsString('VARCHAR(255)', $result[0]['sql']);
    }

    /** @test */
    public function it_converts_text_type(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'test', 'column' => 'content', 'type' => 'text'],
            ],
        ]);

        $result = $this->generator->generate($operations);
        $this->assertStringContainsString('TEXT', $result[0]['sql']);
    }

    /** @test */
    public function it_converts_boolean_type(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'test', 'column' => 'active', 'type' => 'boolean'],
            ],
        ]);

        $result = $this->generator->generate($operations);
        $this->assertStringContainsString('TINYINT(1)', $result[0]['sql']);
    }

    /** @test */
    public function it_converts_json_type(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'test', 'column' => 'metadata', 'type' => 'json'],
            ],
        ]);

        $result = $this->generator->generate($operations);
        $this->assertStringContainsString('JSON', $result[0]['sql']);
    }

    /** @test */
    public function it_converts_uuid_type(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'test', 'column' => 'uuid', 'type' => 'uuid'],
            ],
        ]);

        $result = $this->generator->generate($operations);
        $this->assertStringContainsString('CHAR(36)', $result[0]['sql']);
    }

    /** @test */
    public function it_converts_datetime_type(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'test', 'column' => 'published_at', 'type' => 'datetime'],
            ],
        ]);

        $result = $this->generator->generate($operations);
        $this->assertStringContainsString('DATETIME', $result[0]['sql']);
    }

    /** @test */
    public function it_converts_decimal_type(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'test', 'column' => 'price', 'type' => 'decimal'],
            ],
        ]);

        $result = $this->generator->generate($operations);
        $this->assertStringContainsString('DECIMAL(8,2)', $result[0]['sql']);
    }

    /** @test */
    public function it_uses_default_type_for_unknown(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'test', 'column' => 'field', 'type' => 'unknownType'],
            ],
        ]);

        $result = $this->generator->generate($operations);
        $this->assertStringContainsString('VARCHAR(255)', $result[0]['sql']);
    }

    // ========================================
    // SQL SCRIPT FORMATTING
    // ========================================

    /** @test */
    public function it_formats_sql_script_with_header(): void
    {
        $statements = [
            [
                'operation' => ['type' => 'table', 'action' => 'create'],
                'sql' => 'CREATE TABLE `posts` (...);',
            ],
        ];

        $result = $this->generator->formatAsSqlScript($statements, '2024_01_15_create_posts_table.php');

        $this->assertStringContainsString('-- Migration: 2024_01_15_create_posts_table', $result);
        $this->assertStringContainsString('-- Generated by Schema Lens', $result);
        $this->assertStringContainsString('-- Date:', $result);
    }

    /** @test */
    public function it_includes_foreign_key_checks_in_script(): void
    {
        $statements = [
            [
                'operation' => ['type' => 'table', 'action' => 'drop'],
                'sql' => 'DROP TABLE `posts`;',
            ],
        ];

        $result = $this->generator->formatAsSqlScript($statements, 'test_migration.php');

        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=0;', $result);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=1;', $result);
    }

    /** @test */
    public function it_includes_operation_comments_in_script(): void
    {
        $statements = [
            [
                'operation' => ['type' => 'column', 'action' => 'add'],
                'sql' => 'ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255);',
            ],
        ];

        $result = $this->generator->formatAsSqlScript($statements, 'test_migration.php');

        $this->assertStringContainsString('-- Operation 1: column::add', $result);
    }

    /** @test */
    public function it_includes_end_marker_in_script(): void
    {
        $statements = [
            [
                'operation' => ['type' => 'table', 'action' => 'create'],
                'sql' => 'CREATE TABLE `posts` (...);',
            ],
        ];

        $result = $this->generator->formatAsSqlScript($statements, 'test_migration.php');

        $this->assertStringContainsString('-- End of migration', $result);
    }

    // ========================================
    // EDGE CASES
    // ========================================

    /** @test */
    public function it_returns_empty_array_for_empty_operations(): void
    {
        $operations = collect([]);

        $result = $this->generator->generate($operations);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_skips_unknown_operation_types(): void
    {
        $operations = collect([
            [
                'type' => 'unknown_type',
                'action' => 'create',
                'direction' => 'up',
                'data' => ['table' => 'test'],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertEmpty($result);
    }

    /** @test */
    public function it_skips_unknown_action_types(): void
    {
        $operations = collect([
            [
                'type' => 'table',
                'action' => 'unknown_action',
                'direction' => 'up',
                'data' => ['table' => 'test'],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertEmpty($result);
    }

    /** @test */
    public function it_handles_multiple_operations(): void
    {
        // When columns are part of a CREATE TABLE, they are combined into one statement
        $operations = collect([
            [
                'type' => 'table',
                'action' => 'create',
                'direction' => 'up',
                'data' => ['table' => 'posts'],
            ],
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'posts', 'column' => 'title', 'type' => 'string'],
            ],
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'posts', 'column' => 'body', 'type' => 'text'],
            ],
        ]);

        $result = $this->generator->generate($operations);

        // Columns are now included in the CREATE TABLE statement, so only 1 result
        $this->assertCount(1, $result);
        $this->assertStringContainsString('CREATE TABLE', $result[0]['sql']);
        $this->assertStringContainsString('title', $result[0]['sql']);
        $this->assertStringContainsString('body', $result[0]['sql']);
    }

    /** @test */
    public function it_handles_multiple_operations_on_different_tables(): void
    {
        // When adding columns to existing tables (not part of CREATE TABLE), separate statements are generated
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'users', 'column' => 'nickname', 'type' => 'string'],
            ],
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'posts', 'column' => 'title', 'type' => 'string'],
            ],
        ]);

        $result = $this->generator->generate($operations);

        // Each column addition to an existing table gets its own ALTER TABLE statement
        $this->assertCount(2, $result);
        $this->assertStringContainsString('ALTER TABLE `users`', $result[0]['sql']);
        $this->assertStringContainsString('ALTER TABLE `posts`', $result[1]['sql']);
    }

    /** @test */
    public function it_preserves_operation_order(): void
    {
        $operations = collect([
            [
                'type' => 'table',
                'action' => 'create',
                'direction' => 'up',
                'data' => ['table' => 'first'],
            ],
            [
                'type' => 'table',
                'action' => 'create',
                'direction' => 'up',
                'data' => ['table' => 'second'],
            ],
        ]);

        $result = $this->generator->generate($operations);

        $this->assertStringContainsString('first', $result[0]['sql']);
        $this->assertStringContainsString('second', $result[1]['sql']);
    }

    /** @test */
    public function it_handles_missing_data_gracefully(): void
    {
        $operations = collect([
            [
                'type' => 'column',
                'action' => 'add',
                'direction' => 'up',
                'data' => ['table' => 'existing_table', 'column' => 'test', 'type' => 'string'],
            ],
        ]);

        $result = $this->generator->generate($operations);

        // Should generate ALTER TABLE SQL for column additions to existing tables
        $this->assertCount(1, $result);
        $this->assertStringContainsString('ALTER TABLE', $result[0]['sql']);
    }
}
