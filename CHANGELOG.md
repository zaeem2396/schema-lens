# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- **Laravel 13** support: `illuminate/*` constraints extended to `^13.0`; dev dependency `orchestra/testbench` `^11.0` for tests on Laravel 13. CI runs tests on PHP 8.3+ with Laravel 13.

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
