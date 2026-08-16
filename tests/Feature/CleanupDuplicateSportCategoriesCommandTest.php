<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanupDuplicateSportCategoriesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_soft_deletes_only_the_unreferenced_duplicate(): void
    {
        $organization = Organization::factory()->create();
        $sport = Sport::factory()->create(['organization_id' => $organization->id]);
        $kept = SportCategory::factory()->forSport($sport)->create(['name' => "Men's", 'slug' => 'football-men-s']);
        $duplicate = SportCategory::factory()->forSport($sport)->create(['name' => "Men's", 'slug' => 'football-mens']);

        Event::factory()->create([
            'organization_id' => $organization->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $kept->id,
        ]);

        $this->artisan('sport-categories:cleanup-duplicates', ['--apply' => true])
            ->expectsOutputToContain('Soft-deleted 1 duplicate sport categories.')
            ->assertSuccessful();

        $this->assertNotSoftDeleted('sport_categories', ['id' => $kept->id]);
        $this->assertSoftDeleted('sport_categories', ['id' => $duplicate->id]);
    }

    public function test_it_aborts_when_no_single_referenced_category_can_be_identified(): void
    {
        $organization = Organization::factory()->create();
        $sport = Sport::factory()->create(['organization_id' => $organization->id]);
        SportCategory::factory()->forSport($sport)->create(['name' => 'Mix', 'slug' => 'tenpin-bowling-mix']);
        SportCategory::factory()->forSport($sport)->create(['name' => 'Mix', 'slug' => 'tenpin-bowling-mixed']);

        $this->artisan('sport-categories:cleanup-duplicates', ['--apply' => true])
            ->expectsOutputToContain('Cleanup aborted')
            ->assertFailed();

        $this->assertSame(2, SportCategory::withoutGlobalScopes()->count());
    }

    public function test_a_soft_deleted_event_does_not_keep_a_duplicate_category_visible(): void
    {
        $organization = Organization::factory()->create();
        $sport = Sport::factory()->create(['organization_id' => $organization->id]);
        $kept = SportCategory::factory()->forSport($sport)->create(['name' => "Women's", 'slug' => 'hockey-women-s']);
        $duplicate = SportCategory::factory()->forSport($sport)->create(['name' => "Women's", 'slug' => 'hockey-womens']);

        Event::factory()->create([
            'organization_id' => $organization->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $kept->id,
        ]);
        Event::factory()->create([
            'organization_id' => $organization->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $duplicate->id,
        ])->delete();

        $this->artisan('sport-categories:cleanup-duplicates', ['--apply' => true])
            ->assertSuccessful();

        $this->assertSoftDeleted('sport_categories', ['id' => $duplicate->id]);
    }
}
