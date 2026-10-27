## 2024-11-20 - Fix pagination caching bug in DashboardDataService
**Learning:** When caching paginated queries in Laravel (e.g., using `paginate()` in services like `DashboardDataService`), ensure the request's `page` parameter is explicitly included in the cache key. Otherwise, all paginated requests will erroneously return the cached first page.
**Action:** When caching queries that use `paginate()`, always verify that `request('page', 1)` or similar is included in the cache key.
