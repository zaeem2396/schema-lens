# Schema Lens - Complete Usage Guide

## Table of Contents
1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Basic Usage](#basic-usage)
4. [Advanced Usage](#advanced-usage)
5. [Testing Scenarios](#testing-scenarios)
6. [Common Use Cases](#common-use-cases)
7. [Troubleshooting](#troubleshooting)
8. [CI/CD Integration](#cicd-integration)

---

## Installation

### Step 1: Install via Composer

```bash
composer require zaeem2396/schema-lens
```

### Step 2: Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=schema-lens-config
```

This creates `config/schema-lens.php` in your Laravel project.

### Step 3: Verify Installation

```bash
php artisan schema:preview --help
```

You should see the command help output.

---

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Limit number of rows exported when destructive changes are detected
# Set to null or 0 to export all rows
SCHEMA_LENS_EXPORT_ROW_LIMIT=1000

# Enable/disable compression for exports
SCHEMA_LENS_COMPRESS_EXPORTS=true

# Default output format: 'cli' or 'json'
SCHEMA_LENS_OUTPUT_FORMAT=cli

# Show detailed line numbers in output
SCHEMA_LENS_SHOW_LINE_NUMBERS=true
```

### Config File Structure

After publishing, edit `config/schema-lens.php`:

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

---

## Basic Usage

### Preview a Single Migration

```bash
# Using full path
php artisan schema:preview database/migrations/2024_01_15_100000_create_users_table.php

# Using filename (searches in database/migrations)
php artisan schema:preview 2024_01_15_100000_create_users_table.php

# Using partial filename
php artisan schema:preview create_users_table
```

### Output Formats

**CLI Format (Default):**
```bash
php artisan schema:preview database/migrations/2024_01_15_100000_create_users_table.php
```

**JSON Format:**
```bash
php artisan schema:preview database/migrations/2024_01_15_100000_create_users_table.php --format=json
```

The JSON report will be saved to `storage/app/schema-lens/report.json`

### Skip Data Export

If you want to preview without exporting data (even if destructive changes are detected):

```bash
php artisan schema:preview database/migrations/2024_01_15_100000_create_users_table.php --no-export
```

---

## Advanced Usage

### Custom Export Path

```bash
php artisan schema:preview database/migrations/2024_01_15_100000_create_users_table.php \
    --format=json \
    --export-path=/path/to/custom/location
```

### Preview Before Migration

**Recommended Workflow:**

1. **Create your migration:**
   ```bash
   php artisan make:migration create_products_table
   ```

2. **Edit the migration file** in `database/migrations/`

3. **Preview before running:**
   ```bash
   php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_create_products_table.php
   ```

4. **Review the output** for destructive changes

5. **If safe, run the migration:**
   ```bash
   php artisan migrate
   ```

### Batch Preview Multiple Migrations

Create a simple script `preview-migrations.sh`:

```bash
#!/bin/bash
for migration in database/migrations/*.php; do
    echo "Previewing: $migration"
    php artisan schema:preview "$migration"
    echo "---"
done
```

---

## Testing Scenarios

### Scenario 1: Creating a New Table

**Migration:**
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

**Test Command:**
```bash
php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_create_products_table.php
```

**Expected Output:**
- ✅ Shows table will be created
- ✅ Shows all columns that will be added
- ⚠️ Shows rollback will drop the table (destructive)

**What to Verify:**
- [ ] All columns are listed correctly
- [ ] Table name is correct
- [ ] Rollback simulation shows drop operation
- [ ] No unexpected warnings

---

### Scenario 2: Adding Columns to Existing Table

**Migration:**
```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('phone')->nullable();
        $table->date('birthday')->nullable();
        $table->boolean('is_verified')->default(false);
    });
}
```

**Test Command:**
```bash
php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_add_columns_to_users_table.php
```

**Expected Output:**
- ✅ Shows columns will be added
- ✅ Shows current table structure
- ✅ Shows new columns don't exist yet

**What to Verify:**
- [ ] Existing table is detected
- [ ] New columns are listed
- [ ] Column types are correct
- [ ] Default values are shown

---

### Scenario 3: Dropping Columns (Destructive)

**Migration:**
```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['old_field', 'deprecated_column']);
    });
}
```

**Test Command:**
```bash
php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_drop_columns_from_users_table.php
```

**Expected Output:**
- 🔴 **DESTRUCTIVE CHANGE DETECTED**
- ⚠️ Warning about data loss
- 💾 Automatic data export triggered
- 📁 Export files created in `storage/app/schema-lens/exports/`

**What to Verify:**
- [ ] Destructive change is flagged
- [ ] Data export is created (CSV and JSON)
- [ ] Export includes affected columns
- [ ] Restore metadata is generated
- [ ] Compression works (if enabled)

**Check Export Files:**
```bash
ls -la storage/app/schema-lens/exports/
# Should see:
# - YYYY_MM_DD_HHMMSS_drop_columns_.../users_old_field_deprecated_column.json
# - YYYY_MM_DD_HHMMSS_drop_columns_.../users_old_field_deprecated_column.csv
# - YYYY_MM_DD_HHMMSS_drop_columns_.../restore_metadata.json
```

---

### Scenario 4: Renaming Columns (Destructive)

**Migration:**
```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->renameColumn('email_address', 'email');
    });
}
```

**Test Command:**
```bash
php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_rename_column_in_users_table.php
```

**Expected Output:**
- 🔴 **DESTRUCTIVE CHANGE DETECTED**
- ⚠️ Shows rename operation
- 💾 Data export for affected column

**What to Verify:**
- [ ] Source column exists
- [ ] Target column doesn't exist
- [ ] Data export includes the column being renamed

---

### Scenario 5: Dropping a Table (Critical Destructive)

**Migration:**
```php
public function up(): void
{
    Schema::dropIfExists('old_table');
}
```

**Test Command:**
```bash
php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_drop_old_table.php
```

**Expected Output:**
- 🔴 **CRITICAL DESTRUCTIVE CHANGE**
- ⚠️ Full table data export
- 📊 Shows all columns in the table
- 🔗 Shows foreign key dependencies

**What to Verify:**
- [ ] Entire table data is exported
- [ ] All columns are included in export
- [ ] Dependencies are listed (if any)
- [ ] Rollback simulation shows impact

---

### Scenario 6: Adding Indexes

**Migration:**
```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->index('email');
        $table->unique('username');
    });
}
```

**Test Command:**
```bash
php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_add_indexes_to_users_table.php
```

**Expected Output:**
- ✅ Shows indexes will be added
- ✅ Shows index names and columns
- ✅ Warns if index already exists

**What to Verify:**
- [ ] Index names are correct
- [ ] Columns are listed correctly
- [ ] Unique vs regular index distinction

---

### Scenario 7: Adding Foreign Keys

**Migration:**
```php
public function up(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->foreignId('user_id')
              ->constrained()
              ->onDelete('cascade');
    });
}
```

**Test Command:**
```bash
php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_add_foreign_key_to_orders_table.php
```

**Expected Output:**
- ✅ Shows foreign key will be added
- ✅ Shows referenced table
- ✅ Shows onDelete/onUpdate rules

**What to Verify:**
- [ ] Referenced table exists
- [ ] Foreign key constraints are correct
- [ ] Cascade rules are shown

---

### Scenario 8: Dropping Foreign Keys (Destructive)

**Migration:**
```php
public function up(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
    });
}
```

**Test Command:**
```bash
php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_drop_foreign_key_from_orders_table.php
```

**Expected Output:**
- 🔴 **DESTRUCTIVE CHANGE DETECTED**
- ⚠️ Shows foreign key being dropped
- ⚠️ Warns about referential integrity

**What to Verify:**
- [ ] Foreign key is detected
- [ ] Dependency warning is shown
- [ ] Rollback simulation shows impact

---

### Scenario 9: Changing Table Engine/Charset

**Migration:**
```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->engine('InnoDB');
        $table->charset('utf8mb4');
        $table->collation('utf8mb4_unicode_ci');
    });
}
```

**Test Command:**
```bash
php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_change_table_settings.php
```

**Expected Output:**
- ✅ Shows current engine/charset
- ✅ Shows new engine/charset
- ✅ Highlights differences

**What to Verify:**
- [ ] Current values are detected
- [ ] New values are shown
- [ ] Changes are highlighted

---

### Scenario 10: Complex Migration with Multiple Operations

**Migration:**
```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        // Add columns
        $table->string('phone')->nullable();
        
        // Modify column
        $table->string('name', 255)->change();
        
        // Drop column (destructive)
        $table->dropColumn('old_field');
        
        // Add index
        $table->index('phone');
    });
}
```

**Test Command:**
```bash
php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_complex_users_migration.php
```

**Expected Output:**
- ✅ Shows all operations
- 🔴 Flags destructive operations
- 💾 Exports data for dropped column
- 📊 Complete diff summary

**What to Verify:**
- [ ] All operations are detected
- [ ] Destructive operations are flagged
- [ ] Line numbers map correctly
- [ ] Data export includes only affected data

---

### Scenario 11: Rollback Simulation

**Migration with down() method:**
```php
public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('phone');
        $table->dropIndex('users_email_unique');
    });
}
```

**Test Command:**
```bash
php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_example_migration.php
```

**Expected Output:**
- 🔄 Rollback simulation section
- 📋 SQL preview for rollback
- ⚠️ Dependency warnings
- 📊 Impact analysis

**What to Verify:**
- [ ] Rollback operations are parsed
- [ ] SQL statements are generated
- [ ] Dependencies are identified
- [ ] Risk level is assessed

---

## Common Use Cases

### Use Case 1: Pre-Deployment Review

Before deploying to production, review all migrations:

```bash
# Preview all pending migrations
php artisan schema:preview database/migrations/2024_01_15_*.php --format=json > migration-review.json

# Review the JSON output
cat migration-review.json | jq '.destructive_changes'
```

### Use Case 2: Team Code Review

Generate JSON reports for PR comments:

```bash
php artisan schema:preview database/migrations/new_migration.php --format=json
```

Share the JSON output in your PR for automated review.

### Use Case 3: Data Backup Before Destructive Changes

When you know a migration has destructive changes:

```bash
# Preview will automatically export data
php artisan schema:preview database/migrations/destructive_migration.php

# Check exports
ls -lh storage/app/schema-lens/exports/
```

### Use Case 4: Migration Validation in CI/CD

Add to your CI pipeline:

```bash
# In your CI script
php artisan schema:preview database/migrations/new_migration.php --format=json

# Check exit code (fails if destructive changes found)
if [ $? -ne 0 ]; then
    echo "⚠️ Destructive changes detected!"
    exit 1
fi
```

---

## Troubleshooting

### Issue: "Migration file not found"

**Solution:**
- Use full path: `database/migrations/YYYY_MM_DD_HHMMSS_migration.php`
- Or use filename only if it's in `database/migrations/`
- Check file exists: `ls database/migrations/`

### Issue: "Cannot connect to database"

**Solution:**
- Ensure `.env` has correct database credentials
- Test connection: `php artisan migrate:status`
- Check database exists

### Issue: "No data exported for destructive changes"

**Possible Causes:**
- Table doesn't exist yet
- Column doesn't exist
- `--no-export` flag was used

**Solution:**
- Verify table/column exists in database
- Remove `--no-export` flag
- Check export directory permissions

### Issue: "Export files not compressed"

**Solution:**
- Check `SCHEMA_LENS_COMPRESS_EXPORTS=true` in `.env`
- Verify PHP `zip` extension is installed: `php -m | grep zip`
- Check export directory permissions

### Issue: "JSON report not generated"

**Solution:**
- Use `--format=json` flag
- Check `storage/app/schema-lens/` directory exists
- Verify write permissions

---

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Preview Migrations

on:
  pull_request:
    paths:
      - 'database/migrations/**'

jobs:
  preview:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      
      - name: Install Dependencies
        run: composer install
      
      - name: Preview Migrations
        run: |
          for migration in database/migrations/*.php; do
            php artisan schema:preview "$migration" --format=json || true
          done
      
      - name: Check for Destructive Changes
        run: |
          if grep -q '"has_destructive_changes":true' storage/app/schema-lens/report.json; then
            echo "⚠️ Destructive changes detected!"
            exit 1
          fi
```

### GitLab CI Example

```yaml
migration-preview:
  script:
    - composer install
    - |
      for migration in database/migrations/*.php; do
        php artisan schema:preview "$migration" --format=json
      done
    - |
      if [ $(cat storage/app/schema-lens/report.json | jq '.summary.has_destructive_changes') = "true" ]; then
        echo "⚠️ Destructive changes detected!"
        exit 1
      fi
  artifacts:
    paths:
      - storage/app/schema-lens/
    expire_in: 1 week
```

---

## Best Practices

1. **Always preview before migrating** - Especially in production
2. **Review destructive changes carefully** - Check exported data
3. **Use JSON format for automation** - Easier to parse in CI/CD
4. **Keep exports versioned** - Don't delete export directories immediately
5. **Test rollback scenarios** - Verify down() methods work
6. **Document complex migrations** - Add comments in migration files
7. **Review line mappings** - Verify operations map to correct lines

---

## Example Workflow

```bash
# 1. Create migration
php artisan make:migration add_phone_to_users_table

# 2. Edit migration file
# ... add your schema changes ...

# 3. Preview migration
php artisan schema:preview database/migrations/YYYY_MM_DD_HHMMSS_add_phone_to_users_table.php

# 4. Review output
# - Check for destructive changes
# - Verify column types
# - Review rollback simulation

# 5. If safe, run migration
php artisan migrate

# 6. Verify in database
php artisan tinker
# >>> Schema::hasColumn('users', 'phone')
```

---

## Additional Resources

- **Package Repository:** https://github.com/zaeem2396/schema-lens
- **Laravel Migrations Docs:** https://laravel.com/docs/migrations
- **MySQL Information Schema:** https://dev.mysql.com/doc/refman/8.0/en/information-schema.html

---

## Support

For issues or questions:
- Open an issue on GitHub
- Check existing issues for solutions
- Review the README.md for basic usage

