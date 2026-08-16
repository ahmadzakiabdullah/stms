<?php

namespace App\Actions\Participants;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

class BatchRegisterParticipantToEvents
{
    public function __construct(private readonly RegisterParticipantToEvent $register) {}

    /** @return array{registered:int, failures:array<int,string>, created:Collection<int,EventParticipant>} */
    public function handle(Participant $participant, array $eventIds): array
    {
        $registered = 0;
        $failures = [];
        $created = collect();

        foreach (array_unique($eventIds) as $eventId) {
            $event = Event::find($eventId);

            try {
                $created->push($this->register->handle($participant, $eventId));
                $registered++;
            } catch (ValidationException $e) {
                $failures[] = "{$event?->name}: {$e->getMessage()}";
            } catch (Throwable) {
                $failures[] = "{$event?->name}: failed to register.";
            }
        }

        return compact('registered', 'failures', 'created');
    }
}
