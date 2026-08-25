<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_their_notifications(): void
    {
        $user = User::factory()->create();
        $this->createNotification($user);
        $this->createNotification($user);

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_unauthenticated_user_is_rejected_from_listing(): void
    {
        $response = $this->getJson(route('notifications.index'));

        $response->assertUnauthorized();
    }

    public function test_notification_list_contains_only_authenticated_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownNotification = $this->createNotification($user);
        $this->createNotification($otherUser);

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk();
        $ids = array_column($response->json(), 'id');
        $this->assertSame([$ownNotification->id], $ids);
    }

    public function test_user_can_mark_their_own_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = $this->createNotification($user);

        $response = $this->actingAs($user)->patchJson(route('notifications.read', $notification));

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = $this->createNotification($otherUser);

        $response = $this->actingAs($user)->patchJson(route('notifications.read', $notification));

        $response->assertForbidden();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_affects_only_authenticated_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $first = $this->createNotification($user);
        $second = $this->createNotification($user);
        $othersNotification = $this->createNotification($otherUser);

        $response = $this->actingAs($user)->postJson(route('notifications.read-all'));

        $response->assertOk();
        $this->assertNotNull($first->fresh()->read_at);
        $this->assertNotNull($second->fresh()->read_at);
        $this->assertNull($othersNotification->fresh()->read_at);
    }

    public function test_already_read_notification_remains_valid_when_marked_again(): void
    {
        $user = User::factory()->create();
        $notification = $this->createNotification($user);
        $notification->markAsRead();
        $firstReadAt = $notification->fresh()->read_at;

        $response = $this->actingAs($user)->patchJson(route('notifications.read', $notification));

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame($firstReadAt->toDateTimeString(), $notification->fresh()->read_at->toDateTimeString());
    }

    public function test_invalid_notification_id_returns_not_found(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson(route('notifications.read', ['notification' => Str::uuid()->toString()]));

        $response->assertNotFound();
    }

    public function test_request_fields_cannot_change_notification_ownership(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = $this->createNotification($otherUser);

        $response = $this->actingAs($user)->patchJson(route('notifications.read', $notification), [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'user_id' => $user->id,
        ]);

        $response->assertForbidden();
        $this->assertNull($notification->fresh()->read_at);
        $this->assertSame($otherUser->id, $notification->fresh()->notifiable_id);
    }

    private function createNotification(User $user): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\TestNotification',
            'data' => ['message' => 'test'],
            'read_at' => null,
        ]);
    }
}
