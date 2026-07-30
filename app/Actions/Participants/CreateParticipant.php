<?php

namespace App\Actions\Participants;

use App\Models\Participant;
use App\Services\ParticipantService;

class CreateParticipant
{
    public function handle(array $data, ?ParticipantService $service = null): Participant
    {
        $service = $service ?? app(ParticipantService::class);

        return $service->createParticipant($data);
    }
}
