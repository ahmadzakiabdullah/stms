<?php

namespace App\Actions\Participants;

use App\Models\Participant;
use App\Services\ParticipantService;

class DeleteParticipant
{
    public function handle(Participant $participant, ?ParticipantService $service = null): void
    {
        $service = $service ?? app(ParticipantService::class);
        $service->deleteParticipant($participant);
    }
}
