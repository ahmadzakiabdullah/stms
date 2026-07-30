<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Console\Command;

class BackfillEventParticipantOrgId extends Command
{
    protected $signature = 'event-participants:backfill-org
        {--dry-run : Show what would be done without making changes}';

    protected $description = 'Backfill organization_id for EventParticipant records where it is null';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $records = EventParticipant::whereNull('organization_id')->get();
        $total = $records->count();

        if ($total === 0) {
            $this->info('No EventParticipant records missing organization_id.');

            return Command::SUCCESS;
        }

        $this->line("Found {$total} EventParticipant records without organization_id.");
        $updated = 0;
        $skipped = 0;

        foreach ($records as $ep) {
            $event = Event::find($ep->event_id);

            if (! $event || ! $event->organization_id) {
                $this->warn("  SKIP [{$ep->id}] — event {$ep->event_id} has no org");
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("  WOULD UPDATE [{$ep->id}] → org {$event->organization_id}");
            } else {
                $ep->updateQuietly(['organization_id' => $event->organization_id]);
                $this->info("  UPDATED [{$ep->id}] → org {$event->organization_id}");
            }

            $updated++;
        }

        $this->newLine();

        if ($dryRun) {
            $this->table(
                ['Total Missing', 'Would Update', 'Skipped (no event/org)'],
                [[$total, $updated, $skipped]]
            );
            $this->warn('Dry run — no changes made. Run without --dry-run to apply.');
        } else {
            $this->table(
                ['Total Missing', 'Updated', 'Skipped (no event/org)'],
                [[$total, $updated, $skipped]]
            );
        }

        return Command::SUCCESS;
    }
}
