<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Result;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RankingService
{
    private const STRATEGY_POINTS = 'points';
    private const STRATEGY_WIN_RATE = 'win_rate';
    private const STRATEGY_MEDAL_TALLY = 'medal_tally';

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
            self::STRATEGY_MEDAL_TALLY => $this->rankByMedalTally($participantStats),
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

            if (!$homeId || !$awayId) {
                continue;
            }

            foreach ([$homeId, $awayId] as $participantId) {
                if (!$stats->has($participantId)) {
                    $participant = Participant::find($participantId);
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

    private function rankByMedalTally(Collection $stats): Collection
    {
        return $stats->map(function ($s) {
            $s['points'] = ($s['wins'] * 3) + $s['draws'];
            return $s;
        })->sortByDesc('points')->values()->map(function ($s, $index) {
            $s['rank'] = $index + 1;
            if ($index === 0) $s['gold'] = 1;
            elseif ($index === 1) $s['silver'] = 1;
            elseif ($index === 2) $s['bronze'] = 1;
            return $s;
        });
    }
}
