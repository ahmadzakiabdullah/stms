<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Result;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_and_filter_cross_organization_activity(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $superAdmin = User::factory()->create(['organization_id' => $organizationA->id]);
        $superAdmin->assignRole('super-admin');
        $userA = User::factory()->create(['organization_id' => $organizationA->id]);
        $userB = User::factory()->create(['organization_id' => $organizationB->id]);

        activity()->causedBy($userA)->event('created')->log('Organization A activity');
        activity()->causedBy($userB)->event('updated')->log('Organization B activity');

        $this->actingAs($superAdmin)->get(route('activity-logs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activities.data', fn ($activities) => collect($activities)->contains('description', 'Organization A activity')
                    && collect($activities)->contains('description', 'Organization B activity'))
                ->where('isSuperAdmin', true)
            );

        $this->actingAs($superAdmin)->get(route('activity-logs.index', [
            'organization_id' => $organizationB->id,
            'event' => 'updated',
        ]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('activities.data', 1)
                ->where('activities.data.0.description', 'Organization B activity')
            );
    }

    public function test_org_admin_remains_scoped_to_own_organization(): void
    {
        Role::firstOrCreate(['name' => 'org-admin', 'guard_name' => 'web']);
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $orgAdmin = User::factory()->create(['organization_id' => $organizationA->id]);
        $orgAdmin->assignRole('org-admin');
        $userA = User::factory()->create(['organization_id' => $organizationA->id]);
        $userB = User::factory()->create(['organization_id' => $organizationB->id]);

        activity()->causedBy($userA)->event('created')->log('Visible activity');
        activity()->causedBy($userB)->event('created')->log('Hidden activity');

        $this->actingAs($orgAdmin)->get(route('activity-logs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activities.data', fn ($activities) => collect($activities)->contains('description', 'Visible activity')
                    && ! collect($activities)->contains('description', 'Hidden activity'))
                ->where('isSuperAdmin', false)
            );
    }

    public function test_activity_records_standard_audit_metadata(): void
    {
        Role::firstOrCreate(['name' => 'org-admin', 'guard_name' => 'web']);
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $subject = Organization::factory()->create(['parent_id' => $organization->id]);

        $activity = activity()
            ->causedBy($user)
            ->performedOn($subject)
            ->event('updated')
            ->log('Organization updated');

        $audit = $activity->getProperty('audit');

        $this->assertSame($user->uuid, $audit['actor_id']);
        $this->assertSame('updated', $audit['action']);
        $this->assertSame($subject->id, $audit['subject_id']);
        $this->assertSame($organization->id, $audit['organization_id']);
    }

    public function test_score_and_participant_changes_are_available_in_activity_history(): void
    {
        $organization = Organization::factory()->create();
        $participant = Participant::factory()->create(['organization_id' => $organization->id]);
        $match = Fixture::factory()->create(['organization_id' => $organization->id]);
        $result = Result::factory()->create([
            'organization_id' => $organization->id,
            'match_id' => $match->id,
            'score_home' => 1,
            'score_away' => 0,
        ]);

        $result->update(['score_home' => 2]);
        $participant->update(['name' => 'Updated participant']);

        $resultChange = Activity::query()
            ->where('subject_type', $result->getMorphClass())
            ->where('subject_id', $result->id)
            ->where('event', 'updated')
            ->latest('id')
            ->firstOrFail();
        $participantChange = Activity::query()
            ->where('subject_type', $participant->getMorphClass())
            ->where('subject_id', $participant->id)
            ->where('event', 'updated')
            ->latest('id')
            ->firstOrFail();

        $resultChanges = $resultChange->attribute_changes->toArray();
        $participantChanges = $participantChange->attribute_changes->toArray();

        $this->assertArrayHasKey('score_home', $resultChanges['attributes'] ?? $resultChanges);
        $this->assertArrayHasKey('name', $participantChanges['attributes'] ?? $participantChanges);
    }
}
