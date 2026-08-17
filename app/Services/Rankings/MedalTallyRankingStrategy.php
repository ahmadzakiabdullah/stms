<?php

namespace App\Services\Rankings;

use App\Contracts\RankingStrategy;
use App\Services\KnockoutStageService;
use Illuminate\Support\Collection;

class MedalTallyRankingStrategy implements RankingStrategy
{
    public function key(): string
    {
        return 'medal_tally';
    }

    public function label(array $rules): string
    {
        return 'Medal Tally';
    }

    public function rank(Collection $stats, array $rules, ?Collection $tournaments = null): Collection
    {
        $medals = [];

        ($tournaments ?? collect())
            ->each(function ($tournament): void {
                $tournament->events->each(function ($event): void {
                    $event->setRelation('matches', $event->matches()
                        ->with('result')
                        ->whereIn('stage', [KnockoutStageService::STAGE_FINAL, KnockoutStageService::STAGE_BRONZE])
                        ->where('status', 'completed')
                        ->get());
                });
            })
            ->flatMap(fn ($tournament) => $tournament->events)
            ->each(function ($event) use (&$medals): void {
                $final = $event->matches->firstWhere('stage', KnockoutStageService::STAGE_FINAL);
                $bronze = $event->matches->firstWhere('stage', KnockoutStageService::STAGE_BRONZE);

                if ($final?->result?->winner_participant_id) {
                    $this->award($medals, $final->result->winner_participant_id, 'gold');
                    $loser = $final->home_participant_id === $final->result->winner_participant_id
                        ? $final->away_participant_id
                        : $final->home_participant_id;

                    if ($loser) {
                        $this->award($medals, $loser, 'silver');
                    }
                }

                if ($bronze?->result?->winner_participant_id) {
                    $this->award($medals, $bronze->result->winner_participant_id, 'bronze');
                }
            });

        $order = $rules['tiebreakers'];

        return $stats->map(function (array $row) use ($medals): array {
            $row['gold'] = $medals[$row['participant_id']]['gold'] ?? 0;
            $row['silver'] = $medals[$row['participant_id']]['silver'] ?? 0;
            $row['bronze'] = $medals[$row['participant_id']]['bronze'] ?? 0;
            $row['total_medals'] = $row['gold'] + $row['silver'] + $row['bronze'];

            return $row;
        })->sort(function (array $left, array $right) use ($order): int {
            foreach ($order as $field) {
                $comparison = ($right[$field] ?? 0) <=> ($left[$field] ?? 0);
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return strcmp($left['participant_name'], $right['participant_name']);
        })->values()->reduce(function (Collection $ranked, array $row) use ($order): Collection {
            $previous = $ranked->last();
            $isTie = $previous && collect($order)->every(fn (string $field): bool => ($previous[$field] ?? 0) === ($row[$field] ?? 0));
            $row['rank'] = $isTie ? $previous['rank'] : $ranked->count() + 1;
            $ranked->push($row);

            return $ranked;
        }, collect());
    }

    private function award(array &$medals, string $participantId, string $medal): void
    {
        $medals[$participantId] ??= ['gold' => 0, 'silver' => 0, 'bronze' => 0];
        $medals[$participantId][$medal]++;
    }
}
