<?php

namespace App\Notifications;

use App\Models\Result;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MatchResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public int $timeout = 60;

    public function __construct(
        public Result $result,
        public string $action,
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $result = $this->result;
        $match = $result->match;
        $home = $match?->homeParticipant;
        $away = $match?->awayParticipant;
        $winner = $result->winner;

        $matchLabel = ($match?->event?->name ?? 'Match')
            .($match?->match_number ? " (Match #{$match->match_number})" : '');
        $teams = trim(($home?->name ?? 'TBD').' vs '.($away?->name ?? 'TBD'));
        $score = ($result->score_home !== null || $result->score_away !== null)
            ? "{$result->score_home} - {$result->score_away}"
            : null;
        $winnerSuffix = $winner ? ". Winner: {$winner->name}" : '';

        $message = match ($this->action) {
            'recorded' => "Result recorded: {$matchLabel} — {$teams}".($score ? " ({$score})" : '').$winnerSuffix,
            'updated' => "Result updated: {$matchLabel} — {$teams}".($score ? " ({$score})" : '').$winnerSuffix,
            'removed' => "Result removed: {$matchLabel} — {$teams}. The result was deleted and rankings may change.",
            default => "Match result changed: {$matchLabel} — {$teams}",
        };

        return [
            'result_id' => $result->id,
            'match_id' => $result->match_id,
            'event_name' => $match?->event?->name,
            'match_number' => $match?->match_number,
            'home_name' => $home?->name,
            'away_name' => $away?->name,
            'score_home' => $result->score_home,
            'score_away' => $result->score_away,
            'winner_name' => $winner?->name,
            'message' => $message,
            'type' => 'result_'.$this->action,
            'severity' => $this->action === 'removed' ? 'warning' : 'info',
            'organization_id' => $result->organization_id,
            'organization_name' => $match?->event?->tournament?->organization?->name,
        ];
    }
}
