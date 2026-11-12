<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventParticipant;

final class RegistrationWindowService
{
    /** The event-registration deadline is session-wide; event deadline is legacy fallback. */
    public function eventRegistrationDeadline(Event $event): mixed
    {
        $event->loadMissing('tournament.session');

        return $event->tournament?->session?->event_registration_deadline
            ?? $event->registration_deadline;
    }

    public function isEventRegistrationClosed(Event $event): bool
    {
        $deadline = $this->eventRegistrationDeadline($event);

        return $deadline !== null && now()->startOfDay()->greaterThan($deadline);
    }

    public function isEventRegistrationOpen(Event $event): bool
    {
        $event->loadMissing('tournament.session');
        $session = $event->tournament?->session;
        $today = now()->startOfDay();
        $start = $session?->event_registration_start_date;
        $deadline = $this->eventRegistrationDeadline($event);

        return ($start === null || $today->greaterThanOrEqualTo($start))
            && ($deadline === null || $today->lessThanOrEqualTo($deadline));
    }

    public function isSquadRegistrationOpen(EventParticipant $eventParticipant): bool
    {
        $eventParticipant->loadMissing('event.tournament.session');

        $session = $eventParticipant->event?->tournament?->session;
        $eventDeadline = $session?->event_registration_deadline ?? $eventParticipant->event?->registration_deadline;
        $squadStart = $session?->squad_registration_start_date ?? $eventDeadline;
        $squadDeadline = $session?->squad_registration_deadline;
        $today = now()->startOfDay();

        return $eventParticipant->status === EventParticipant::STATUS_CONFIRMED
            && $eventDeadline !== null
            && $today->greaterThan($eventDeadline)
            && ($squadStart === null || $today->greaterThanOrEqualTo($squadStart))
            && $squadDeadline !== null
            && $today->lessThanOrEqualTo($squadDeadline);
    }
}
