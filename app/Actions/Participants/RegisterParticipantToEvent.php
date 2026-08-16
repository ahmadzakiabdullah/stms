<?php

namespace App\Actions\Participants;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Services\ParticipantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterParticipantToEvent
{
    public function handle(Participant $p, string $eventId, array $d = [], ?ParticipantService $s = null): EventParticipant
    {
        $s = $s ?? app(ParticipantService::class);

        return DB::transaction(function () use ($p, $eventId, $d, $s) {
            $event = Event::findOrFail($eventId);

            if ($event->registration_deadline && now()->greaterThan($event->registration_deadline)) {
                throw ValidationException::withMessages(['event_id' => 'Registration deadline for this event has passed.']);
            }

            return $s->registerToEvent($p, $eventId, $d);
        });
    }
}
