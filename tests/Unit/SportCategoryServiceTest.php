<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Services\SportCategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SportCategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_sport_category_with_org_from_sport(): void
    {
        $org = Organization::factory()->create();
        $sport = Sport::factory()->create(['organization_id' => $org->id]);

        $service = new SportCategoryService;

        $category = $service->createSportCategory([
            'sport_id' => $sport->id,
            'name' => 'Test Category via Service',
        ]);

        $this->assertDatabaseHas('sport_categories', [
            'name' => 'Test Category via Service',
            'slug' => Str::slug($sport->name.' Test Category via Service'),
            'organization_id' => $org->id,
            'sport_id' => $sport->id,
        ]);
    }

    public function test_it_updates_sport_category_slug(): void
    {
        $org = Organization::factory()->create();
        $sport = Sport::factory()->create(['organization_id' => $org->id]);

        $category = SportCategory::factory()->create([
            'organization_id' => $org->id,
            'sport_id' => $sport->id,
            'name' => 'Original Category',
            'slug' => 'original-category',
        ]);

        $service = new SportCategoryService;

        $updated = $service->updateSportCategory($category, [
            'name' => 'Updated Category Name',
        ]);

        $expectedSlug = Str::slug($sport->name.' Updated Category Name');

        $this->assertEquals($expectedSlug, $updated->slug);
        $this->assertDatabaseHas('sport_categories', ['slug' => $expectedSlug]);
    }

    public function test_it_deletes_sport_category(): void
    {
        $org = Organization::factory()->create();
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->create([
            'organization_id' => $org->id,
            'sport_id' => $sport->id,
        ]);

        $service = new SportCategoryService;
        $service->deleteSportCategory($category);

        $this->assertSoftDeleted('sport_categories', ['id' => $category->id]);
    }
}
