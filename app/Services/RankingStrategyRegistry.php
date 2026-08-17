<?php

namespace App\Services;

use App\Contracts\RankingStrategy;
use App\Services\Rankings\MedalTallyRankingStrategy;
use App\Services\Rankings\PointsRankingStrategy;
use App\Services\Rankings\WinRateRankingStrategy;
use InvalidArgumentException;

class RankingStrategyRegistry
{
    /** @var array<string, RankingStrategy> */
    private array $strategies;

    public function __construct(
        PointsRankingStrategy $points,
        WinRateRankingStrategy $winRate,
        MedalTallyRankingStrategy $medalTally,
    ) {
        $this->strategies = collect([$points, $winRate, $medalTally])
            ->mapWithKeys(fn (RankingStrategy $strategy): array => [$strategy->key() => $strategy])
            ->all();
    }

    public function get(string $key): RankingStrategy
    {
        return $this->strategies[$key]
            ?? throw new InvalidArgumentException("Unknown ranking strategy [{$key}].");
    }

    public function labels(): array
    {
        return collect($this->strategies)->mapWithKeys(function (RankingStrategy $strategy, string $key): array {
            return [$key => $strategy->label($this->rules($key))];
        })->all();
    }

    public function rules(string $key, array $overrides = []): array
    {
        return array_replace_recursive(config("ranking.strategies.{$key}", []), $overrides);
    }
}
