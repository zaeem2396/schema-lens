# Schema Lens

A Laravel package that extends the default Artisan CLI with commands to preview a single migration file against the current MySQL schema before execution. It provides comprehensive schema diff analysis, destructive change detection, automatic data export, and rollback simulation.

## Features

- 🔍 **Schema Diff Analysis**: Compare migration operations against current MySQL schema
- ⚠️ **Destructive Change Detection**: Automatically flags dangerous operations
- 💾 **Automatic Data Export**: Exports affected data to CSV/JSON when destructive changes are detected
- 🔄 **Rollback Simulation**: Preview rollback impact and SQL statements
- 📊 **Line-by-Line Mapping**: Maps each database change back to exact lines in migration file
- 🎨 **Clean CLI Output**: Human-readable formatted output
- 📄 **JSON Export**: Optional JSON report for CI/CD integration
- 🗜️ **Compression**: Automatic compression of exported data
- 📦 **Versioning**: Automatic versioning of exports with restore metadata

## Quick Start

```bash
composer require zaeem2396/schema-lens
php artisan schema:preview database/migrations/your_migration.php
```

📖 **For detailed usage instructions, testing scenarios, and examples, see [USAGE.md](USAGE.md)**

## Installation

```bash
composer require zaeem2396/schema-lens
```

For Laravel 10.x:
```bash
composer require zaeem2396/schema-lens:^1.0
```

For Laravel 11.x:
```bash
composer require zaeem2396/schema-lens:^2.0
```

## Configuration

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=schema-lens-config
```

This will create `config/schema-lens.php` with the following options:

```php
return [
    'export' => [
        'row_limit' => env('SCHEMA_LENS_EXPORT_ROW_LIMIT', 1000),
        'storage_path' => storage_path('app/schema-lens/exports'),
        'compress' => env('SCHEMA_LENS_COMPRESS_EXPORTS', true),
    ],
    'output' => [
        'format' => env('SCHEMA_LENS_OUTPUT_FORMAT', 'cli'),
        'show_line_numbers' => env('SCHEMA_LENS_SHOW_LINE_NUMBERS', true),
    ],
];
```

## Usage

### Basic Usage

Preview a migration file:

```bash
php artisan schema:preview database/migrations/2024_01_01_000000_create_users_table.php
```

Or use a relative path from the migrations directory:

```bash
php artisan schema:preview 2024_01_01_000000_create_users_table.php
```

### JSON Output

Generate a JSON report for CI/CD:

```bash
php artisan schema:preview database/migrations/2024_01_01_000000_create_users_table.php --format=json
```

The JSON report will be saved to `storage/app/schema-lens/report.json` by default.

### Skip Data Export

If you want to preview without exporting data (even if destructive changes are detected):

```bash
php artisan schema:preview database/migrations/2024_01_01_000000_create_users_table.php --no-export
```

## What It Detects

### Schema Changes

- **Tables**: Create, modify, drop
- **Columns**: Add, modify, drop, rename
- **Indexes**: Add, drop
- **Foreign Keys**: Add, drop
- **Engine**: Changes
- **Charset**: Changes
- **Collation**: Changes

### Destructive Operations

The following operations are flagged as destructive:

- `dropTable()` / `dropIfExists()`
- `dropColumn()`
- `dropIndex()`
- `dropForeign()`
- `renameColumn()`
- Constraint removals

## Data Export

When destructive changes are detected, Schema Lens automatically:

1. Exports affected table/column data to CSV and JSON
2. Compresses exports (if enabled)
3. Versions the export with metadata
4. Creates restore instructions

### Export Structure

```
storage/app/schema-lens/exports/
└── 2024_01_01_000000_create_users_table_2024-01-15_10-30-45_v0001/
    ├── users.json
    ├── users.csv
    ├── users.zip (if compression enabled)
    └── restore_metadata.json
```

### Restore Metadata

Each export includes a `restore_metadata.json` file with:

- Export version and timestamp
- Migration file reference
- Affected tables and columns
- Restore instructions
- File paths for all exported data

## Output Examples

### CLI Output

```
╔══════════════════════════════════════════════════════════════╗
║          Schema Lens - Migration Preview Report            ║
╚══════════════════════════════════════════════════════════════╝

📊 SUMMARY
────────────────────────────────────────────────────────────
Tables:        1
Columns:       5
Indexes:       2
Foreign Keys:  1
Engine:        0
Charset:       0
Collation:     0

⚠️  DESTRUCTIVE CHANGES: 1

⚠️  DESTRUCTIVE CHANGES DETECTED
════════════════════════════════════════════════════════════

  Risk Level: HIGH
  Operation:  column:drop
  Line:       45
  Tables:     users
  Columns:    users.email

📋 DETAILED CHANGES
────────────────────────────────────────────────────────────

📦 TABLES:
  ➕ [Line 12] Will create new table 'users'

📝 COLUMNS:
  ➕ [Line 15] Will add new column 'users.id'
  ➕ [Line 16] Will add new column 'users.name'
  🔴 [Line 45] Will DROP column 'users.email' (DESTRUCTIVE)

🔄 ROLLBACK SIMULATION
────────────────────────────────────────────────────────────
  Risk Level: HIGH
  Columns Affected: users.email
```

### JSON Output

```json
{
    "timestamp": "2024-01-15T10:30:45+00:00",
    "summary": {
        "tables": 1,
        "columns": 5,
        "indexes": 2,
        "foreign_keys": 1,
        "destructive_changes_count": 1,
        "has_destructive_changes": true
    },
    "diff": {
        "tables": [...],
        "columns": [...],
        "indexes": [...],
        "foreign_keys": [...]
    },
    "destructive_changes": [...],
    "rollback": {...},
    "exports": [...]
}
```

## Rollback Simulation

Schema Lens analyzes the `down()` method of migrations to:

- Show rollback SQL statements
- Identify dependency break risks
- Warn about foreign key constraints
- Highlight affected tables and columns

## Requirements

- PHP 8.1+
- Laravel 10.x or 11.x
- MySQL 5.7+ or MariaDB 10.2+
- Access to `information_schema` database

## Environment Variables

You can configure Schema Lens using environment variables:

```env
SCHEMA_LENS_EXPORT_ROW_LIMIT=1000
SCHEMA_LENS_COMPRESS_EXPORTS=true
SCHEMA_LENS_OUTPUT_FORMAT=cli
SCHEMA_LENS_SHOW_LINE_NUMBERS=true
```

## CI/CD Integration

### GitHub Actions Example

```yaml
- name: Preview Migration
  run: |
    php artisan schema:preview database/migrations/2024_01_01_000000_create_users_table.php --format=json
    cat storage/app/schema-lens/report.json | jq '.destructive_changes'
```

### GitLab CI Example

```yaml
migration-preview:
  script:
    - php artisan schema:preview database/migrations/2024_01_01_000000_create_users_table.php --format=json
    - |
      if [ $(cat storage/app/schema-lens/report.json | jq '.summary.has_destructive_changes') = "true" ]; then
        echo "⚠️ Destructive changes detected!"
        exit 1
      fi
```

## Limitations

- Currently supports MySQL/MariaDB only
- Requires direct database connection (no cloud services)
- Schema introspection uses `information_schema` tables
- Migration parser supports standard Laravel migration syntax

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Author

**zaeem2396**

GitHub: [@zaeem2396](https://github.com/zaeem2396)

## Support

For issues, questions, or contributions, please open an issue on GitHub.

