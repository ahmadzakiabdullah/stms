<?php

namespace App\Events;

use App\Models\Fixture;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchScoreUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fixture;

    public function __construct(Fixture $fixture)
    {
        // Load relationships needed for the frontend
        $this->fixture = $fixture->load(['competitor1.participant', 'competitor2.participant', 'result']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('matches.'.$this->fixture->id),
            new Channel('events.'.$this->fixture->event_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'score.updated';
    }
}
