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
];
