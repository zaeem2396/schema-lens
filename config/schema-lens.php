<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for data export when destructive changes are detected.
    |
    */

    'export' => [
        // Number of rows to export when destructive changes are detected
        // Set to null to export all rows
        'row_limit' => env('SCHEMA_LENS_EXPORT_ROW_LIMIT', 1000),

        // Directory to store exported data
        // This will be resolved at runtime using Laravel's storage_path() helper
        'storage_path' => 'app/schema-lens/exports',

        // Enable compression for exports
        'compress' => env('SCHEMA_LENS_COMPRESS_EXPORTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for command output formatting.
    |
    */

    'output' => [
        // Default output format: 'cli' or 'json'
        'format' => env('SCHEMA_LENS_OUTPUT_FORMAT', 'cli'),

        // Show detailed line numbers in output
        'show_line_numbers' => env('SCHEMA_LENS_SHOW_LINE_NUMBERS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | SQL Generation Settings
    |--------------------------------------------------------------------------
    |
    | Options for generated SQL (e.g. schema:preview --sql). The table engine
    | is used in CREATE TABLE statements. If not set, the default database
    | connection's engine is used, falling back to InnoDB.
    |
    */

    'sql' => [
        // Table engine for CREATE TABLE (e.g. InnoDB, MyISAM)
        'engine' => env('SCHEMA_LENS_SQL_ENGINE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema diff (`schema:diff`)
    |--------------------------------------------------------------------------
    |
    | No extra options here: pass Laravel connection names from config/database.php.
    | Both connections must use the same driver: mysql/mariadb, or both pgsql
    | (PostgreSQL uses the connection “schema”, default `public`).
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Full database backup (`migrate:safe --backup`)
    |--------------------------------------------------------------------------
    |
    | Optional mysqldump-based logical backup before migrations. Use with care in production.
    |
    */

    'backup' => [
        // When true, create a full dump automatically if destructive changes are detected (unless --no-backup)
        'auto' => env('SCHEMA_LENS_BACKUP_AUTO', false),

        // mysqldump (default) or spatie (requires spatie/laravel-backup; see driver implementation)
        'driver' => env('SCHEMA_LENS_BACKUP_DRIVER', 'mysqldump'),

        // Directory under storage_path() for SQL dumps (filename pattern schema-lens-db-*.sql)
        'directory' => env('SCHEMA_LENS_BACKUP_DIRECTORY', 'app/schema-lens/backups'),

        // Delete matching dumps older than this many days (0 disables pruning)
        'retention_days' => (int) env('SCHEMA_LENS_BACKUP_RETENTION_DAYS', 7),

        // Absolute path to mysqldump binary if not on PATH
        'mysqldump_binary' => env('SCHEMA_LENS_MYSQLDUMP_PATH'),
    ],
];
