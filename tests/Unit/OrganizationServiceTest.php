<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrganizationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_organization_with_slug(): void
    {
        $service = new OrganizationService;

        $org = $service->createOrganization([
            'name' => 'Test Organization via Service',
            'organization_type' => 'university',
        ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Organization via Service',
            'slug' => 'test-organization-via-service',
        ]);
    }

    public function test_it_updates_organization_slug(): void
    {
        $org = Organization::factory()->create([
            'name' => 'Original Org',
            'slug' => 'original-org',
        ]);

        $service = new OrganizationService;

        $updated = $service->updateOrganization($org, [
            'name' => 'Updated Organization Name',
        ]);

        $this->assertEquals('updated-organization-name', $updated->slug);
        $this->assertDatabaseHas('organizations', ['slug' => 'updated-organization-name']);
    }

    public function test_it_deletes_organization(): void
    {
        $org = Organization::factory()->create();

        $service = new OrganizationService;
        $service->deleteOrganization($org);

        $this->assertSoftDeleted('organizations', ['id' => $org->id]);
    }

    public function test_it_rejects_parent_cycles(): void
    {
        $parent = Organization::factory()->create();
        $child = Organization::factory()->create(['parent_id' => $parent->id]);

        $this->expectException(ValidationException::class);
        (new OrganizationService)->updateOrganization($parent, [
            'name' => $parent->name,
            'slug' => $parent->slug,
            'parent_id' => $child->id,
        ]);
    }
}
