## 2024-05-18 - Optimized Collection Mapping
**Learning:** Calling Laravel collection methods (like `where` and `count`) inside a loop over many parent models creates excessive intermediate collection objects and overhead, even when relations are eager loaded.
**Action:** Use `flatMap->relation` before the loop to vectorize operations across all children at once.
