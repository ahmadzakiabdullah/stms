## 2024-05-18 - Fix N+1 Query in Dashboard
**Learning:** Found an N+1 query vulnerability when counting nested `eventParticipants` on the Dashboard. Calling `$e->eventParticipants()->count()` in a loop maps sequentially, hitting the DB for each item.
**Action:** Use Laravel's `->withCount('eventParticipants')` eager load feature to retrieve the count in the initial SQL query, drastically reducing query overhead.
## 2026-07-31 - N+1 Query Optimization in Loops
**Learning:** Checking for model existence repeatedly in a loop using `first()` or similar queries causes N+1 problems.
**Action:** Always fetch the relevant collection outside of the loop first, group them by unique identifiers (`$sport_id . '_' . $category_id`), and perform collection lookups (`get()->first()`) inside the loop to dramatically decrease query count.
