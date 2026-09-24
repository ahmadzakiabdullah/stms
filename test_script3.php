<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing to see how UpdateEventParticipantStatus loads participants...\n";

// UpdateEventParticipantStatus handles `$eventParticipant->participant?->users`
// So we should eager load 'event', 'participant.users' to avoid N+1 queries.
// Also 'event' is used by Gate::allows('update', $eventParticipant) which checks `$eventParticipant->event?->organization_id`
