# Naming Conventions

## Table Names

Use **snake_case plural** for table names:

```php
Schema::create('organizations', ...);
Schema::create('event_sessions', ...);
Schema::create('sport_categories', ...);
```

Pivot tables combine both table names in snake_case, singular:

```php
Schema::create('tournament_sport', ...);
```

## Column Names

All columns use **snake_case**:

| Type | Format | Example |
|------|--------|---------|
| Primary key | `id` (UUID) | `$table->uuid('id')->primary();` |
| Foreign key | `{model}_id` | `organization_id`, `tournament_id` |
| Timestamps | `{verb}_at` | `created_at`, `deleted_at` |
| Boolean flags | `is_` or `has_` | `is_active`, `has_played` |
| Status enums | `status` | `$table->string('status');` |

## Foreign Key Columns

Always name FK columns as the **singular snake_case model name** followed by `_id`:

```php
$table->foreignUuid('organization_id')->constrained();
$table->foreignUuid('session_id')->constrained('event_sessions');
$table->foreignUuid('sport_category_id')->constrained();
```

## Standard Columns

Every table should include:

```php
$table->uuid('id')->primary();
$table->timestamps();
$table->softDeletes();  // where applicable
```

## Model/Table Mapping

| Model | Table |
|-------|-------|
| `Organization` | `organizations` |
| `User` | `users` |
| `Sport` | `sports` |
| `SportCategory` | `sport_categories` |
| `EventSession` | `event_sessions` |
| `Tournament` | `tournaments` |
| `Event` | `events` |
| `Participant` | `participants` |
| `Registration` | `registrations` |
| `EventParticipant` | `event_participants` |
| `Match` | `matches` |
| `Result` | `results` |
| `SquadMember` | `squad_members` |

### Pivot Tables (no model)

| Pivot Table | Purpose |
|-------------|---------|
| `tournament_sport` | Links tournaments to sports (many-to-many) |
