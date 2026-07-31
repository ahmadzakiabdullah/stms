## 2024-05-18 - Fix N+1 Query in Dashboard
**Learning:** Found an N+1 query vulnerability when counting nested `eventParticipants` on the Dashboard. Calling `$e->eventParticipants()->count()` in a loop maps sequentially, hitting the DB for each item.
**Action:** Use Laravel's `->withCount('eventParticipants')` eager load feature to retrieve the count in the initial SQL query, drastically reducing query overhead.

## 2026-07-31 - N+1 Query Optimization in Loops
**Learning:** Checking for model existence repeatedly in a loop using `first()` or similar queries causes N+1 problems.
**Action:** Always fetch the relevant collection outside of the loop first, group them by unique identifiers (`$sport_id . '_' . $category_id`), and perform collection lookups (`get()->first()`) inside the loop to dramatically decrease query count.

## 2024-05-18 - Eliminating N+1 query in batch deletes while preserving audits
**Learning:** When using third-party packages like `Spatie Activitylog` that listen to Eloquent model events (e.g., `deleted`), calling `$model->delete()` in a loop triggers an N+1 query scenario (1 UPDATE, 1 SELECT, and 1 INSERT per model).
**Action:** Perform bulk database updates (e.g. `Model::whereIn('id', $ids)->delete()`) within a `DB::transaction()` instead of looping `$model->delete()`. To preserve auditing behavior, manually trigger the activity log via the package's facade (e.g. `activity()->performedOn($model)->event('deleted')->log('deleted');`) within the same loop. This avoids the SELECT query for each model that Activitylog normally executes, cutting queries significantly, while maintaining the correct logging logic and metadata.
