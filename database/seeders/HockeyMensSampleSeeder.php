<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use Illuminate\Database\Seeder;
use RuntimeException;

class HockeyMensSampleSeeder extends Seeder
{
    private const PARTICIPANTS = ['FTKEK', 'FTKM', 'FTKIP', 'FTMK', 'FTKE', 'FPTT', 'FAIX', 'STEP'];

    public function run(): void
    {
        if (app()->environment('production') && ! config('app.allow_demo_seeding')) {
            throw new RuntimeException('HockeyMensSampleSeeder requires ALLOW_DEMO_SEEDING=true in production.');
        }

        $event = Event::query()
            ->where(function ($query) {
                $query->where('name', "Sukan Antara Fakulti 2026 - Hockey - Men's")
                    ->orWhere('name', "Hockey (Men's)")
                    ->orWhere('slug', 'hockey-men-s');
            })
            ->firstOrFail();

        $registered = 0;

        foreach (self::PARTICIPANTS as $short) {
            $participant = Participant::query()
                ->where('organization_id', $event->organization_id)
                ->where('slug', str($short)->slug())
                ->first();

            if (! $participant) {
                $this->command?->warn("Participant {$short} not found; skipped.");
                continue;
            }

            EventParticipant::withTrashed()->updateOrCreate(
                ['event_id' => $event->id, 'participant_id' => $participant->id],
                [
                    'organization_id' => $event->organization_id,
                    'registration_date' => now(),
                    'status' => 'confirmed',
                ],
            )->restore();

            $registered++;
        }

        $this->command?->info("Hockey Men's sample data seeded: {$registered} participants registered.");
    }
}
