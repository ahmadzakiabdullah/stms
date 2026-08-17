<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface RankingStrategy
{
    public function key(): string;

    public function label(array $rules): string;

    public function rank(Collection $stats, array $rules, ?Collection $tournaments = null): Collection;
}
