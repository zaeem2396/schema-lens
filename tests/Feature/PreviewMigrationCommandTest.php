<?php

namespace Zaeem2396\SchemaLens\Tests\Feature;

use Illuminate\Support\Facades\File;
use Zaeem2396\SchemaLens\Tests\TestCase;

/**
 * PreviewMigrationCommand feature tests.
 *
 * Note: These tests require MySQL because the command uses SchemaIntrospector
 * which queries MySQL's information_schema tables. Tests will be skipped
 * if not running on MySQL.
 */
class PreviewMigrationCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Only set up database tables if running on MySQL
        if ($this->isMySQL()) {
            try {
                $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
                    $table->id();
                    $table->string('name');
                    $table->string('email')->unique();
                    $table->timestamps();
                });
            } catch (\Exception $e) {
                // Table might already exist
            }
        }
    }

    protected function tearDown(): void
    {
        // Clean up
        if ($this->isMySQL()) {
            try {
                $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('users');
                $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('posts');
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }

        parent::tearDown();
    }

    /** @test */
    public function it_can_preview_a_create_table_migration(): void
    {
        $this->skipIfNotMySQL();

        // First drop users so we can test creating it
        $this->app['db']->connection()->getSchemaBuilder()->dropIfExists('users');

        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_01_000000_create_users_table.php'),
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Analyzing migration')
            ->expectsOutputToContain('users');
    }

    /** @test */
    public function it_can_preview_add_columns_migration(): void
    {
        $this->skipIfNotMySQL();

        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_02_000000_add_columns_to_users.php'),
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Analyzing migration');
    }

    /** @test */
    public function it_detects_destructive_changes_and_returns_failure(): void
    {
        $this->skipIfNotMySQL();

        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_03_000000_drop_columns_from_users.php'),
        ])
            ->assertFailed()
            ->expectsOutputToContain('DESTRUCTIVE');
    }

    /** @test */
    public function it_detects_drop_table_as_destructive(): void
    {
        $this->skipIfNotMySQL();

        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_05_000000_drop_users_table.php'),
        ])
            ->assertFailed()
            ->expectsOutputToContain('DESTRUCTIVE');
    }

    /** @test */
    public function it_can_output_json_format(): void
    {
        $this->skipIfNotMySQL();

        // Clean up any previous report
        $reportPath = storage_path('app/schema-lens/report.json');
        if (File::exists($reportPath)) {
            File::delete($reportPath);
        }

        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_02_000000_add_columns_to_users.php'),
            '--format' => 'json',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('JSON report saved');

        $this->assertFileExists($reportPath);

        $content = File::get($reportPath);
        $this->assertJson($content);

        $data = json_decode($content, true);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('diff', $data);
    }

    /** @test */
    public function it_can_skip_data_export(): void
    {
        $this->skipIfNotMySQL();

        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_03_000000_drop_columns_from_users.php'),
            '--no-export' => true,
        ])
            ->assertFailed()
            ->expectsOutputToContain('DESTRUCTIVE');
    }

    /** @test */
    public function it_shows_error_for_non_existent_migration(): void
    {
        $this->skipIfNotMySQL();

        $this->artisan('schema:preview', [
            'migration' => 'non_existent_migration.php',
        ])
            ->assertFailed()
            ->expectsOutputToContain('not found');
    }

    /** @test */
    public function it_shows_line_numbers_in_output(): void
    {
        $this->skipIfNotMySQL();

        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_02_000000_add_columns_to_users.php'),
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('[Line');
    }

    /** @test */
    public function it_shows_summary_in_output(): void
    {
        $this->skipIfNotMySQL();

        // Note: The summary format includes padding, e.g., "Tables:        1"
        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_02_000000_add_columns_to_users.php'),
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('SUMMARY');
    }

    /** @test */
    public function it_shows_rollback_simulation(): void
    {
        $this->skipIfNotMySQL();

        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_02_000000_add_columns_to_users.php'),
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('ROLLBACK');
    }

    /** @test */
    public function it_can_preview_complex_migration(): void
    {
        $this->skipIfNotMySQL();

        // Add remember_token column for the complex migration to drop
        $this->app['db']->connection()->getSchemaBuilder()->table('users', function ($table) {
            $table->rememberToken();
        });

        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_07_000000_complex_migration.php'),
            '--no-export' => true,
        ])
            ->assertFailed() // Has destructive changes
            ->expectsOutputToContain('DESTRUCTIVE');
    }

    /** @test */
    public function json_output_contains_all_required_sections(): void
    {
        $this->skipIfNotMySQL();

        $reportPath = storage_path('app/schema-lens/report.json');
        if (File::exists($reportPath)) {
            File::delete($reportPath);
        }

        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_02_000000_add_columns_to_users.php'),
            '--format' => 'json',
        ])->assertSuccessful();

        $content = File::get($reportPath);
        $data = json_decode($content, true);

        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('diff', $data);
        $this->assertArrayHasKey('destructive_changes', $data);
        $this->assertArrayHasKey('rollback', $data);
        $this->assertArrayHasKey('exports', $data);
    }

    /** @test */
    public function json_summary_has_correct_structure(): void
    {
        $this->skipIfNotMySQL();

        $reportPath = storage_path('app/schema-lens/report.json');
        if (File::exists($reportPath)) {
            File::delete($reportPath);
        }

        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_02_000000_add_columns_to_users.php'),
            '--format' => 'json',
        ])->assertSuccessful();

        $content = File::get($reportPath);
        $data = json_decode($content, true);

        $this->assertArrayHasKey('tables', $data['summary']);
        $this->assertArrayHasKey('columns', $data['summary']);
        $this->assertArrayHasKey('indexes', $data['summary']);
        $this->assertArrayHasKey('foreign_keys', $data['summary']);
        $this->assertArrayHasKey('destructive_changes_count', $data['summary']);
        $this->assertArrayHasKey('has_destructive_changes', $data['summary']);
    }

    /** @test */
    public function it_can_use_custom_export_path(): void
    {
        $this->skipIfNotMySQL();

        $customPath = storage_path('app/custom-exports');
        File::ensureDirectoryExists($customPath);

        $reportPath = $customPath.'/report.json';
        if (File::exists($reportPath)) {
            File::delete($reportPath);
        }

        $this->artisan('schema:preview', [
            'migration' => $this->getFixturePath('2024_01_02_000000_add_columns_to_users.php'),
            '--format' => 'json',
            '--export-path' => $customPath,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain($customPath);

        $this->assertFileExists($reportPath);

        // Cleanup
        File::deleteDirectory($customPath);
    }
}
