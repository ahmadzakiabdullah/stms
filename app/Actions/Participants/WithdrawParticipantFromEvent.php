<?php

namespace App\Actions\Participants;

use App\Models\Participant;
use App\Services\ParticipantService;

class WithdrawParticipantFromEvent
{
    public function handle(Participant $p, string $eventId, ?ParticipantService $s = null): void
    {
        $s = $s ?? app(ParticipantService::class);
        $s->withdrawFromEvent($p, $eventId);
    }
}
