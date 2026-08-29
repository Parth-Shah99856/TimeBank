<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_requests_where_they_are_requester_or_provider(): void
    {
        $user = User::factory()->create();
        $otherA = User::factory()->create();
        $otherB = User::factory()->create();
        $category = $this->activeCategory();

        $asRequester = $this->makeServiceRequest($category, $user->id, $otherA->id);
        $asProvider = $this->makeServiceRequest($category, $otherB->id, $user->id);
        $unrelated = $this->makeServiceRequest($category, $otherA->id, $otherB->id);

        $response = $this->actingAs($user)->getJson(route('service-requests.index'));

        $response->assertOk();
        $ids = array_column($response->json(), 'id');

        $this->assertContains($asRequester->id, $ids);
        $this->assertContains($asProvider->id, $ids);
        $this->assertNotContains($unrelated->id, $ids);
        $this->assertCount(2, $ids);
    }

    public function test_response_marks_viewer_role_correctly(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = $this->activeCategory();

        $asRequester = $this->makeServiceRequest($category, $user->id, $other->id);
        $asProvider = $this->makeServiceRequest($category, $other->id, $user->id);

        $response = $this->actingAs($user)->getJson(route('service-requests.index'));

        $response->assertOk();
        $payload = collect($response->json())->keyBy('id');

        $this->assertSame('requester', $payload[$asRequester->id]['viewer_role']);
        $this->assertSame('provider', $payload[$asProvider->id]['viewer_role']);
    }

    public function test_requests_are_ordered_newest_first(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = $this->activeCategory();

        $older = $this->makeServiceRequest($category, $user->id, $other->id);
        $older->forceFill(['created_at' => now()->subDays(2)])->save();

        $newer = $this->makeServiceRequest($category, $user->id, $other->id);
        $newer->forceFill(['created_at' => now()])->save();

        $response = $this->actingAs($user)->getJson(route('service-requests.index'));

        $response->assertOk();
        $ids = array_column($response->json(), 'id');

        $this->assertSame($newer->id, $ids[0]);
        $this->assertSame($older->id, $ids[1]);
    }

    public function test_empty_result_when_user_has_no_service_requests(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('service-requests.index'));

        $response->assertOk();
        $this->assertSame([], $response->json());
    }

    public function test_unauthenticated_user_cannot_list_service_requests(): void
    {
        $response = $this->getJson(route('service-requests.index'));

        $response->assertUnauthorized();
    }

    public function test_query_parameters_cannot_be_used_to_view_another_users_requests(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = $this->activeCategory();

        $ownRequest = $this->makeServiceRequest($category, $user->id, $other->id);
        $othersOnlyRequest = $this->makeServiceRequest($category, $other->id, User::factory()->create()->id);

        $response = $this->actingAs($user)->getJson(route('service-requests.index', [
            'requester_id' => $other->id,
            'provider_id' => $other->id,
            'user_id' => $other->id,
        ]));

        $response->assertOk();
        $ids = array_column($response->json(), 'id');

        $this->assertSame([$ownRequest->id], $ids);
        $this->assertNotContains($othersOnlyRequest->id, $ids);
    }

    private function makeServiceRequest(Category $category, int $requesterId, int $providerId): ServiceRequest
    {
        return ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requesterId,
            'provider_id' => $providerId,
            'category_id' => $category->id,
            'title' => 'Sample request',
            'project_scope' => 'Sample scope',
            'estimated_hours' => '1.00',
            'total_credits' => '2.00',
            'desired_deadline' => null,
            'status' => 'pending',
            'completed_at' => null,
        ]);
    }

    private function activeCategory(): Category
    {
        return Category::query()->create([
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
    }
}
