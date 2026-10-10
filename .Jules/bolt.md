
## 2024-05-30 - Optimize Collection Aggregations
**Learning:** Performing multiple chained collection operations (like `where(...)->count()`) within a loop for different keys on the same base collection creates unnecessary intermediate collections and increases CPU and memory usage, creating an O(N) bottleneck.
**Action:** Consolidate multiple collection filters into a single pass using `flatMap` and `countBy` to aggregate by keys in one O(1) traversal. When refactoring to `countBy`, use the `get(key, default)` method to retrieve counts safely, and ensure original variable assignment operators (like `+=`) are maintained to prevent regressions.
