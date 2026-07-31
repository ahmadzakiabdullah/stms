<?php

namespace App\Services;

use App\Models\Pool;
use Illuminate\Support\Collection;

class LeagueTableService
{
    public const WIN_POINTS = 3;

    public const DRAW_POINTS = 1;

    public const LOSS_POINTS = 0;

    /**
     * Build a league table (standings) for the given pool.
     *
     * Every participant in the pool is included, even before their first
     * completed fixture (they appear with zeroed stats). Fixtures with a
     * recorded result contribute to the table. Rows are sorted by points,
     * then goal difference, then goals scored.
     */
    public function standings(Pool $pool): Collection
    {
        $rows = $this->seedParticipantRows($pool);

        $pool->fixtures()
            ->where('status', 'completed')
            ->with('result')
            ->get()
            ->each(function ($fixture) use ($rows) {
                $result = $fixture->result;
                if (! $result || ! $fixture->home_participant_id || ! $fixture->away_participant_id) {
                    return;
                }

                $this->applyResult($rows, $fixture->home_participant_id, $fixture->away_participant_id, $result->score_home, $result->score_away);
            });

        return $rows
            ->sortByDesc(fn ($row) => [$row['points'], $row['goal_difference'], $row['goals_for']])
            ->values();
    }

    protected function applyResult(Collection $rows, string $homeId, string $awayId, int $homeScore, int $awayScore): void
    {
        $this->applyOutcome($rows, $homeId, $homeScore, $awayScore, $this->outcome($homeScore, $awayScore));
        $this->applyOutcome($rows, $awayId, $awayScore, $homeScore, $this->outcome($awayScore, $homeScore));
    }

    protected function outcome(int $for, int $against): string
    {
        if ($for > $against) {
            return 'win';
        }

        if ($for < $against) {
            return 'loss';
        }

        return 'draw';
    }

    protected function seedParticipantRows(Pool $pool): Collection
    {
        $rows = collect();

        $pool->eventParticipants
            ->loadMissing('participant')
            ->pluck('participant')
            ->filter()
            ->each(function ($participant) use ($rows) {
                $rows->put($participant->id, $this->emptyRow($participant->id));
            });

        return $rows;
    }

    protected function emptyRow(string $participantId): array
    {
        return [
            'participant_id' => $participantId,
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,
            'points' => 0,
        ];
    }

    protected function applyOutcome(Collection $rows, string $participantId, int $goalsFor, int $goalsAgainst, string $outcome): void
    {
        $row = $rows->get($participantId) ?? $this->emptyRow($participantId);

        $row['played']++;
        $row['goals_for'] += $goalsFor;
        $row['goals_against'] += $goalsAgainst;
        $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];

        match ($outcome) {
            'win' => $row['won']++,
            'draw' => $row['drawn']++,
            'loss' => $row['lost']++,
        };

        $row['points'] = ($row['won'] * self::WIN_POINTS) + ($row['drawn'] * self::DRAW_POINTS) + ($row['lost'] * self::LOSS_POINTS);

        $rows->put($participantId, $row);
    }
}
