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

    /** @return array{registered:int, failures:array<int,string>, created:Collection<int,EventParticipant>, selected_count:int, skipped_count:int, event_ids:array<int,string>} */
    public function handle(Participant $participant, array $eventIds): array
    {
        $eventIds = array_values(array_unique($eventIds));
        $registered = 0;
        $failures = [];
        $created = collect();

        foreach ($eventIds as $eventId) {
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

        return [
            'registered' => $registered,
            'failures' => $failures,
            'created' => $created,
            'selected_count' => count($eventIds),
            'skipped_count' => max(0, count($eventIds) - $registered),
            'event_ids' => $eventIds,
        ];
    }
}
