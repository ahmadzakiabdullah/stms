<?php

namespace App\Console\Commands;

use App\Models\SportCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class StandardizeSportCategorySlugs extends Command
{
    protected $signature = 'sport-categories:standardize-slugs {--dry-run : Preview changes without saving}';
    protected $description = 'Standardize all sport category slugs to sport-name-category-name format';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $updated = 0;
        $skipped = 0;

        $categories = SportCategory::with('sport')->get();

        $this->line("Found {$categories->count()} categories.\n");

        foreach ($categories as $category) {
            if (!$category->sport) {
                $this->warn("  [SKIP] Category #{$category->id} '{$category->name}' has no sport");
                $skipped++;
                continue;
            }

            $expectedSlug = Str::slug($category->sport->name . ' ' . $category->name);

            if ($category->slug === $expectedSlug) {
                $this->line("  [OK]   {$category->slug}");
                continue;
            }

            $uniqueSlug = $this->makeSlugUnique($expectedSlug, $category->sport_id, $category->id);

            if ($dryRun) {
                $this->line("  [DRY]  {$category->slug} -> {$uniqueSlug}");
            } else {
                $old = $category->slug;
                $category->slug = $uniqueSlug;
                $category->save();
                $this->line("  [UPD]  {$old} -> {$uniqueSlug}");
            }

            $updated++;
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run complete. {$updated} categories would be updated, {$skipped} skipped.");
        } else {
            $this->info("Done. {$updated} categories updated, {$skipped} skipped.");
        }

        return Command::SUCCESS;
    }

    private function makeSlugUnique(string $slug, string $sportId, string $excludeId): string
    {
        $base = $slug;
        $counter = 1;

        while (true) {
            $query = SportCategory::where('slug', $slug)
                ->where('sport_id', $sportId)
                ->where('id', '!=', $excludeId);

            if (!$query->exists()) {
                return $slug;
            }

            $slug = $base . '-' . $counter;
            $counter++;
        }
    }
}
