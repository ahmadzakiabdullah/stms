<?php

namespace App\Actions\Matches;

use App\Models\Fixture;
use App\Models\Organization;
use App\Services\MatchService;

class StoreMatch
{
    public function __construct(
        protected MatchService $matchService,
    ) {}

    public function handle(Organization $organization, array $data): Fixture
    {
        return $this->matchService->create($organization, $data);
    }
}
