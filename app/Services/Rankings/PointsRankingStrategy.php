<?php

namespace App\Services\Rankings;

use App\Contracts\RankingStrategy;
use Illuminate\Support\Collection;

class PointsRankingStrategy implements RankingStrategy
{
    public function key(): string
    {
        return 'points';
    }

    public function label(array $rules): string
    {
        return sprintf(
            'Points (Win=%d, Draw=%d, Loss=%d)',
            $rules['win_points'],
            $rules['draw_points'],
            $rules['loss_points'],
        );
    }

    public function rank(Collection $stats, array $rules, ?Collection $tournaments = null): Collection
    {
        $ranked = $stats->map(function (array $row) use ($rules): array {
            $row['points'] = ($row['wins'] * $rules['win_points'])
                + ($row['draws'] * $rules['draw_points'])
                + ($row['losses'] * $rules['loss_points']);
            $row['goal_difference'] = $row['score_for'] - $row['score_against'];

            return $row;
        });

        return $this->sortAndRank($ranked, $rules['tiebreakers']);
    }

    private function sortAndRank(Collection $stats, array $tiebreakers): Collection
    {
        $allowed = ['points', 'goal_difference', 'score_for', 'wins'];
        $fields = array_values(array_filter($tiebreakers, fn (string $field): bool => in_array($field, $allowed, true)));
        $fields = $fields === [] ? ['points', 'goal_difference', 'score_for'] : $fields;

        return $stats->sort(function (array $left, array $right) use ($fields): int {
            foreach ($fields as $field) {
                $comparison = ($right[$field] ?? 0) <=> ($left[$field] ?? 0);
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return strcmp($left['participant_name'], $right['participant_name']);
        })->values()->map(function (array $row, int $index): array {
            $row['rank'] = $index + 1;

            return $row;
        });
    }
}
