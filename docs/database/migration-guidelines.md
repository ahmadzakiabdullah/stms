# Migration Guidelines

## Naming Conventions

Use descriptive, snake_case names following the pattern:

```
YYYY_MM_DD_HHMMSS_description_of_change.php
```

Examples:
- `2026_01_15_000001_create_organizations_table.php`
- `2026_01_15_000002_create_event_sessions_table.php`
- `2026_06_20_143000_add_status_to_registrations_table.php`

Always use a timestamp that preserves migration order. Group related migrations under the same date block.

## Primary Keys

Semua jadual domain baharu **mesti** menggunakan UUID sebagai primary key:

```php
$table->uuid('id')->primary();
```

Elakkan auto-increment integer ID. `settings.id` ialah pengecualian legacy yang telah deployed; ubah hanya melalui pelan migration kompatibel, bukan dengan mengedit migration asal. Jadual framework/vendor boleh mengikut skema package masing-masing.

## Soft Deletes

Apply `SoftDeletes` apabila retention/restoration domain diperlukan:

```php
use Illuminate\Database\Eloquent\SoftDeletes;
// ...
$table->softDeletes();
```

Pengecualian termasuk pivot, jadual framework/vendor dan snapshot immutable seperti `draw_versions`. Model semasa `Setting` dan `SquadMember` juga tidak mempunyai soft delete; jangan dokumentasikan sebaliknya.

## Timestamps

Every table must include:

```php
$table->timestamps();
```

## Foreign Keys

Use UUID foreign keys with cascading deletes where appropriate:

```php
$table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
```

## Golden Rule

**Never modify an existing migration** after it has been merged or deployed. Create a new migration for schema changes. This ensures reproducibility across environments and avoids conflicts in team workflows.

Migration backfills must use database-portable Laravel/PHP APIs unless a driver-specific branch is explicit. Cleanup migrations must also tolerate historical environments where an optional index is already absent; dropping a column is preferred when the database automatically removes its dependent indexes.
