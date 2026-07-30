<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Session;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_session_with_auto_org_and_slug(): void
    {
        $org = Organization::factory()->create();

        $service = new SessionService;

        $session = $service->createSession([
            'organization_id' => $org->id,
            'name' => 'Test Session via Service',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-15',
        ]);

        $this->assertDatabaseHas('event_sessions', [
            'name' => 'Test Session via Service',
            'slug' => 'test-session-via-service',
            'organization_id' => $org->id,
        ]);
    }

    public function test_it_updates_session_and_regenerates_slug(): void
    {
        $org = Organization::factory()->create();

        $session = Session::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Original Session',
            'slug' => 'original-session',
            'start_date' => '2026-06-01',
        ]);

        $service = new SessionService;

        $updated = $service->updateSession($session, [
            'name' => 'Updated Session Name',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-15',
        ]);

        $this->assertEquals('updated-session-name', $updated->slug);
        $this->assertDatabaseHas('event_sessions', ['slug' => 'updated-session-name']);
    }

    public function test_it_deletes_session(): void
    {
        $session = Session::factory()->create();

        $service = new SessionService;
        $service->deleteSession($session);

        $this->assertSoftDeleted('event_sessions', ['id' => $session->id]);
    }
}
