# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Nothing yet.

## [1.8.1] - 2026-06-04

### Fixed

- **PostgreSQL foreign keys** — `getForeignKeys()` and rollback referencing-table lookup use `information_schema.constraint_column_usage` (PostgreSQL has no `referenced_table_name` on `key_column_usage`, unlike MySQL).
- **PostgreSQL catalog scope** — `PostgresCatalogScope` centralizes database + `schema` resolution (default `public`) with case-insensitive `information_schema` matching across introspection and rollback FK discovery.
- **Index introspection** — Primary keys expose a `primary` flag; expression-only indexes without column names are omitted to avoid empty index entries.
- **Column types** — `PostgresColumnTypeFormatter` handles `serial` / `bigserial`, `int2` / `int8`, `bool`, `interval`, `inet`, `money`, and `ARRAY` (`udt[]`) types for stable `schema:diff` output.
- **Rollback FK lookup** — `RollbackSimulator::findReferencingTables()` uses normalized catalog/schema names on PostgreSQL (fixes missed matches when catalog casing differs).

### Added

- **`PostgreSQLStabilizationTest`** — Foreign keys, primary indexes, quoted rollback SQL, and paired `pgsql` `schema:diff` smoke tests (CI `postgres-package` job).
- Unit tests for `PostgresCatalogScope` and expanded `PostgresColumnTypeFormatter` coverage.

### Documentation

- README, USAGE, TESTING-SCENARIOS (scenario 25), ROADMAP release table, and `config/schema-lens.php` comments updated for v1.8.1 stabilization.

## [1.8.0] - 2026-05-12

### Added

- **PostgreSQL schema introspection** via `SchemaIntrospector`: tables, columns, indexes (`pg_catalog` / `information_schema`), and foreign keys scoped to the connection `schema` (default `public`).
- **`MySqlInformationSchemaDriver`** and **`PostgresInformationSchemaDriver`** implementing **`SchemaIntrospectionDriverContract`**.
- **`PostgresColumnTypeFormatter`** normalizes Postgres catalog types (`varchar`, `timestamptz`, `nextval`-style extras) toward stable display strings used by **`SchemaComparator`**.
- **`schema:diff`** supports **paired `pgsql` connections** (still requires both connections to share the same driver family as before for MySQL pairs).
- **`RollbackSimulator`** uses the introspector’s connection for dependency discovery and emits **PostgreSQL-flavored rollback hint SQL** (`DROP CONSTRAINT`, `"quoted"` identifiers) when introspection runs on Postgres.

### CI

- Optional **PostgreSQL 16** job (`DB_CONNECTION=pgsql`, `pdo_pgsql`) running the PHPUnit suite with live catalog smoke tests (`PostgreSQLSchemaIntrospectionTest`).

### Documentation

- README requirements, troubleshooting, limitations, `config/schema-lens.php` diff comment block, ROADMAP §2.1, USAGE, and TESTING-SCENARIOS updated for Postgres support.

Before tagging v1.8.0: run `composer check` (Pint, PHPStan, PHPUnit).

## [1.7.0] - 2026-04-03

### Added

- **`migrate:safe --backup` / `--backup-path`**: optional full MySQL logical backup via `mysqldump` before migrations; skipped for `--pretend` and when `--no-backup` is set.
- **Config `schema-lens.backup`**: `auto`, `driver` (`mysqldump` or `spatie` placeholder), `directory`, `retention_days`, `mysqldump_binary` with matching `SCHEMA_LENS_*` / `SCHEMA_LENS_MYSQLDUMP_PATH` environment variables.
- **`BackupManager`**, **`MysqldumpBackupDriver`**, **`SpatieBackupDriver`**, **`BackupDriverInterface`**, **`BackupResult`**.
- **`schema:restore`**: prints a suggested `mysql` CLI restore command for a `.sql` dump (does not execute restore); paths may be absolute or relative to the application base path.

### Documentation

- README, USAGE.md, TESTING-SCENARIOS.md (scenario 23), ROADMAP §1.7, and the roadmap status overview for backup-before-migration and release alignment.

Before tagging v1.7.0: run `composer check` (Pint, PHPStan, PHPUnit).

## [1.6.0] - 2026-04-02

### Added

- **`schema:diff`**: Compare MySQL schemas between two Laravel database connections (`config/database.php` connection names). Reports missing or extra tables and columns, type and nullable mismatches; JSON output via `--format=json`; optional migration-style hints with `--stubs`; exit code **1** when schemas differ unless `--exit-zero`.
- **`SchemaComparator`**, **`SchemaMigrationStubHint`**, and **`SchemaDiffCommand`**.
- **`SchemaIntrospector`** accepts an optional named connection for multi-connection introspection.

### Documentation

- README: feature list, usage, exit codes, troubleshooting, and limitations for `schema:diff`.
- USAGE.md: Advanced Usage — compare schemas between environments; TOC link.
- TESTING-SCENARIOS.md: scenario 22.
- `config/schema-lens.php`: comment block for `schema:diff`.
- ROADMAP.md: section 1.6 marked implemented; `ROADMAP.md` is tracked in the repository.
- Composer keywords: `schema-diff`, `environments`.

## [1.5.0] - 2026-03-17

### Added

- **Laravel 13** support: `illuminate/support`, `illuminate/database`, `illuminate/console`, and `illuminate/filesystem` constraints include `^13.0`.
- **Orchestra Testbench** `^11.0` for package development and tests on Laravel 13.
- CI: Laravel **13.*** matrix on PHP **8.3** and **8.4** (excluded on PHP 8.1 / 8.2 where incompatible).

### Documentation

- README: Laravel 10.x–13.x and PHP 8.3+ note for Laravel 13.
- `pint.json`: explicit Laravel preset for consistent CI/local Pint behavior.

## [1.4.1] - 2025-01-27

### Fixed

- **Migration dependency graph**
  - Edges are now deduplicated: at most one edge per migration pair (e.g. one migration referencing the same table twice no longer produces duplicate edges).
  - When `schema:graph --path=<dir>` is used and the directory is empty or has no migration files, the command now exits with code 1 instead of 0.
  - Clearer messages when no migration files are found and when the migrations path does not exist, with hints to check `--path` or add migration files.

### Documentation

- README: documented exit code behavior for empty path and edge deduplication.
- TESTING-SCENARIOS: scenario 21 updated for path-not-found hint and empty-directory verification.

Before tagging v1.4.1: run `composer check` (Pint, PHPStan, PHPUnit).
