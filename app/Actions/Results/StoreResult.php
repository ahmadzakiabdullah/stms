<?php

namespace App\Actions\Results;

use App\Models\Organization;
use App\Models\Result;
use App\Services\ResultService;

class StoreResult
{
    public function __construct(
        protected ResultService $resultService,
    ) {}

    public function handle(Organization $organization, array $data): Result
    {
        return $this->resultService->create($organization, $data);
    }
}
