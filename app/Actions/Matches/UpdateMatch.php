<?php

namespace App\Actions\Matches;

use App\Models\Fixture;
use App\Models\Organization;
use App\Services\MatchService;

class UpdateMatch
{
    public function __construct(
        protected MatchService $matchService,
    ) {}

    public function handle(Organization $organization, string $id, array $data): Fixture
    {
        return $this->matchService->update($organization, $id, $data);
    }
}
