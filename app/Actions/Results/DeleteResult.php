<?php

namespace App\Actions\Results;

use App\Models\Organization;
use App\Services\ResultService;

class DeleteResult
{
    public function __construct(
        protected ResultService $resultService,
    ) {}

    public function handle(Organization $organization, string $id): void
    {
        $this->resultService->delete($organization, $id);
    }
}
