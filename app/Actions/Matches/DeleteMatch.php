<?php

namespace App\Actions\Matches;

use App\Models\Organization;
use App\Services\MatchService;

class DeleteMatch
{
    public function __construct(
        protected MatchService $matchService,
    ) {}

    public function handle(Organization $organization, string $id): void
    {
        $this->matchService->delete($organization, $id);
    }
}
