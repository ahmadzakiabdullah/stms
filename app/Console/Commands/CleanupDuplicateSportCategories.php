<?php

namespace App\Console\Commands;

use App\Services\DuplicateSportCategoryCleanupService;
use Illuminate\Console\Command;

class CleanupDuplicateSportCategories extends Command
{
    protected $signature = 'sport-categories:cleanup-duplicates
        {--apply : Soft-delete verified, unreferenced duplicates}';

    protected $description = 'Find duplicate sport categories and safely remove copies unused by active events';

    public function handle(DuplicateSportCategoryCleanupService $service): int
    {
        $report = $service->inspect();
        $removable = $report['removable'];

        $this->info("Duplicate groups: {$report['duplicate_groups']}");
        $this->info("Safe to remove: {$removable->count()}");
        $this->info("Blocked groups: {$report['blocked_groups']->count()}");

        foreach ($removable as $category) {
            $this->line("  {$category->id} | {$category->name} | {$category->slug}");
        }

        if (! $this->option('apply')) {
            $this->comment('Dry run only. Re-run with --apply to soft-delete the listed records.');

            return self::SUCCESS;
        }

        if ($report['blocked_groups']->isNotEmpty()) {
            $this->error('Cleanup aborted because at least one duplicate group has an ambiguous reference state.');

            return self::FAILURE;
        }

        $deleted = $service->cleanup($removable);
        $this->info("Soft-deleted {$deleted} duplicate sport categories.");

        return $deleted === $removable->count() ? self::SUCCESS : self::FAILURE;
    }
}
