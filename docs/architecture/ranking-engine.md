# Ranking Engine

## Current Implementation

`App\Services\RankingService` calculates rankings on demand from completed, tenant-scoped results. `RankingStrategyRegistry` resolves implementations through the `RankingStrategy` contract.

| Strategy | Configurable rules |
|---|---|
| `points` | Win/draw/loss points and ordered tiebreakers |
| `win_rate` | Ordered tiebreakers across win rate, wins, points, goal difference, goals for and name |
| `medal_tally` | Ordered gold/silver/bronze/total-medal/name tiebreakers |

Defaults live in `config/ranking.php`. Validated JSON overrides can be saved on sessions and tournaments; tournament rules take precedence over session rules. No sport name or formula is hardcoded in controllers.

Session rankings aggregate tournaments in a session. Tournament and event rankings query completed results and eager-load participants and winners. Rankings are calculated rather than stored in a materialized table.

## Strategy Contract

Each strategy receives a result collection and a normalized rule array, and returns deterministic ranked rows. The registry:

1. validates that a configured strategy exists;
2. merges stored rules with safe defaults;
3. dispatches calculation to the matching strategy class;
4. preserves shared-rank behavior where configured values are identical.

## Remaining Limits

- Medal allocation assumes explicit Final and Bronze fixture stages.
- Event ranking inherits its tournament strategy; rule administration remains at session/tournament level.
- Rankings are not cached independently; public portal caching covers the public aggregate response.
- New sport formats require strategy fixtures and tests, but not sport-name conditionals in services.

## Extension Rules

Add a new class implementing `RankingStrategy`, register it in `config/ranking.php`, validate its rule schema at the request boundary, and add tenant-isolation plus deterministic-tiebreaker tests.
