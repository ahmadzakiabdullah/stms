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

All tables **must** use UUIDs as primary keys:

```php
$table->uuid('id')->primary();
```

Avoid auto-increment integer IDs. UUIDs prevent enumeration attacks, simplify multi-tenant data merging, and support distributed ID generation.

## Soft Deletes

Apply `SoftDeletes` to most tables:

```php
use Illuminate\Database\Eloquent\SoftDeletes;
// ...
$table->softDeletes();
```

Exceptions may include bare pivot tables (e.g. `tournament_sport`) where cascade cleanup is preferred over soft deletion.

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
