<?php

namespace App\Actions\Results;

use App\Models\Organization;
use App\Models\Result;
use App\Models\User;
use App\Services\ResultService;

class TransitionResult
{
    public function __construct(
        protected ResultService $resultService,
    ) {}

    public function handle(Organization $organization, Result $result, User $actor, string $transition): Result
    {
        return match ($transition) {
            'submit' => $this->resultService->submit($organization, $result->id, $actor),
            'approve' => $this->resultService->approve($organization, $result->id, $actor),
            'lock' => $this->resultService->lock($organization, $result->id, $actor),
            'unlock' => $this->resultService->unlock($organization, $result->id, $actor),
            default => throw new \InvalidArgumentException('Unsupported result transition.'),
        };
    }
}
