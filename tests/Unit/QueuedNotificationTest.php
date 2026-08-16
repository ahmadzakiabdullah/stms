<?php

namespace Tests\Unit;

use App\Models\EventParticipant;
use App\Models\Result;
use App\Notifications\EventParticipantConfirmed;
use App\Notifications\EventParticipantRejected;
use App\Notifications\MatchResultNotification;
use App\Notifications\NewEventRegistration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

class QueuedNotificationTest extends TestCase
{
    public function test_competition_notifications_are_queued_with_retry_policy(): void
    {
        $notifications = [
            new EventParticipantConfirmed(new EventParticipant),
            new EventParticipantRejected(new EventParticipant),
            new NewEventRegistration(new EventParticipant),
            new MatchResultNotification(new Result, 'recorded'),
        ];

        foreach ($notifications as $notification) {
            $this->assertInstanceOf(ShouldQueue::class, $notification);
            $this->assertSame(3, $notification->tries);
            $this->assertSame([10, 30, 60], $notification->backoff);
            $this->assertSame(60, $notification->timeout);
        }
    }

    public function test_redis_queue_dispatches_after_commit(): void
    {
        $this->assertTrue((bool) config('queue.connections.redis.after_commit'));
    }
}
