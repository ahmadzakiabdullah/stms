<?php

namespace App\Services\Rankings;

use App\Contracts\RankingStrategy;
use Illuminate\Support\Collection;

class WinRateRankingStrategy implements RankingStrategy
{
    public function key(): string
    {
        return 'win_rate';
    }

    public function label(array $rules): string
    {
        return 'Win Rate';
    }

    public function rank(Collection $stats, array $rules, ?Collection $tournaments = null): Collection
    {
        $ranked = $stats->map(function (array $row): array {
            $row['win_rate'] = $row['matches_played'] > 0
                ? round(($row['wins'] / $row['matches_played']) * 100, 2)
                : 0;
            $row['goal_difference'] = $row['score_for'] - $row['score_against'];

            return $row;
        });

        $allowed = ['win_rate', 'wins', 'goal_difference', 'score_for'];
        $fields = array_values(array_filter($rules['tiebreakers'], fn (string $field): bool => in_array($field, $allowed, true)));
        $fields = $fields === [] ? ['win_rate', 'wins', 'goal_difference'] : $fields;

        return $ranked->sort(function (array $left, array $right) use ($fields): int {
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
