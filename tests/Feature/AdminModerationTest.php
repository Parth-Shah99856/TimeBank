<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_all_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson(route('admin.users.index'));

        $response->assertOk();
        $this->assertCount(4, $response->json());
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $user = User::factory()->create();
        User::factory()->count(2)->create();

        $response = $this->actingAs($user)->getJson(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_list_users(): void
    {
        $response = $this->getJson(route('admin.users.index'));

        $response->assertUnauthorized();
    }

    public function test_admin_can_list_service_requests_filtered_by_disputed_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $disputed = $this->makeServiceRequest('disputed');
        $this->makeServiceRequest('pending');
        $this->makeServiceRequest('completed');

        $response = $this->actingAs($admin)->getJson(route('admin.service-requests.index', ['status' => 'disputed']));

        $response->assertOk();
        $ids = array_column($response->json(), 'id');

        $this->assertSame([$disputed->id], $ids);
    }

    public function test_admin_can_list_all_service_requests_without_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->makeServiceRequest('disputed');
        $this->makeServiceRequest('pending');
        $this->makeServiceRequest('completed');

        $response = $this->actingAs($admin)->getJson(route('admin.service-requests.index'));

        $response->assertOk();
        $this->assertCount(3, $response->json());
    }

    public function test_non_admin_cannot_list_service_requests_for_moderation(): void
    {
        $user = User::factory()->create();
        $this->makeServiceRequest('disputed');

        $response = $this->actingAs($user)->getJson(route('admin.service-requests.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_list_service_requests_for_moderation(): void
    {
        $response = $this->getJson(route('admin.service-requests.index'));

        $response->assertUnauthorized();
    }

    public function test_invalid_status_filter_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->getJson(route('admin.service-requests.index', ['status' => 'not-a-real-status']));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('status');
    }

    private function makeServiceRequest(string $status): ServiceRequest
    {
        $requester = User::factory()->create();
        $provider = User::factory()->create();
        $category = Category::query()->create([
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);

        return ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Sample request',
            'project_scope' => 'Sample scope',
            'estimated_hours' => '1.00',
            'total_credits' => '2.00',
            'desired_deadline' => null,
            'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);
    }
}
