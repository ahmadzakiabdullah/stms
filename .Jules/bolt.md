## 2024-10-19 - Optimize loop collection counts
**Learning:** Found an `O(n)` complexity nested aggregation where Laravel collection was filtered and counted inside a loop for each registration, leading to unnecessary iteration overhead.
**Action:** Used `flatMap->squadMembers` combined with `countBy` to aggregate values in a single pass without any nested loops.
