<?php

namespace App\Policies;

use App\Models\DataTransfer;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DataTransferPolicy
{
    public function view(User $user, DataTransfer $transfer): bool
    {
        if (! $user->is_active || $user->organization_id !== $transfer->organization_id
            || $user->uuid !== $transfer->requested_by) {
            return false;
        }

        return match ($transfer->type) {
            DataTransfer::TYPE_EXPORT_FIXTURES, DataTransfer::TYPE_EXPORT_RESULTS,
            DataTransfer::TYPE_EXPORT_RANKINGS, DataTransfer::TYPE_EXPORT_MEDALS => Gate::forUser($user)->allows('export-data'),
            DataTransfer::TYPE_IMPORT_PARTICIPANTS => Gate::forUser($user)->allows('create', Participant::class),
            DataTransfer::TYPE_IMPORT_EVENT_PARTICIPANTS => Gate::forUser($user)->allows('create', EventParticipant::class)
                || ($user->hasRole('faculty-representative') && $user->participant_id
                    && $user->participant_id === ($transfer->payload['participant_id'] ?? null)),
            default => false,
        };
    }

    public function download(User $user, DataTransfer $transfer): bool
    {
        return $this->view($user, $transfer);
    }
}
