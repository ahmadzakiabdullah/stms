## 2024-05-18 - Fix N+1 Query in Dashboard
**Learning:** Found an N+1 query vulnerability when counting nested `eventParticipants` on the Dashboard. Calling `$e->eventParticipants()->count()` in a loop maps sequentially, hitting the DB for each item.
**Action:** Use Laravel's `->withCount('eventParticipants')` eager load feature to retrieve the count in the initial SQL query, drastically reducing query overhead.
