## 2026-07-30 - [Hidden N+1 Query in Aggregation Loop]
**Learning:** Even when relations are correctly eager-loaded on a parent collection (`match.homeParticipant`), nested aggregation loops can introduce hidden N+1 queries if they call `Model::find($id)` instead of referencing the eager-loaded relationship variables.
**Action:** When iterating over results and aggregating stats, always inspect inner loops for `Model::find()` calls and replace them with references to the eager-loaded relations from the parent object.
