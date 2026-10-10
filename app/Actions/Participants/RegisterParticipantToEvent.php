<?php

namespace App\Actions\Participants;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Services\EventParticipantNotificationService;
use App\Services\ParticipantService;
use App\Services\RegistrationWindowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterParticipantToEvent
{
    public function __construct(private readonly RegistrationWindowService $registrationWindow) {}

    public function handle(Participant $p, string $eventId, array $d = [], ?ParticipantService $s = null): EventParticipant
    {
        $s = $s ?? app(ParticipantService::class);

        $eventParticipant = DB::transaction(function () use ($p, $eventId, $d, $s) {
            $event = Event::findOrFail($eventId);

            if (! $this->registrationWindow->isEventRegistrationOpen($event)) {
                throw ValidationException::withMessages(['event_id' => 'The event registration window is not currently open.']);
            }

            return $s->registerToEvent($p, $eventId, $d);
        });

        app(EventParticipantNotificationService::class)->notifyRegistration($eventParticipant);

        return $eventParticipant;
    }
}
