# Schema Lens Roadmap

> **Current Version:** v1.8.1  
> **Last Updated:** June 2026

This document outlines the planned features and enhancements for Schema Lens. Each feature includes a dedicated git branch name for tracking development progress.

---

## 📋 Table of Contents

- [Release Roadmap](#release-roadmap)
- [Upcoming Releases](#upcoming-releases)
- [Long-Term Vision](#long-term-vision)
- [Status Legend](#status-legend)
- [What Schema Lens Is Today](#what-schema-lens-is-today)
- [Phase 1: Core Enhancements](#phase-1-core-enhancements)
- [Phase 2: Database Support Expansion](#phase-2-database-support-expansion)
- [Phase 3: Advanced Analysis](#phase-3-advanced-analysis)
- [Phase 4: Developer Experience](#phase-4-developer-experience)
- [Phase 5: Integration & Ecosystem](#phase-5-integration--ecosystem)
- [Future Considerations](#future-considerations)
- [Vision: Schema Intelligence & AI Roadmap](#vision-schema-intelligence--ai-roadmap)
- [Changelog](#changelog)

---

## Release Roadmap

> **Current release:** v1.8.1 — see [Changelog](#changelog) for dated entries.

### Released

| Version | Feature | Status | Branch |
|---------|---------|--------|--------|
| v1.0.0 | Initial Release | 🟢 Complete | `release/v1.0.0` |
| v1.1.0 | Interactive Mode for Destructive Changes | 🟢 Complete | `feature/interactive-mode` |
| v1.1.1 | Single Migration File Support | 🟢 Complete | `feature/single-migration` |
| v1.2.0 | Dry Run / SQL Preview | 🟢 Complete | `feature/dry-run-sql` |
| v1.2.1 | Bug Fixes & Stability Improvements | 🟢 Complete | `hotfix/v1.2.1` |
| v1.2.2 | Migration Parser Improvements | 🟢 Complete | `hotfix/v1.2.2` |
| v1.2.3 | Defensive Error Handling Improvements | 🟢 Complete | `hotfix/v1.2.3` |
| v1.3.0 | Configurable SQL Engine | 🟢 Complete | `feature/configurable-sql-engine` |
| v1.3.1 | Documentation & Examples | 🟢 Complete | `hotfix/v1.3.1` |
| v1.4.0 | Migration Dependency Graph | 🟢 Complete | `feature/dependency-graph` |
| v1.4.1 | Dependency Detection Fixes | 🟢 Complete | `hotfix/v1.4.1` |
| v1.5.0 | Laravel 13 Support | 🟢 Complete | `feature/laravel-13-support` |
| v1.5.1 | CI Matrix Improvements | 🟢 Complete | `hotfix/v1.5.1` |
| v1.6.0 | Schema Diff Between Environments | 🟢 Complete | `feature/schema-diff` |
| v1.6.1 | Schema Diff Accuracy Improvements | 🟢 Complete | `hotfix/v1.6.1` |
| v1.7.0 | Backup Before Migration | 🟢 Complete | `feature/auto-backup` |
| v1.7.1 | Backup Retention Improvements | 🟢 Complete | `hotfix/v1.7.1` |
| v1.8.0 | PostgreSQL Support | 🟢 Complete | `feature/postgresql-support` |
| v1.8.1 | PostgreSQL Stabilization & Bug Fixes | 🟢 Complete | `hotfix/v1.8.1` |

## Upcoming Releases

| Version | Feature | Status | Branch |
|---------|---------|--------|--------|
| v1.8.2 | PostgreSQL Documentation & Examples | 🔴 Planned | `hotfix/v1.8.2` |
| v1.9.0 | SQLite Support | 🔴 Planned | `feature/sqlite-support` |
| v1.9.1 | SQLite Compatibility Fixes | 🔴 Planned | `hotfix/v1.9.1` |
| v1.10.0 | SQL Server Support | 🔴 Planned | `feature/sqlserver-support` |
| v1.10.1 | SQL Server Stabilization | 🔴 Planned | `hotfix/v1.10.1` |
| v1.11.0 | Performance Impact Analysis | 🔴 Planned | `feature/performance-analysis` |
| v1.11.1 | Performance Rule Refinements | 🔴 Planned | `hotfix/v1.11.1` |
| v1.12.0 | Data Loss Estimation | 🔴 Planned | `feature/data-loss-estimation` |
| v1.12.1 | Data Loss Accuracy Improvements | 🔴 Planned | `hotfix/v1.12.1` |
| v1.13.0 | Schema Health Check | 🔴 Planned | `feature/schema-health` |
| v1.13.1 | Health Rule Improvements | 🔴 Planned | `hotfix/v1.13.1` |
| v1.14.0 | Index Optimization Suggestions | 🔴 Planned | `feature/index-suggestions` |
| v1.14.1 | Index Analyzer Improvements | 🔴 Planned | `hotfix/v1.14.1` |
| v1.15.0 | CI/CD Pipeline Integration | 🔴 Planned | `feature/cicd-integration` |
| v1.15.1 | GitHub Actions Enhancements | 🔴 Planned | `hotfix/v1.15.1` |
| v1.16.0 | Better Error Messages & Suggestions | 🔴 Planned | `feature/better-errors` |
| v1.16.1 | Additional Diagnostics Rules | 🔴 Planned | `hotfix/v1.16.1` |
| v1.17.0 | Laravel Tinker Integration | 🔴 Planned | `feature/tinker-integration` |
| v1.17.1 | Tinker Helper Improvements | 🔴 Planned | `hotfix/v1.17.1` |
| v1.18.0 | VS Code / IDE Integration | 🔴 Planned | `feature/vscode-json-schema` |
| v1.18.1 | IDE Export Enhancements | 🔴 Planned | `hotfix/v1.18.1` |
| v1.19.0 | Slack / Discord Notifications | 🔴 Planned | `feature/notifications` |
| v1.19.1 | Notification Templates & Channels | 🔴 Planned | `hotfix/v1.19.1` |
| v1.20.0 | Laravel Telescope Integration | 🔴 Planned | `feature/telescope-integration` |
| v1.20.1 | Telescope Metrics Enhancements | 🔴 Planned | `hotfix/v1.20.1` |

## Long-Term Vision

| Version | Feature | Status | Branch |
|---------|---------|--------|--------|
| v2.0.0 | Schema Intelligence Engine | 🔴 Planned | `feature/schema-intelligence` |
| v2.1.0 | Migration Generator | 🔴 Planned | `feature/migration-generator` |
| v2.2.0 | Schema Versioning | 🔴 Planned | `feature/schema-versioning` |
| v2.3.0 | Time Travel Schema Inspection | 🔴 Planned | `feature/time-travel` |
| v2.4.0 | AI-Powered Migration Suggestions | 🔴 Planned | `feature/ai-suggestions` |
| v2.5.0 | Multi-Tenancy Support | 🔴 Planned | `feature/multi-tenancy` |
| v2.6.0 | Visual Migration Builder | 🔴 Planned | `feature/visual-builder` |

## Status Legend

| Label | Meaning |
|-------|---------|
| 🟢 Complete | Released and available |
| 🟡 In Progress | Currently being developed |
| 🔴 Planned | Scheduled for future development |

Every feature release includes a logical patch lane (`v1.x.1`, `v1.x.2`, …) for bug fixes, documentation, refactoring, and stabilization without forcing every change into a new minor version.

---

## What Schema Lens Is Today

Schema Lens is a **migration preview and safe-migration** package for Laravel:

- **✅ Schema introspection** — `SchemaIntrospector`: tables, columns, indexes, foreign keys via **MySQL / MariaDB** `information_schema` or **PostgreSQL** catalog (`information_schema` + `pg_catalog` for indexes)
- **✅ Migration parsing** — `MigrationParser`: extracts operations from migration files (create, drop, add column, etc.)
- **✅ Destructive change detection** — Flags dangerous operations and risk levels
- **✅ Data export** — CSV/JSON backup of affected data before destructive changes
- **✅ Full database backup (optional)** — `migrate:safe --backup` / config auto backup via `mysqldump`; `schema:restore` prints restore hints
- **✅ SQL preview** — Generate executable SQL from migrations (`--sql`, configurable engine)
- **✅ Structured output** — CLI and JSON formatters; config-driven behavior
- **✅ Laravel package skeleton** — Service provider, config, Artisan commands

It is **not yet** an error-interpretation or AI-powered debugging system; that direction is captured in the [Vision: Schema Intelligence & AI Roadmap](#vision-schema-intelligence--ai-roadmap) section below.

---

## Phase 1: Core Enhancements

### 1.1 Interactive Mode for Destructive Changes ✅ COMPLETED

**Branch:** `feature/interactive-mode`

**Status:** ✅ Implemented in v1.1.0

**Description:**  
Add an interactive confirmation flow when destructive changes are detected. Instead of just showing warnings, prompt the user to confirm each destructive operation before proceeding.

**Features:**
- Step-by-step confirmation for each destructive change
- Option to skip individual operations
- "Confirm all" and "Abort all" shortcuts
- Color-coded risk indicators during confirmation

**Implementation Details:**
```php
// Example usage
php artisan migrate:safe --interactive

// Options during review:
// [y]es    - Approve this migration
// [n]o     - Skip this migration
// [a]ll    - Approve all remaining
// [s]kip   - Skip all remaining
// [q]uit   - Cancel everything
```

**Files modified:**
- `src/Commands/SafeMigrateCommand.php`

---

### 1.2 Single Migration File Support ✅ COMPLETED

**Branch:** `feature/single-migration`

**Status:** ✅ Implemented in v1.1.1

**Description:**  
Allow `migrate:safe` to target a specific migration file instead of processing all pending migrations. This gives developers more granular control over which migration to analyze and execute.

**Features:**
- Specify a single migration file path as argument
- Works with all existing options (`--interactive`, `--no-backup`, etc.)
- Validates that the migration file exists and is pending
- Clear error messages for invalid paths

**Implementation Details:**
```php
// Example usage
php artisan migrate:safe database/migrations/2024_01_15_drop_column.php
php artisan migrate:safe database/migrations/2024_01_15_drop_column.php --interactive
php artisan migrate:safe database/migrations/2024_01_15_drop_column.php --no-backup

// With full path
php artisan migrate:safe /var/www/app/database/migrations/2024_01_15_drop_column.php
```

**Files modified:**
- `src/Commands/SafeMigrateCommand.php`

**Estimated Effort:** Low

---

### 1.3 Dry Run Mode with SQL Preview ✅ COMPLETED

**Branch:** `feature/dry-run-sql`

**Status:** ✅ Implemented in v1.2.0

**Description:**  
Generate the actual SQL statements that would be executed without running them. This helps developers understand exactly what changes will be made to the database.

**Features:**
- Full SQL statement generation for each migration
- Color-coded terminal output (🟢 create/add, 🟡 modify, 🔴 drop)
- Export SQL to file option with `--output`
- Foreign key check wrappers in exported files

**Implementation Details:**
```php
// Example usage
php artisan schema:preview migration.php --sql
php artisan schema:preview migration.php --sql --output=migrations.sql
php artisan schema:preview migration.php --format=sql

// Output includes:
// - Color-coded operations
// - Summary with counts
// - Tip for file export
```

**Files modified:**
- `src/Commands/PreviewMigrationCommand.php`
- `src/Services/SqlGenerator.php` (new)

---

### 1.4 Configurable SQL Engine ✅ COMPLETED

**Branch:** `feature/configurable-sql-engine`

**Status:** ✅ Implemented in v1.3.0

**Description:**  
The table engine in generated SQL (`CREATE TABLE ... ENGINE=...`) is configurable so it can match the target environment (e.g. InnoDB, MyISAM).

**Features:**
- Config option `schema-lens.sql.engine` and env `SCHEMA_LENS_SQL_ENGINE`
- Fallback to default database connection engine, then InnoDB
- Documented in README (config snippet, SQL Preview section, Troubleshooting)

**Files modified:**
- `config/schema-lens.php`
- `src/Services/SqlGenerator.php`

---

### 1.5 Migration Dependency Graph ✅ IMPLEMENTED

**Branch:** `feature/dependency-graph`

**Status:** ✅ Implemented (ready for release)

**Description:**  
Visualize the relationships and dependencies between migrations. Show which migrations depend on others (e.g., foreign key relationships).

**Features:**
- ASCII art dependency tree in terminal
- JSON export of dependency graph (`--format=json`)
- Detect circular dependencies (reported at top of output)
- Optional `--path` for custom migrations directory

**Implementation Details:**
```php
// Example usage
php artisan schema:graph
php artisan schema:graph --path=database/migrations
php artisan schema:graph --format=json

// Output: Migration Dependency Graph with tree (roots = no deps; children = dependents)
// Circular dependencies listed if any
```

**Files created:**
- `src/Commands/DependencyGraphCommand.php`
- `src/Services/DependencyAnalyzer.php`

**Estimated Effort:** High

---

### 1.6 Schema Diff Between Environments ✅ IMPLEMENTED

**Branch:** `feature/schema-diff`

**Status:** ✅ Implemented

**Description:**  
Compare database schemas between different environments (e.g., local vs staging, staging vs production) to identify discrepancies.

**Features:**
- Compare two database connections (MySQL only)
- Highlight missing tables/columns and extras on the target connection
- Show type and nullable mismatches
- Optional migration-style hints (`--stubs`); JSON output (`--format=json`); `--exit-zero` for scripts

**Implementation Details:**
```php
// Example usage
php artisan schema:diff mysql mysql_staging
php artisan schema:diff --from=mysql --to=mysql_staging
php artisan schema:diff mysql mysql_staging --format=json --stubs
```

**Files:**
- `src/Commands/SchemaDiffCommand.php`
- `src/Services/SchemaComparator.php`
- `src/Services/SchemaMigrationStubHint.php`
- `src/Services/SchemaIntrospector.php` (optional named connection)

**Estimated Effort:** High

---

### 1.7 Backup Before Migration ✅ COMPLETED

**Branch:** `feature/auto-backup`

**Status:** ✅ Released in v1.7.0

**Description:**  
Optional full logical database backup (`mysqldump`) before `migrate:safe` runs, with configurable retention and a non-destructive restore hint command.

**Features:**
- `--backup` / `--backup-path` on `migrate:safe`; optional automatic dump when `schema-lens.backup.auto` is true and destructive changes exist
- Drivers: `mysqldump` (default); `spatie` reserved for host apps with `spatie/laravel-backup` (guidance-only until deeper integration)
- Retention pruning for `schema-lens-db-*.sql` in the configured backup directory
- `schema:restore` prints suggested `mysql` invocation (does not run restore)

**Implementation Details:**
```php
// Example usage
php artisan migrate:safe --backup
php artisan migrate:safe --backup --backup-path=/backups/pre-migrate.sql
php artisan schema:restore /backups/pre-migrate.sql

// Config (see config/schema-lens.php → backup)
'schema-lens.backup.auto' => env('SCHEMA_LENS_BACKUP_AUTO', false),
'schema-lens.backup.driver' => env('SCHEMA_LENS_BACKUP_DRIVER', 'mysqldump'),
'schema-lens.backup.retention_days' => (int) env('SCHEMA_LENS_BACKUP_RETENTION_DAYS', 7),
```

**Files:**
- `src/Services/BackupManager.php`
- `src/Drivers/MysqldumpBackupDriver.php`
- `src/Drivers/SpatieBackupDriver.php`
- `src/Contracts/BackupDriverInterface.php`
- `src/DataTransferObjects/BackupResult.php`
- `src/Commands/SafeMigrateCommand.php` (integration)
- `src/Commands/SchemaRestoreCommand.php`

**Estimated Effort:** Medium-High

---

## Phase 2: Database Support Expansion

### 2.1 PostgreSQL Support ✅ IMPLEMENTED (v1.8.0, stabilized v1.8.1)

**Branch:** `feature/postgresql-support`

**Status:** ✅ Released in v1.8.0; **v1.8.1** stabilization (`hotfix/v1.8.1`, tag `v1.8.1`) adds `PostgresCatalogScope`, foreign keys via `constraint_column_usage`, primary index metadata, expanded type formatting, and case-insensitive rollback FK discovery. `--sql` / `SqlGenerator` output remains primarily MySQL-flavored until a dialect switch is introduced.

**Description:**  
Extend schema introspection so PostgreSQL applications can run `schema:preview`, `migrate:safe`, and `schema:diff` against real databases.

**Features:**
- **`PostgresCatalogScope`** (v1.8.1): normalized database + schema scope for all Postgres catalog queries
- **`PostgresInformationSchemaDriver`**: catalogs, columns, FKs via `information_schema`; indexes via `pg_index` aggregation with `primary` flag
- **Type normalization** via `PostgresColumnTypeFormatter` (including `nextval`/serial, arrays, `int2`/`int8`, `interval`)
- **`schema:diff`**: paired `pgsql` connections (same-driver rule as MySQL pairs)
- **`RollbackSimulator`**: Postgres-aware FK discovery and rollback hint DDL (quoted identifiers, `DROP CONSTRAINT`, `DROP INDEX`)

**Still planned / Phase 2+:** fuller multi-schema search_path, Postgres-specific destructive heuristics, `pg_dump` backup driver parity with `migrate:safe --backup`, and Postgres-targeted **`--sql`** output.

**Implementation Details:**
```php
// Auto-detection via connection driver → MySqlInformationSchemaDriver vs PostgresInformationSchemaDriver
$new = new SchemaIntrospector(); // resolves from default connection (mysql, mariadb, or pgsql)

// PostgreSQL connections should set `'schema' => env('DB_SCHEMA','public')` when not using Laravel defaults.

php artisan schema:diff pgsql_a pgsql_b
```

**Files:**
- `src/Contracts/SchemaIntrospectionDriverContract.php`
- `src/Services/Introspection/MySqlInformationSchemaDriver.php`
- `src/Services/Introspection/PostgresCatalogScope.php`
- `src/Services/Introspection/PostgresInformationSchemaDriver.php`
- `src/Services/Introspection/PostgresColumnTypeFormatter.php`
- `src/Services/SchemaIntrospector.php`
- `src/Services/RollbackSimulator.php`
- `src/Commands/SchemaDiffCommand.php`

**Estimated Effort:** High

---

### 2.2 SQLite Support

**Branch:** `feature/sqlite-support`

**Description:**  
Add support for SQLite databases, commonly used in development and testing environments.

**Features:**
- SQLite `sqlite_master` introspection
- Handle SQLite's limited ALTER TABLE support
- Detect SQLite-specific limitations
- Warn about operations not supported in SQLite

**Implementation Details:**
```php
// SQLite limitations to handle:
// - Cannot drop columns (before SQLite 3.35)
// - Cannot rename columns (before SQLite 3.25)
// - Cannot add constraints to existing tables

// Special warnings for SQLite-incompatible migrations
```

**Files to create:**
- `src/Drivers/SqliteSchemaDriver.php`

**Estimated Effort:** Medium

---

### 2.3 SQL Server Support

**Branch:** `feature/sqlserver-support`

**Description:**  
Add support for Microsoft SQL Server databases for enterprise environments.

**Features:**
- SQL Server `INFORMATION_SCHEMA` queries
- Handle SQL Server-specific data types
- Support for schemas (dbo, etc.)
- SQL Server-specific destructive changes

**Files to create:**
- `src/Drivers/SqlServerSchemaDriver.php`

**Estimated Effort:** Medium-High

---

## Phase 3: Advanced Analysis

### 3.1 Performance Impact Analysis

**Branch:** `feature/performance-analysis`

**Description:**  
Analyze the potential performance impact of migrations, especially for large tables.

**Features:**
- Table size estimation before migration
- Lock time prediction
- Index impact analysis
- Recommendations for large table migrations

**Implementation Details:**
```php
// Example usage
php artisan schema:preview --analyze-performance

// Output example:
// ⚠️  PERFORMANCE WARNING
// Table `orders` has ~5.2M rows
// 
// Adding column with default value:
//   Estimated lock time: 45-90 seconds
//   Recommendation: Use pt-online-schema-change or gh-ost
// 
// Adding index on `customer_id`:
//   Estimated time: 2-5 minutes
//   Recommendation: Run during off-peak hours
```

**Files to create:**
- `src/Services/PerformanceAnalyzer.php`
- `src/Services/TableSizeEstimator.php`

**Estimated Effort:** High

---

### 3.2 Data Loss Estimation

**Branch:** `feature/data-loss-estimation`

**Description:**  
Before destructive operations, estimate the amount of data that would be affected or lost.

**Features:**
- Count affected rows for column drops
- Preview sample data that would be lost
- Estimate storage space to be freed
- Historical data analysis

**Implementation Details:**
```php
// Example output:
// ⚠️  DATA LOSS ESTIMATION
// 
// DROP COLUMN users.legacy_field:
//   Rows with data: 45,231 (89.5%)
//   NULL values: 5,302 (10.5%)
//   Sample values: ["value1", "value2", "value3"...]
// 
// DROP TABLE temp_imports:
//   Total rows: 1,234
//   Total size: 2.3 MB
```

**Files to create:**
- `src/Services/DataLossEstimator.php`

**Estimated Effort:** Medium

---

### 3.3 Index Optimization Suggestions

**Branch:** `feature/index-suggestions`

**Description:**  
Analyze current schema and query patterns to suggest index optimizations.

**Features:**
- Detect missing indexes on foreign keys
- Identify unused indexes
- Suggest composite indexes
- Detect redundant indexes

**Implementation Details:**
```php
// Example usage
php artisan schema:analyze-indexes

// Output example:
// INDEX OPTIMIZATION SUGGESTIONS
// 
// Missing Indexes:
//   ✗ orders.customer_id - Foreign key without index
//   ✗ posts.published_at - Frequently filtered, no index
// 
// Potentially Unused:
//   ? users.idx_legacy - No queries in slow log
// 
// Redundant:
//   ⚠ orders.idx_status covered by orders.idx_status_date
```

**Files to create:**
- `src/Commands/AnalyzeIndexesCommand.php`
- `src/Services/IndexAnalyzer.php`

**Estimated Effort:** High

---

### 3.4 Schema Health Check

**Branch:** `feature/schema-health`

**Description:**  
Comprehensive schema health analysis with scoring and recommendations.

**Features:**
- Overall schema health score
- Naming convention compliance
- Data type optimization suggestions
- Normalization analysis
- Best practices checklist

**Implementation Details:**
```php
// Example usage
php artisan schema:health

// Output example:
// SCHEMA HEALTH REPORT
// Overall Score: 78/100
// 
// ✓ Naming Conventions: 95%
// ✓ Primary Keys: All tables have PK
// ⚠ Foreign Keys: 3 missing FK constraints
// ✗ Indexes: 5 FKs without indexes
// ⚠ Data Types: 2 oversized varchar columns
// 
// Top Recommendations:
// 1. Add index on orders.customer_id
// 2. Change posts.title from varchar(500) to varchar(255)
// 3. Add foreign key constraint on comments.post_id
```

**Files to create:**
- `src/Commands/SchemaHealthCommand.php`
- `src/Services/HealthAnalyzer.php`
- `src/Services/NamingConventionChecker.php`

**Estimated Effort:** High

---

## Phase 4: Developer Experience

### 4.1 Laravel Tinker Integration

**Branch:** `feature/tinker-integration`

**Description:**  
Provide helper functions accessible in Laravel Tinker for quick schema inspection.

**Features:**
- `schema()` helper function
- Quick table info lookup
- Column details inspection
- Relationship mapping

**Implementation Details:**
```php
// In Tinker:
>>> schema()->tables()
>>> schema()->table('users')
>>> schema()->columns('users')
>>> schema()->foreignKeys('posts')
>>> schema()->indexes('orders')
```

**Files to create:**
- `src/Helpers/schema.php`
- `src/Services/TinkerSchemaHelper.php`

**Estimated Effort:** Low-Medium

---

### 4.2 VS Code Extension Support

**Branch:** `feature/vscode-json-schema`

**Description:**  
Provide JSON schema and language server protocol support for better IDE integration.

**Features:**
- JSON schema for config file
- Export schema for IDE consumption
- Migration file hover info
- Auto-complete for table/column names

**Implementation Details:**
```php
// Export command for IDE
php artisan schema:export-for-ide

// Generates:
// - .schema-lens/tables.json
// - .schema-lens/columns.json
// - .schema-lens/schema.json
```

**Files to create:**
- `src/Commands/ExportForIdeCommand.php`
- `src/Services/IdeSchemaExporter.php`

**Estimated Effort:** Medium

---

### 4.3 Web Dashboard (Optional Package)

**Branch:** `feature/web-dashboard`

**Description:**  
Optional web-based dashboard for visual schema management and migration preview.

**Features:**
- Visual schema diagram (ERD)
- Interactive migration preview
- Historical migration timeline
- Team collaboration features

**Implementation Details:**
```php
// Separate optional package
composer require zaeem2396/schema-lens-ui

// Access at:
// /schema-lens/dashboard
```

**Files to create:**
- Separate package repository
- Vue.js/React frontend
- API controllers

**Estimated Effort:** Very High (Separate Project)

---

### 4.4 Better Error Messages & Suggestions

**Branch:** `feature/better-errors`

**Description:**  
Improve error messages with actionable suggestions and common fix patterns.

**Features:**
- Context-aware error messages
- Common mistake detection
- Fix suggestions with code examples
- Links to documentation

**Implementation Details:**
```php
// Instead of generic error:
// "Foreign key constraint failed"

// Show:
// ✗ Foreign Key Error
// Cannot add foreign key on `posts.user_id` → `users.id`
// 
// Possible causes:
// 1. Table `users` does not exist yet
//    → Move this migration after create_users_table
// 
// 2. Column types don't match
//    user_id: int unsigned
//    users.id: bigint unsigned
//    → Change user_id to unsignedBigInteger
// 
// 3. Orphan records exist
//    → Run: DELETE FROM posts WHERE user_id NOT IN (SELECT id FROM users)
```

**Files to modify:**
- `src/Exceptions/SchemaLensException.php`
- `src/Services/ErrorDiagnostics.php` (new)

**Estimated Effort:** Medium

---

## Phase 5: Integration & Ecosystem

### 5.1 CI/CD Pipeline Integration

**Branch:** `feature/cicd-integration`

**Description:**  
Better integration with CI/CD pipelines with exit codes and machine-readable output.

**Features:**
- Proper exit codes for different scenarios
- JUnit XML output format
- GitHub Actions annotations
- GitLab CI integration

**Implementation Details:**
```yaml
# GitHub Actions example
- name: Check Migrations
  run: php artisan schema:preview --ci --format=github

# Exit codes:
# 0 - No issues
# 1 - Destructive changes detected
# 2 - Critical errors
```

**Files to modify:**
- `src/Commands/PreviewMigrationCommand.php`
- `src/Formatters/GithubActionsFormatter.php` (new)
- `src/Formatters/JunitFormatter.php` (new)

**Estimated Effort:** Medium

---

### 5.2 Slack/Discord Notifications

**Branch:** `feature/notifications`

**Description:**  
Send notifications when destructive migrations are detected or executed.

**Features:**
- Slack webhook integration
- Discord webhook integration
- Email notifications
- Customizable notification templates

**Implementation Details:**
```php
// Config
'notifications' => [
    'slack' => [
        'webhook' => env('SCHEMA_LENS_SLACK_WEBHOOK'),
        'channel' => '#deployments',
    ],
    'notify_on' => ['destructive', 'error'],
],

// Notification example:
// 🚨 Destructive Migration Detected
// Environment: production
// Migration: drop_legacy_users_table
// Risk Level: HIGH
// Changes: DROP TABLE legacy_users (45,231 rows)
```

**Files to create:**
- `src/Notifications/SlackNotifier.php`
- `src/Notifications/DiscordNotifier.php`
- `src/Notifications/NotificationManager.php`

**Estimated Effort:** Medium

---

### 5.3 Spatie Laravel Backup Integration

**Branch:** `feature/spatie-backup`

**Description:**  
Deep integration with Spatie's Laravel Backup package for seamless backup before migrations.

**Features:**
- Automatic backup trigger
- Selective table backup
- Quick restore integration
- Backup verification

**Implementation Details:**
```php
// Auto-backup before destructive migrations
php artisan migrate:safe --backup

// Uses Spatie backup under the hood
// Respects existing backup configuration
```

**Files to create:**
- `src/Integrations/SpatieBackupIntegration.php`

**Estimated Effort:** Low-Medium

---

### 5.4 Laravel Telescope Integration

**Branch:** `feature/telescope-integration`

**Description:**  
Log all schema changes and migration executions to Laravel Telescope.

**Features:**
- Migration execution logging
- Schema change timeline
- Query logging during migrations
- Performance metrics

**Implementation Details:**
```php
// Automatically logs to Telescope when available
// New Telescope watcher: SchemaLensWatcher

// Logged data:
// - Migration name
// - Execution time
// - SQL queries
// - Rows affected
// - Destructive changes
```

**Files to create:**
- `src/Integrations/TelescopeIntegration.php`
- `src/Watchers/SchemaLensWatcher.php`

**Estimated Effort:** Medium

---

## Future Considerations

Additional ideas not yet scheduled in [Long-Term Vision](#long-term-vision):

| Feature | Description | Branch Name |
|---------|-------------|-------------|
| Migration Linting | Lint migrations for best practices | `feature/migration-linter` |
| Web Dashboard | Optional package for errors, suggestions, query insights | `feature/web-dashboard` |
| Spatie Laravel Backup (deep integration) | Beyond current `mysqldump` backup | `feature/spatie-backup` |

---

## Vision: Schema Intelligence & AI Roadmap

This section merges a **long-term product vision**: evolving from a migration-preview tool toward an **AI-powered Laravel schema and debugging assistant**. Items marked ✅ are **already implemented** in the current codebase; others are planned or exploratory.

### Phase 1 — Foundation (Refactor to scalable engine)

| Item | Status | Notes |
|------|--------|-------|
| Extract Analyzer System (ErrorAnalyzer interface, SqlAnalyzer, ValidationAnalyzer, RouteAnalyzer) | ❌ Not started | Current package is migration/schema focused, not exception-analyzer focused |
| Create Analyzer Engine (dispatch to analyzers, default fallback) | ❌ Not started | Would be new core for error-handling product |
| Standardize Output (type, message, cause, fixes, meta) | ✅ Partial | We have CLI + JSON formatters and structured diff/destructive output; not exception-shaped |
| Config-Driven Behavior | ✅ Done | `config/schema-lens.php` (export, output, sql); enabled options |

### Phase 2 — Schema intelligence (real differentiator)

| Item | Status | Notes |
|------|--------|-------|
| Build Schema Inspector (tables, columns, indexes) | ✅ Done | `SchemaIntrospector::getTables()`, `getColumns()`, `getIndexes()`, `getForeignKeys()`, `getTableStructure()` using `information_schema` |
| Smart SQL Analysis (e.g. “Column not found” → “Did you mean ‘username’?” with Levenshtein/schema) | ❌ Not started | Would upgrade error handling with schema-aware suggestions |
| Relationship Awareness (missing FKs, wrong joins) | ✅ Partial | We introspect and use FKs for diff/destructive detection; no “suggestion” layer yet |
| Validation Intelligence (parse Laravel rules, suggest missing/wrong rules) | ❌ Not started | Out of current scope |

### Phase 3 — Query + runtime intelligence

| Item | Status | Notes |
|------|--------|-------|
| Hook into Query Listener (`DB::listen`) | ❌ Not started | Would be new feature |
| Detect N+1, missing index, full table scan | ❌ Not started | |
| Performance insights (slow/repeated queries, heavy joins) | ❌ Not started | |
| Request lifecycle debugging (request → controller → query → error) | ❌ Not started | |

### Phase 4 — AI integration

| Item | Status | Notes |
|------|--------|-------|
| AI fallback when no analyzer matches | ❌ Not started | |
| Prompt builder (schema + error → explain/fix) | ❌ Not started | |
| AI response structure (cause, explanation, fix, confidence) | ❌ Not started | |
| Local AI support (Ollama, OpenAI, Claude; pluggable driver) | ❌ Not started | |
| Learning system (store error hash, fix, success) | ❌ Not started | |

### Phase 5 — Portfolio / ecosystem

| Item | Status | Notes |
|------|--------|-------|
| Web dashboard (errors, suggestions, query insights) | ❌ Not started | Mentioned in main roadmap as optional package |
| Auto-fix suggestions (e.g. `where('user_name')` → `where('username')`) | ❌ Not started | |
| CI/CD integration (fail on bad queries / missing indexes) | ✅ Partial | We have CI-friendly JSON output and exit codes for destructive changes |
| VS Code / Cursor extension (inline “N+1” etc.) | ❌ Not started | |

**Summary:** The current repo is a **migration-preview and safe-migration** tool with **schema introspection** and **config-driven, structured output**. The vision above extends toward an **error-analysis and AI-assisted debugging** product; merging it here keeps one roadmap while marking what is already done vs. planned.

---

## Contributing

Want to contribute to any of these features? Here's how:

1. **Pick a feature** from this roadmap
2. **Create the branch** using the specified branch name
3. **Open an issue** to discuss implementation approach
4. **Submit a PR** when ready for review

```bash
# Example workflow
git checkout -b feature/interactive-mode
# ... implement feature ...
git push origin feature/interactive-mode
# Open PR on GitHub
```

---

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| Jun 2026 | v1.8.1 | `PostgresCatalogScope`, FK introspection via `constraint_column_usage`, primary index flag, richer Postgres types, rollback FK catalog fix, `PostgreSQLStabilizationTest` |
| May 2026 | v1.8.0 | PostgreSQL introspection via `SchemaIntrospector`, paired `pgsql` support for `schema:diff`, Postgres rollback hints, and PostgreSQL CI job |
| Apr 2026 | v1.7.0 | `migrate:safe --backup` / `--backup-path`, `schema-lens.backup` config, `BackupManager` + mysqldump drivers, `schema:restore`; roadmap status tables; scenario 23 |
| Apr 2026 | v1.6.0 | `schema:diff` — compare MySQL schemas across two Laravel DB connections; JSON and `--stubs`; optional named connection on `SchemaIntrospector`; docs and scenario 22 |
| Mar 2026 | v1.5.0 | Laravel 13 support (`illuminate/*` ^13, Orchestra Testbench ^11); CI matrix for Laravel 13; README and `pint.json` updates |
| Jan 2025 | v1.3.0 | Configurable table engine for generated SQL via `schema-lens.sql.engine` / `SCHEMA_LENS_SQL_ENGINE`; falls back to DB connection then InnoDB |
| Jan 2025 | v1.2.3 | Defensive fixes: MigrationParser throws on unreadable file; commands show stack trace only with `--verbose`; SchemaIntrospector fails fast with clear message when driver is not MySQL |
| Jan 2025 | v1.2.2 | Bug fixes: MigrationParser parses `dropColumn(['col1','col2'])` array syntax; DataExporter throws on CSV fopen failure instead of undefined behavior |
| Jan 2025 | v1.2.1 | Bug fixes: foreignId()->constrained() parsing, timestamps()/id() support, CREATE TABLE SQL generation, dynamic DB connection, metadata file path |
| Jan 2025 | v1.2.0 | SQL preview mode with --sql and --output options |
| Jan 2025 | v1.1.1 | Single migration file support |
| Jan 2025 | v1.1.0 | Interactive mode for destructive changes |
| Dec 2024 | v1.0.0 | Initial release |

---

*This roadmap is a living document and will be updated as development progresses.*

