<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TestNotification extends Notification
{
    public $id;

    public function __construct(private array $payload = [])
    {
        $this->id = Str::uuid()->toString();
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return array_merge([
            'message' => 'Test notification message',
            'type' => 'info',
        ], $this->payload);
    }
}

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_defaults_to_unread_action_required_notifications(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('super-admin');

        $user->notify(new TestNotification([
            'type' => 'new_registration',
            'severity' => 'warning',
            'organization_id' => $organization->id,
            'organization_name' => $organization->name,
        ]));
        $user->notify(new TestNotification(['type' => 'result_recorded']));
        $user->notify(new TestNotification(['type' => 'new_registration', 'message' => 'Read action']));
        $user->notifications()->where('data->message', 'Read action')->firstOrFail()->markAsRead();

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.tab', 'action')
                ->where('filters.status', 'unread')
                ->where('counts.action_required', 1)
                ->has('notifications.data', 1)
                ->where('notifications.data.0.data.type', 'new_registration')
                ->where('isSuperAdmin', true)
            );
    }

    public function test_super_admin_can_filter_personal_notifications_by_organization(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organizationA->id]);
        $user->assignRole('super-admin');

        $user->notify(new TestNotification(['organization_id' => $organizationA->id]));
        $user->notify(new TestNotification(['organization_id' => $organizationB->id]));

        $this->actingAs($user)->get(route('notifications.index', [
            'tab' => 'inbox',
            'organization_id' => $organizationB->id,
        ]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.tab', 'inbox')
                ->where('filters.organization_id', $organizationB->id)
                ->has('notifications.data', 1)
                ->where('notifications.data.0.data.organization_id', $organizationB->id)
            );
    }

    public function test_regular_user_cannot_switch_to_action_required_view(): void
    {
        $user = User::factory()->create();
        $user->notify(new TestNotification(['type' => 'new_registration']));

        $this->actingAs($user)->get(route('notifications.index', ['tab' => 'action']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.tab', 'inbox')
                ->where('isSuperAdmin', false)
                ->has('notifications.data', 1)
            );
    }

    public function test_user_can_view_notifications_page(): void
    {
        $user = User::factory()->create();
        $user->notify(new TestNotification);
        $user->notify(new TestNotification);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Notifications/Index')
                ->has('notifications.data', 2)
            );
    }

    public function test_user_can_fetch_notifications_via_json(): void
    {
        $user = User::factory()->create();
        $user->notify(new TestNotification);

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->get(route('notifications.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'notifications' => [
                    '*' => ['id', 'type', 'data', 'read_at', 'created_at'],
                ],
                'unread_count',
                'has_more',
            ])
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('unread_count', 1);
    }

    public function test_user_can_get_unread_count(): void
    {
        $user = User::factory()->create();
        $user->notify(new TestNotification);
        $user->notify(new TestNotification);

        // Mark one as read
        $notification = $user->notifications()->first();
        $notification->markAsRead();

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->get(route('notifications.unread-count'));

        $response->assertOk()
            ->assertJson(['count' => 1]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new TestNotification);

        $notification = $user->notifications()->first();
        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)
            ->post(route('notifications.mark-read', $notification->id));

        $response->assertRedirect()
            ->assertSessionHas('success', 'Notification marked as read.');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_notification_as_read_via_json(): void
    {
        $user = User::factory()->create();
        $user->notify(new TestNotification);

        $notification = $user->notifications()->first();
        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('notifications.mark-read', $notification->id));

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new TestNotification);
        $user->notify(new TestNotification);

        $this->assertEquals(2, $user->unreadNotifications()->count());

        $response = $this->actingAs($user)
            ->post(route('notifications.mark-all-read'));

        $response->assertRedirect()
            ->assertSessionHas('success', 'All notifications marked as read.');

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $user1 = User::factory()->create();
        $user1->notify(new TestNotification);
        $notification = $user1->notifications()->first();

        $user2 = User::factory()->create();

        $response = $this->actingAs($user2)
            ->post(route('notifications.mark-read', $notification->id));

        $response->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }
}
