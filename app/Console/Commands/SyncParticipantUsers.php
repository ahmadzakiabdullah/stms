<?php

namespace App\Console\Commands;

use App\Models\Participant;
use App\Models\User;
use App\Services\ParticipantService;
use Illuminate\Console\Command;

class SyncParticipantUsers extends Command
{
    protected $signature = 'participants:sync-users
        {--dry-run : Show what would be done without making changes}';

    protected $description = 'Ensure every Participant has a linked User account';

    public function handle(ParticipantService $service): int
    {
        $dryRun = $this->option('dry-run');
        $participants = Participant::all();

        $linked = 0;
        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($participants as $participant) {
            if (User::where('participant_id', $participant->id)->exists()) {
                continue;
            }

            $email = $participant->email;

            if (empty($email)) {
                $this->warn("  SKIP [{$participant->id}] {$participant->name} — no email");
                $skipped++;
                continue;
            }

            $existingUser = User::where('email', $email)->first();

            if ($existingUser) {
                if ($dryRun) {
                    $this->line("  LINK [{$participant->id}] {$participant->name} → {$existingUser->uuid} ({$email})");
                } else {
                    try {
                        $existingUser->update(['participant_id' => $participant->id]);
                        $this->info("  LINKED {$participant->name} → existing user {$email}");
                        $linked++;
                    } catch (\Throwable $e) {
                        $this->error("  ERROR {$participant->name}: {$e->getMessage()}");
                        $errors++;
                    }
                }
                continue;
            }

            if ($dryRun) {
                $this->line("  CREATE [{$participant->id}] {$participant->name} <{$email}>");
                continue;
            }

            try {
                $service->ensureUserLinked($participant, $participant->organization_id);
                $this->info("  CREATED user for {$participant->name} <{$email}>");
                $created++;
            } catch (\Throwable $e) {
                $this->error("  ERROR {$participant->name}: {$e->getMessage()}");
                $errors++;
            }
        }

        $total = $participants->count();

        $this->newLine();

        if ($dryRun) {
            $this->table(
                ['Total', 'Linked (existing)', 'To Create', 'Skipped (no email)', 'Errors'],
                [[$total, $linked, $created, $skipped, $errors]]
            );
            $this->warn('Dry run — no changes made. Run without --dry-run to apply.');
        } else {
            $this->table(
                ['Total', 'Linked (existing)', 'Created (new)', 'Skipped (no email)', 'Errors'],
                [[$total, $linked, $created, $skipped, $errors]]
            );
        }

        return Command::SUCCESS;
    }
}
