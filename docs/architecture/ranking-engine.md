# Ranking Engine

## Current Implementation

`App\Services\RankingService` calculates rankings on demand from completed, tenant-scoped results. `RankingStrategyRegistry` resolves implementations through the `RankingStrategy` contract.

| Strategy | Configurable rules |
|---|---|
| `points` | Win/draw/loss points and ordered tiebreakers |
| `win_rate` | Ordered tiebreakers across win rate, wins, points, goal difference, goals for and name |
| `medal_tally` | Ordered gold/silver/bronze/total-medal/name tiebreakers |

Default rules live in `config/ranking.php` (the table lists accepted tiebreakers; shipped defaults are narrower, e.g. `win_rate` = win_rate/wins/goal_difference, `medal_tally` = gold/silver/bronze). Validated JSON overrides can be saved on sessions and tournaments; tournament rules take precedence over session rules. No sport name or formula is hardcoded in controllers.

Session rankings aggregate tournaments in a session. Tournament and event rankings query completed results and eager-load participants and winners. Rankings are calculated rather than stored in a materialized table.

## Strategy Contract

`App\Contracts\RankingStrategy` defines `key()`, `label(array $rules)` and `rank(Collection $stats, array $rules, ?Collection $tournaments = null)`. Each strategy receives aggregated per-participant stats (built by `RankingService::aggregateStats()` from completed results), the merged rule array and, where needed (e.g. `medal_tally` reading Final/Bronze fixtures), the tournaments; it returns deterministic ranked rows.

`RankingStrategyRegistry`:

1. is constructed with the three strategy classes injected and keys them by `key()`;
2. `get($key)` throws `InvalidArgumentException` for an unknown strategy;
3. `rules($key, $overrides)` merges stored overrides over the `config("ranking.strategies.{key}")` defaults;
4. `labels()` exposes strategy labels.

`RankingService` resolves the strategy via the registry and calls its `rank()`.

## Remaining Limits

- Medal allocation assumes explicit Final and Bronze fixture stages.
- Event ranking inherits its tournament strategy; rule administration remains at session/tournament level.
- Rankings are not cached independently; public portal caching covers the public aggregate response.
- New sport formats require strategy fixtures and tests, but not sport-name conditionals in services.

## Extension Rules

Add a new class implementing `RankingStrategy`, register it in `config/ranking.php`, validate its rule schema at the request boundary, and add tenant-isolation plus deterministic-tiebreaker tests.
