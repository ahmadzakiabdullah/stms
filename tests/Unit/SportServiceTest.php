<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Sport;
use App\Services\SportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_sport_with_auto_org_and_slug(): void
    {
        $org = Organization::factory()->create();

        $service = new SportService;

        $sport = $service->createSport([
            'organization_id' => $org->id,
            'name' => 'Test Sport via Service',
        ]);

        $this->assertDatabaseHas('sports', [
            'name' => 'Test Sport via Service',
            'slug' => 'test-sport-via-service',
            'organization_id' => $org->id,
        ]);
    }

    public function test_it_updates_sport_slug_on_name_change(): void
    {
        $org = Organization::factory()->create();

        $sport = Sport::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Original Sport',
            'slug' => 'original-sport',
        ]);

        $service = new SportService;

        $updated = $service->updateSport($sport, [
            'name' => 'Updated Sport Name',
        ]);

        $this->assertEquals('updated-sport-name', $updated->slug);
        $this->assertDatabaseHas('sports', ['slug' => 'updated-sport-name']);
    }

    public function test_it_deletes_sport(): void
    {
        $sport = Sport::factory()->create();

        $service = new SportService;
        $service->deleteSport($sport);

        $this->assertSoftDeleted('sports', ['id' => $sport->id]);
    }
}
