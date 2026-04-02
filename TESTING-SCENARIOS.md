# Testing Scenarios Checklist

Quick reference guide for testing Schema Lens in different scenarios. For shipped versions (e.g. **v4.5.0**, **v4.6.0**), see [CHANGELOG.md](CHANGELOG.md).

## ✅ Pre-Testing Setup

- [ ] Laravel application with MySQL database configured
- [ ] Package installed via Composer
- [ ] Database connection working (`php artisan migrate:status`)
- [ ] Some existing tables in database (for testing against existing schema)

---

## Scenario Checklist

### 1. New Table Creation
- [ ] Create migration: `php artisan make:migration create_test_table`
- [ ] Add columns in migration
- [ ] Run: `php artisan schema:preview database/migrations/...create_test_table.php`
- [ ] Verify: Shows table will be created
- [ ] Verify: All columns listed
- [ ] Verify: Rollback shows drop operation

### 2. Add Columns to Existing Table
- [ ] Use existing table (e.g., `users`)
- [ ] Create migration to add columns
- [ ] Run preview command
- [ ] Verify: Existing table detected
- [ ] Verify: New columns shown
- [ ] Verify: Column types correct

### 3. Drop Column (Destructive)
- [ ] Create migration to drop existing column
- [ ] Run preview command
- [ ] Verify: 🔴 Destructive change flagged
- [ ] Verify: Data export created (CSV + JSON)
- [ ] Verify: Export files in `storage/app/schema-lens/exports/`
- [ ] Verify: Restore metadata file exists
- [ ] Check: Export contains column data

### 4. Rename Column (Destructive)
- [ ] Create migration to rename column
- [ ] Run preview command
- [ ] Verify: 🔴 Destructive change flagged
- [ ] Verify: Shows "from" and "to" column names
- [ ] Verify: Data export created

### 5. Drop Table (Critical Destructive)
- [ ] Create migration to drop table
- [ ] Run preview command
- [ ] Verify: 🔴 Critical destructive change
- [ ] Verify: Full table data exported
- [ ] Verify: All columns in export
- [ ] Verify: Dependencies listed (if any)

### 6. Add Index
- [ ] Create migration to add index
- [ ] Run preview command
- [ ] Verify: Index operation shown
- [ ] Verify: Index name and columns correct
- [ ] Verify: Warning if index already exists

### 7. Drop Index (Destructive)
- [ ] Create migration to drop index
- [ ] Run preview command
- [ ] Verify: 🔴 Destructive change flagged
- [ ] Verify: Index name shown

### 8. Add Foreign Key
- [ ] Create migration to add foreign key
- [ ] Run preview command
- [ ] Verify: Foreign key operation shown
- [ ] Verify: Referenced table shown
- [ ] Verify: Cascade rules shown

### 9. Drop Foreign Key (Destructive)
- [ ] Create migration to drop foreign key
- [ ] Run preview command
- [ ] Verify: 🔴 Destructive change flagged
- [ ] Verify: Dependency warning shown

### 10. Modify Column
- [ ] Create migration to modify column (e.g., change type)
- [ ] Run preview command
- [ ] Verify: Column modification shown
- [ ] Verify: Current vs new type shown

### 11. Change Engine/Charset
- [ ] Create migration to change table engine
- [ ] Run preview command
- [ ] Verify: Current engine shown
- [ ] Verify: New engine shown
- [ ] Verify: Change highlighted

### 12. Complex Migration (Multiple Operations)
- [ ] Create migration with multiple operations:
  - Add columns
  - Drop columns
  - Add indexes
  - Modify columns
- [ ] Run preview command
- [ ] Verify: All operations detected
- [ ] Verify: Destructive operations flagged
- [ ] Verify: Line numbers map correctly
- [ ] Verify: Data export for dropped columns only

### 13. Rollback Simulation
- [ ] Create migration with `down()` method
- [ ] Run preview command
- [ ] Verify: Rollback section shown
- [ ] Verify: SQL preview generated
- [ ] Verify: Dependencies identified
- [ ] Verify: Risk level assessed

### 14. JSON Output Format
- [ ] Run: `php artisan schema:preview ... --format=json`
- [ ] Verify: JSON file created at `storage/app/schema-lens/report.json`
- [ ] Verify: JSON is valid
- [ ] Verify: Contains all sections:
  - `summary`
  - `diff`
  - `destructive_changes`
  - `rollback`
  - `exports`

### 15. No Export Flag
- [ ] Run: `php artisan schema:preview ... --no-export`
- [ ] Verify: Destructive changes still detected
- [ ] Verify: No export files created
- [ ] Verify: Warning still shown

### 16. Custom Export Path
- [ ] Run: `php artisan schema:preview ... --format=json --export-path=/tmp`
- [ ] Verify: JSON report saved to custom path

### 17. Migration File Not Found
- [ ] Run: `php artisan schema:preview nonexistent.php`
- [ ] Verify: Error message shown
- [ ] Verify: Exit code is failure

### 18. Empty Migration
- [ ] Create migration with empty `up()` method
- [ ] Run preview command
- [ ] Verify: No errors
- [ ] Verify: Shows no changes

### 19. Migration with Only down() Method
- [ ] Create migration with only `down()` method
- [ ] Run preview command
- [ ] Verify: No errors
- [ ] Verify: Rollback simulation works

### 20. Compression Test
- [ ] Ensure `SCHEMA_LENS_COMPRESS_EXPORTS=true`
- [ ] Create migration with destructive change
- [ ] Run preview command
- [ ] Verify: ZIP file created
- [ ] Verify: ZIP contains CSV and JSON files

### 21. Migration Dependency Graph

Run `composer check` (Pint, PHPStan, PHPUnit) before release when changing this command.

- [ ] Run: `php artisan schema:graph`
- [ ] Verify: Output shows "Migration Dependency Graph"
- [ ] Verify: Migrations listed (e.g. create_users_table, create_posts_with_foreign_key)
- [ ] Verify: Tree shows dependencies (posts depending on users)
- [ ] Run: `php artisan schema:graph --format=json`
- [ ] Verify: Valid JSON with `migrations`, `nodes`, `edges`, `circular`
- [ ] Run: `php artisan schema:graph --path=nonexistent`
- [ ] Verify: Error message, hint to check directory, and non-zero exit code
- [ ] Run: `php artisan schema:graph --path=<empty-directory>` (directory with no .php files)
- [ ] Verify: "No migration files found" message and non-zero exit code

### 22. Schema diff between environments (`schema:diff`)

Requires two **MySQL** connections in `config/database.php` (e.g. `mysql` and `mysql_staging`). On SQLite-only test apps, rely on package CI for validation.

- [ ] Run: `php artisan schema:diff` (no arguments) — expect error that both connections are required
- [ ] Run: `php artisan schema:diff unknown_conn mysql` — expect unknown connection error
- [ ] With two MySQL connections configured, run: `php artisan schema:diff conn_a conn_b` and verify summary output; exit code 1 when schemas differ
- [ ] Run: `php artisan schema:diff conn_a conn_b --format=json` — JSON includes `from_connection`, `to_connection`, `identical`, `diff`
- [ ] With schema differences, run: `php artisan schema:diff conn_a conn_b --stubs` — hints contain `Schema::`
- [ ] Run: `php artisan schema:diff conn_a conn_b --exit-zero` — exit 0 even when schemas differ
- [ ] Run `composer check` before release when changing this command

---

## Edge Cases to Test

### Edge Case 1: Table Doesn't Exist
- [ ] Preview migration that modifies non-existent table
- [ ] Verify: Error/warning shown

### Edge Case 2: Column Doesn't Exist
- [ ] Preview migration that modifies non-existent column
- [ ] Verify: Error/warning shown

### Edge Case 3: Large Data Export
- [ ] Table with many rows (>1000)
- [ ] Set `SCHEMA_LENS_EXPORT_ROW_LIMIT=100`
- [ ] Run preview with destructive change
- [ ] Verify: Only 100 rows exported

### Edge Case 4: Multiple Destructive Changes
- [ ] Migration with multiple drop operations
- [ ] Run preview command
- [ ] Verify: All destructive changes flagged
- [ ] Verify: All affected data exported

### Edge Case 5: Foreign Key Dependencies
- [ ] Drop table that has foreign keys referencing it
- [ ] Run preview command
- [ ] Verify: Dependencies shown in rollback
- [ ] Verify: Warning about breaking foreign keys

---

## Performance Testing

### Large Migration File
- [ ] Migration with 50+ operations
- [ ] Run preview command
- [ ] Verify: Completes in reasonable time (<30 seconds)
- [ ] Verify: All operations detected

### Many Tables in Database
- [ ] Database with 100+ tables
- [ ] Run preview command
- [ ] Verify: Schema introspection completes
- [ ] Verify: Performance acceptable

---

## Integration Testing

### With Existing Laravel Migrations
- [ ] Run on real Laravel project
- [ ] Test with actual migrations
- [ ] Verify: Works with Laravel's migration structure

### With CI/CD Pipeline
- [ ] Add to GitHub Actions
- [ ] Add to GitLab CI
- [ ] Verify: JSON output works
- [ ] Verify: Exit codes work correctly

---

## Regression Testing

After making changes to the package:

1. [ ] Run all scenarios above
2. [ ] Verify: No new errors
3. [ ] Verify: Output format unchanged
4. [ ] Verify: Export files structure unchanged
5. [ ] Verify: JSON schema unchanged

---

## Quick Test Script

Save as `test-scenarios.sh`:

```bash
#!/bin/bash

echo "Testing Schema Lens Scenarios..."

# Test 1: Basic preview
php artisan schema:preview database/migrations/$(ls -t database/migrations/ | head -1) --format=json

# Test 2: Check JSON output
if [ -f storage/app/schema-lens/report.json ]; then
    echo "✅ JSON report created"
    jq '.summary' storage/app/schema-lens/report.json
else
    echo "❌ JSON report not found"
fi

# Test 3: Check for destructive changes
if jq -e '.summary.has_destructive_changes == true' storage/app/schema-lens/report.json > /dev/null 2>&1; then
    echo "⚠️ Destructive changes detected"
    jq '.destructive_changes' storage/app/schema-lens/report.json
fi

echo "Testing complete!"
```

Run with: `chmod +x test-scenarios.sh && ./test-scenarios.sh`

---

## Success Criteria

All tests pass when:
- ✅ No PHP errors or warnings
- ✅ All operations detected correctly
- ✅ Destructive changes flagged
- ✅ Data exports created when needed
- ✅ JSON output is valid
- ✅ Line numbers map correctly
- ✅ Rollback simulation works
- ✅ Exit codes are correct

