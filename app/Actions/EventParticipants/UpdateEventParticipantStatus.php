<?php

namespace App\Actions\EventParticipants;

use App\Models\EventParticipant;
use App\Notifications\EventParticipantConfirmed;
use App\Notifications\EventParticipantRejected;
use Illuminate\Validation\ValidationException;

class UpdateEventParticipantStatus
{
    public function handle(EventParticipant $eventParticipant, string $status, ?string $notes = null): void
    {
        if (! $eventParticipant->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot change registration status from '{$eventParticipant->status}' to '{$status}'."],
            ]);
        }

        if ($status === EventParticipant::STATUS_REJECTED && blank($notes)) {
            throw ValidationException::withMessages([
                'notes' => ['A reason is required when rejecting a registration.'],
            ]);
        }

        $eventParticipant->update([
            'status' => $status,
            'notes' => filled($notes) ? trim(implode(PHP_EOL, array_filter([$eventParticipant->notes, trim($notes)]))) : $eventParticipant->notes,
        ]);

        foreach ($eventParticipant->participant?->users ?? [] as $user) {
            $user->notify(match ($status) {
                EventParticipant::STATUS_CONFIRMED => new EventParticipantConfirmed($eventParticipant),
                default => new EventParticipantRejected($eventParticipant),
            });
        }
    }
}
