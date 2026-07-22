<?php

namespace App\Actions\Results;

use App\Models\Result;
use App\Models\Organization;
use App\Services\ResultService;

class UpdateResult
{
    public function __construct(
        protected ResultService $resultService,
    ) {}

    public function handle(Organization $organization, string $id, array $data): Result
    {
        return $this->resultService->update($organization, $id, $data);
    }
}
