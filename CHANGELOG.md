# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Nothing yet.

## [4.6.0] - 2026-04-02

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

## [4.5.0] - 2026-03-17

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
