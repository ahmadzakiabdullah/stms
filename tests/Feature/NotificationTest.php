<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Support\Str;

class TestNotification extends Notification
{
    public $id;

    public function __construct()
    {
        $this->id = Str::uuid()->toString();
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Test notification message',
            'type' => 'info'
        ];
    }
}

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_notifications_page(): void
    {
        $user = User::factory()->create();
        $user->notify(new TestNotification());
        $user->notify(new TestNotification());

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
        $user->notify(new TestNotification());

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->get(route('notifications.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'notifications' => [
                    '*' => ['id', 'type', 'data', 'read_at', 'created_at']
                ],
                'unread_count',
                'has_more'
            ])
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('unread_count', 1);
    }

    public function test_user_can_get_unread_count(): void
    {
        $user = User::factory()->create();
        $user->notify(new TestNotification());
        $user->notify(new TestNotification());

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
        $user->notify(new TestNotification());

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
        $user->notify(new TestNotification());

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
        $user->notify(new TestNotification());
        $user->notify(new TestNotification());

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
        $user1->notify(new TestNotification());
        $notification = $user1->notifications()->first();

        $user2 = User::factory()->create();

        $response = $this->actingAs($user2)
            ->post(route('notifications.mark-read', $notification->id));

        $response->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }
}
