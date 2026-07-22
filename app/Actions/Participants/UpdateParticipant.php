<?php

namespace App\Actions\Participants;

use App\Models\Participant;
use App\Services\ParticipantService;

class UpdateParticipant
{
    public function handle(Participant $participant, array $data, ?ParticipantService $service = null): Participant
    {
        $service = $service ?? app(ParticipantService::class);
        return $service->updateParticipant($participant, $data);
    }
}
