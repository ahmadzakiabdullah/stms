# Ranking Engine

The Ranking Engine provides on-the-fly calculation of participant or team rankings based on match results. It is implemented as a `RankingService` class in the Service Layer, supporting three interchangeable strategies:

| Strategy      | Description |
|---------------|-------------|
| **points**    | Rankings by total accumulated points (win=3, draw=1, loss=0). Points are configurable per tournament. |
| **win_rate**  | Rankings by win/loss ratio (wins / total matches). Useful for tournaments with uneven match counts. |
| **medal_tally** | Rankings by gold > silver > bronze medal counts. Used in multi-sport event contexts. |

Rankings are **never stored** in the database. They are computed on-the-fly from the `results` table each time a ranking view is requested. The `RankingService` accepts a collection of matches, applies the selected strategy, and returns a sorted collection with rank, entity ID, and computed values. Caching of ranking results is optional and configurable.

New ranking strategies can be added by implementing the `RankingStrategy` interface and registering it in the service. This keeps the engine extensible without modifying existing code.
