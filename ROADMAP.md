# Schema Lens Roadmap

> **Current Version:** v4.7.0 (release branch) · **Composer / tag:** v1.7.0  
> **Last Updated:** April 2026

This document outlines the planned features and enhancements for Schema Lens. Each feature includes a dedicated git branch name for tracking development progress.

---

## 📋 Table of Contents

- [Roadmap status overview](#roadmap-status-overview)
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

## Roadmap status overview

**Package line (marketing):** v4.7.0 — see [Changelog](#changelog) for dated entries.  
**Status legend:** <span style="color:#1a7f37;font-weight:600;">Completed</span> (green) · <span style="color:#bf8700;font-weight:600;">In progress</span> (yellow) · <span style="color:#cf222e;font-weight:600;">Planned</span> (red) *(GitHub and some viewers may strip inline `style`; the words Completed / In progress / Planned remain readable.)*

### Phase 1 — Core enhancements

| Version | Item | Status | Branch |
|---------|------|--------|--------|
| v1.1.0 | **1.1** Interactive mode for destructive changes | <span style="color:#1a7f37;font-weight:600;">Completed</span> | `feature/interactive-mode` |
| v1.1.1 | **1.2** Single migration file support | <span style="color:#1a7f37;font-weight:600;">Completed</span> | `feature/single-migration` |
| v1.2.0 | **1.3** Dry run / SQL preview | <span style="color:#1a7f37;font-weight:600;">Completed</span> | `feature/dry-run-sql` |
| v1.3.0 | **1.4** Configurable SQL engine | <span style="color:#1a7f37;font-weight:600;">Completed</span> | `feature/configurable-sql-engine` |
| v1.4.x | **1.5** Migration dependency graph (`schema:graph`) | <span style="color:#1a7f37;font-weight:600;">Completed</span> | `feature/dependency-graph` |
| v1.6.0 | **1.6** Schema diff between environments | <span style="color:#1a7f37;font-weight:600;">Completed</span> | `feature/schema-diff` |
| v1.7.0 | **1.7** Backup before migration (`migrate:safe --backup`, `schema:restore`) | <span style="color:#1a7f37;font-weight:600;">Completed</span> | `feature/auto-backup` |

### Phase 2 — Database support expansion

| Version | Item | Status | Branch |
|---------|------|--------|--------|
| *next* | **2.1** PostgreSQL support (introspection, `schema:diff`) | <span style="color:#1a7f37;font-weight:600;">Completed</span> *(pending release)* | `feature/postgresql-support` |
| — | **2.2** SQLite support | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/sqlite-support` |
| — | **2.3** SQL Server support | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/sqlserver-support` |

### Phase 3 — Advanced analysis

| Version | Item | Status | Branch |
|---------|------|--------|--------|
| — | **3.1** Performance impact analysis | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/performance-analysis` |
| — | **3.2** Data loss estimation | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/data-loss-estimation` |
| — | **3.3** Index optimization suggestions | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/index-suggestions` |
| — | **3.4** Schema health check | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/schema-health` |

### Phase 4 — Developer experience

| Version | Item | Status | Branch |
|---------|------|--------|--------|
| — | **4.1** Laravel Tinker integration | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/tinker-integration` |
| — | **4.2** VS Code / JSON schema support | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/vscode-json-schema` |
| — | **4.3** Web dashboard (optional package) | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/web-dashboard` |
| — | **4.4** Better error messages & suggestions | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/better-errors` |

### Phase 5 — Integration & ecosystem

| Version | Item | Status | Branch |
|---------|------|--------|--------|
| — | **5.1** CI/CD pipeline integration | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/cicd-integration` |
| — | **5.2** Slack / Discord notifications | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/notifications` |
| — | **5.3** Spatie Laravel Backup (deep integration) | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/spatie-backup` |
| — | **5.4** Laravel Telescope integration | <span style="color:#cf222e;font-weight:600;">Planned</span> | `feature/telescope-integration` |

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

**Status:** ✅ Released in v1.7.0 (docs line v4.7.0)

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

### 2.1 PostgreSQL Support

**Branch:** `feature/postgresql-support`

**Description:**  
Extend schema introspection and analysis to support PostgreSQL databases.

**Features:**
- PostgreSQL `information_schema` queries
- PostgreSQL-specific data types handling
- Sequence and serial column detection
- PostgreSQL-specific destructive change detection

**Implementation Details:**
```php
// Auto-detection based on connection driver
// Works with existing commands

// PostgreSQL-specific considerations:
// - Sequences for auto-increment
// - Array types
// - JSON/JSONB distinction
// - Schema namespaces
```

**Files to modify/create:**
- `src/Services/SchemaIntrospector.php` (modify)
- `src/Drivers/PostgresSchemaDriver.php` (new)
- `src/Drivers/SchemaDriverInterface.php` (new)

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

### Ideas for v3.0+

| Feature | Description | Branch Name |
|---------|-------------|-------------|
| AI-Powered Suggestions | Use LLMs to suggest optimal migrations | `feature/ai-suggestions` |
| Migration Generator | Generate migrations from schema diff | `feature/migration-generator` |
| Multi-tenancy Support | Handle multi-tenant database schemas | `feature/multi-tenancy` |
| Time Travel | View schema state at any point in time | `feature/time-travel` |
| Schema Versioning | Git-like versioning for database schema | `feature/schema-versioning` |
| Migration Linting | Lint migrations for best practices | `feature/migration-linter` |
| Visual Migration Builder | Drag-and-drop migration creation | `feature/visual-builder` |

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

## Priority Matrix

| Version | Priority | Feature | Effort | Impact | Status |
|---------|----------|---------|--------|--------|--------|
| v1.1.0 | High | Interactive Mode | Medium | High | <span style="color:#1a7f37;font-weight:600;">Completed</span> |
| v1.1.1 | High | Single Migration File | Low | High | <span style="color:#1a7f37;font-weight:600;">Completed</span> |
| v1.2.0 | High | Dry Run SQL Preview | Medium-High | High | <span style="color:#1a7f37;font-weight:600;">Completed</span> |
| v1.3.0 | Medium | Configurable SQL Engine | Low | Medium | <span style="color:#1a7f37;font-weight:600;">Completed</span> |
| v1.6.0 | Medium | Schema Diff | High | High | <span style="color:#1a7f37;font-weight:600;">Completed</span> |
| v1.7.0 | High | Backup before migration (`migrate:safe --backup`, `schema:restore`) | Medium-High | High | <span style="color:#1a7f37;font-weight:600;">Completed</span> |
| — | High | PostgreSQL Support | High | High | <span style="color:#cf222e;font-weight:600;">Planned</span> |
| — | Medium | Performance Analysis | High | Medium | <span style="color:#cf222e;font-weight:600;">Planned</span> |
| — | Medium | CI/CD Integration | Medium | High | <span style="color:#cf222e;font-weight:600;">Planned</span> |
| — | Low | Web Dashboard | Very High | Medium | <span style="color:#cf222e;font-weight:600;">Planned</span> |
| — | Low | AI Suggestions | High | Medium | <span style="color:#cf222e;font-weight:600;">Future</span> |

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
| Apr 2026 | v4.7.0 | `migrate:safe --backup` / `--backup-path`, `schema-lens.backup` config, `BackupManager` + mysqldump drivers, `schema:restore`; roadmap status tables; scenario 23 |
| Apr 2026 | v4.6.0 | `schema:diff` — compare MySQL schemas across two Laravel DB connections; JSON and `--stubs`; optional named connection on `SchemaIntrospector`; docs and scenario 22 |
| Mar 2026 | v4.5.0 | Laravel 13 support (`illuminate/*` ^13, Orchestra Testbench ^11); CI matrix for Laravel 13; README and `pint.json` updates |
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

