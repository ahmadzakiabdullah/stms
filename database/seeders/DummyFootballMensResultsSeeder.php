<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Result;
use Illuminate\Database\Seeder;

class DummyFootballMensResultsSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::query()
            ->whereHas('tournament', fn ($query) => $query->where('name', 'SAF 2026 Fasa 1'))
            ->whereHas('sport', fn ($query) => $query->where('slug', 'football'))
            ->whereHas('sportCategory', fn ($query) => $query->where('slug', 'football-men-s'))
            ->firstOrFail();

        $fixtures = Fixture::query()
            ->where('event_id', $event->id)
            ->whereNotNull('home_participant_id')
            ->whereNotNull('away_participant_id')
            ->orderBy('match_number')
            ->get();

        $results = 0;
        foreach ($fixtures as $fixture) {
            // Deterministic football scores: mostly decisive, with occasional draws.
            $scoreHome = ($fixture->match_number * 2 + 1) % 4;
            $scoreAway = ($fixture->match_number + 2) % 3;
            $winnerId = $scoreHome === $scoreAway
                ? null
                : ($scoreHome > $scoreAway ? $fixture->home_participant_id : $fixture->away_participant_id);

            $result = Result::withTrashed()->updateOrCreate(
                ['match_id' => $fixture->id],
                [
                    'organization_id' => $event->organization_id,
                    'score_home' => $scoreHome,
                    'score_away' => $scoreAway,
                    'winner_participant_id' => $winnerId,
                    'notes' => 'Dummy result for SAF 2026 testing.',
                ],
            );

            if ($result->trashed()) {
                $result->restore();
            }

            $fixture->update(['status' => 'completed']);
            $results++;
        }

        $this->command?->info("Dummy Football Men's results seeded: {$results} matches completed.");
    }
}
