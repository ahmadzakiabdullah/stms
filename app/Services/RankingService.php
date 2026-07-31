<?php

namespace App\Services;

use App\Models\Result;
use App\Models\Session;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RankingService
{
    private const STRATEGY_POINTS = 'points';

    private const STRATEGY_WIN_RATE = 'win_rate';

    private const STRATEGY_MEDAL_TALLY = 'medal_tally';

    /**
     * Rankings aggregated across every tournament in a session.
     * Used when the session is a single competition split into phases.
     */
    public function calculateForSession(Session $session): Collection
    {
        $strategy = $session->ranking_strategy ?? self::STRATEGY_POINTS;

        $results = Result::query()
            ->whereHas('match', fn ($q) => $q->where('event_id', '!=', null))
            ->whereHas('match.event', fn ($q) => $q->whereIn(
                'tournament_id',
                $session->tournaments()->pluck('tournaments.id')
            ))
            ->with(['match.homeParticipant', 'match.awayParticipant', 'winner'])
            ->get();

        $participantStats = $this->aggregateStats($results);

        $rankings = match ($strategy) {
            self::STRATEGY_POINTS => $this->rankByPoints($participantStats),
            self::STRATEGY_WIN_RATE => $this->rankByWinRate($participantStats),
            self::STRATEGY_MEDAL_TALLY => $this->rankByMedalTally(
                $session->tournaments()->with('events')->get(),
                $participantStats
            ),
            default => $this->rankByPoints($participantStats),
        };

        Log::info('Rankings calculated for session', ['session_id' => $session->id, 'strategy' => $strategy, 'participant_count' => $rankings->count()]);

        return $rankings;
    }

    public function calculateForTournament(Tournament $tournament): Collection
    {
        $strategy = $tournament->ranking_strategy ?? self::STRATEGY_POINTS;

        $results = Result::query()
            ->whereHas('match', fn ($q) => $q->where('event_id', '!=', null))
            ->whereHas('match.event', fn ($q) => $q->where('tournament_id', $tournament->id))
            ->with(['match.homeParticipant', 'match.awayParticipant', 'winner'])
            ->get();

        $participantStats = $this->aggregateStats($results);

        $rankings = match ($strategy) {
            self::STRATEGY_POINTS => $this->rankByPoints($participantStats),
            self::STRATEGY_WIN_RATE => $this->rankByWinRate($participantStats),
            self::STRATEGY_MEDAL_TALLY => $this->rankByMedalTally(
                collect([$tournament->load('events')]),
                $participantStats
            ),
            default => $this->rankByPoints($participantStats),
        };

        Log::info('Rankings calculated for tournament', ['tournament_id' => $tournament->id, 'strategy' => $strategy, 'participant_count' => $rankings->count()]);

        return $rankings;
    }

    public function calculateForEvent($eventId): Collection
    {
        $results = Result::query()
            ->whereHas('match', fn ($q) => $q->where('event_id', $eventId))
            ->with(['match.homeParticipant', 'match.awayParticipant', 'winner'])
            ->get();

        $participantStats = $this->aggregateStats($results);

        $rankings = $this->rankByPoints($participantStats);

        Log::info('Rankings calculated for event', ['event_id' => $eventId, 'participant_count' => $rankings->count()]);

        return $rankings;
    }

    public function getAvailableStrategies(): array
    {
        return [
            self::STRATEGY_POINTS => 'Points (Win=3, Draw=1, Loss=0)',
            self::STRATEGY_WIN_RATE => 'Win Rate (Wins/Total)',
            self::STRATEGY_MEDAL_TALLY => 'Medal Tally (Gold/Silver/Bronze)',
        ];
    }

    private function aggregateStats(Collection $results): Collection
    {
        $stats = collect();

        foreach ($results as $result) {
            $homeId = $result->match?->home_participant_id;
            $awayId = $result->match?->away_participant_id;
            $winnerId = $result->winner_participant_id;

            if (! $homeId || ! $awayId) {
                continue;
            }

            foreach ([$homeId, $awayId] as $participantId) {
                if (! $stats->has($participantId)) {
                    // Bolt ⚡: Replace N+1 query with eager-loaded relationship
                    $participant = $participantId === $homeId ? $result->match->homeParticipant : $result->match->awayParticipant;
                    $stats->put($participantId, [
                        'participant_id' => $participantId,
                        'participant_name' => $participant?->name ?? 'Unknown',
                        'participant_type' => $participant?->participant_type ?? 'individual',
                        'team_name' => $participant?->team_name,
                        'matches_played' => 0,
                        'wins' => 0,
                        'draws' => 0,
                        'losses' => 0,
                        'score_for' => 0,
                        'score_against' => 0,
                        'gold' => 0,
                        'silver' => 0,
                        'bronze' => 0,
                    ]);
                }

                $s = $stats->get($participantId);
                $s['matches_played']++;

                $isHome = $participantId === $homeId;
                $scoreFor = $isHome ? ($result->score_home ?? 0) : ($result->score_away ?? 0);
                $scoreAgainst = $isHome ? ($result->score_away ?? 0) : ($result->score_home ?? 0);

                $s['score_for'] += $scoreFor;
                $s['score_against'] += $scoreAgainst;

                if ($result->score_home === $result->score_away && $result->score_home !== null) {
                    $s['draws']++;
                } elseif ($winnerId === $participantId) {
                    $s['wins']++;
                } else {
                    $s['losses']++;
                }

                $stats->put($participantId, $s);
            }
        }

        return $stats;
    }

    private function rankByPoints(Collection $stats): Collection
    {
        return $stats->map(function ($s) {
            $s['points'] = ($s['wins'] * 3) + $s['draws'];
            $s['goal_difference'] = $s['score_for'] - $s['score_against'];

            return $s;
        })->sortByDesc('points')->sortByDesc('goal_difference')->values()->map(function ($s, $index) {
            $s['rank'] = $index + 1;

            return $s;
        });
    }

    private function rankByWinRate(Collection $stats): Collection
    {
        return $stats->map(function ($s) {
            $s['win_rate'] = $s['matches_played'] > 0
                ? round(($s['wins'] / $s['matches_played']) * 100, 2)
                : 0;

            return $s;
        })->sortByDesc('win_rate')->sortByDesc('wins')->values()->map(function ($s, $index) {
            $s['rank'] = $index + 1;

            return $s;
        });
    }

    /**
     * Real medal tally: for every event in the given tournaments, the Final
     * winner earns Gold, the Final runner-up earns Silver, and the Bronze
     * match winner earns Bronze. Totals are aggregated across all events and
     * sorted by Gold, then Silver, then Bronze. Points are never considered;
     * teams with identical medal counts share the same rank.
     *
     * @param  Collection<int, Tournament>  $tournaments
     */
    private function rankByMedalTally(Collection $tournaments, Collection $stats): Collection
    {
        $medals = [];

        $tournaments
            ->each(function ($tournament) {
                $tournament->events->each(function ($event) {
                    $event->setRelation('matches', $event->matches()
                        ->with('result')
                        ->whereIn('stage', [KnockoutStageService::STAGE_FINAL, KnockoutStageService::STAGE_BRONZE])
                        ->where('status', 'completed')
                        ->get());
                });
            })
            ->flatMap(fn ($tournament) => $tournament->events)
            ->each(function ($event) use (&$medals) {
                $final = $event->matches->firstWhere('stage', KnockoutStageService::STAGE_FINAL);
                $bronze = $event->matches->firstWhere('stage', KnockoutStageService::STAGE_BRONZE);

                if ($final && $final->result && $final->result->winner_participant_id) {
                    $this->awardMedal($medals, $final->result->winner_participant_id, 'gold');
                    $loser = $final->home_participant_id === $final->result->winner_participant_id
                        ? $final->away_participant_id
                        : $final->home_participant_id;

                    if ($loser) {
                        $this->awardMedal($medals, $loser, 'silver');
                    }
                }

                if ($bronze && $bronze->result && $bronze->result->winner_participant_id) {
                    $this->awardMedal($medals, $bronze->result->winner_participant_id, 'bronze');
                }
            });

        return $stats->map(function ($s) use ($medals) {
            $s['gold'] = $medals[$s['participant_id']]['gold'] ?? 0;
            $s['silver'] = $medals[$s['participant_id']]['silver'] ?? 0;
            $s['bronze'] = $medals[$s['participant_id']]['bronze'] ?? 0;
            $s['total_medals'] = $s['gold'] + $s['silver'] + $s['bronze'];

            return $s;
        })
            ->sortByDesc(fn ($s) => [$s['gold'], $s['silver'], $s['bronze']])
            ->values()
            ->reduce(function (Collection $ranked, array $s) {
                $previous = $ranked->last();
                $s['rank'] = $previous
                    && $previous['gold'] === $s['gold']
                    && $previous['silver'] === $s['silver']
                    && $previous['bronze'] === $s['bronze']
                        ? $previous['rank']
                        : $ranked->count() + 1;

                $ranked->push($s);

                return $ranked;
            }, collect());
    }

    protected function awardMedal(array &$medals, string $participantId, string $medal): void
    {
        if (! isset($medals[$participantId])) {
            $medals[$participantId] = ['gold' => 0, 'silver' => 0, 'bronze' => 0];
        }

        $medals[$participantId][$medal]++;
    }
}
