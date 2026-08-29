<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_service_request(): void
    {
        [$requester, $provider, $service] = $this->makeService('3.00');

        $response = $this->actingAs($requester)->post(route('service-requests.store'), [
            'service_id' => $service->id,
            'title' => 'Need help moving furniture',
            'project_scope' => 'Move a sofa and two bookshelves',
            'estimated_hours' => '2.00',
            'desired_deadline' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $serviceRequest = ServiceRequest::query()->firstOrFail();
        $this->assertSame($requester->id, $serviceRequest->requester_id);
        $this->assertSame($provider->id, $serviceRequest->provider_id);
        $this->assertSame($service->category_id, $serviceRequest->category_id);
        $this->assertSame($service->id, $serviceRequest->service_id);
        $this->assertSame('pending', $serviceRequest->status);
        $this->assertSame('6.00', $serviceRequest->total_credits);
        $this->assertNull($serviceRequest->completed_at);
    }

    public function test_unauthenticated_user_cannot_create_a_service_request(): void
    {
        [, , $service] = $this->makeService('3.00');

        $response = $this->post(route('service-requests.store'), [
            'service_id' => $service->id,
            'title' => 'Need help',
            'project_scope' => 'Some scope',
            'estimated_hours' => '2.00',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_total_credits_is_always_server_calculated(): void
    {
        [$requester, , $service] = $this->makeService('4.50');

        $this->actingAs($requester)->post(route('service-requests.store'), [
            'service_id' => $service->id,
            'title' => 'Tutoring session',
            'project_scope' => 'Algebra tutoring',
            'estimated_hours' => '2.00',
            'total_credits' => '999999.00',
        ]);

        $serviceRequest = ServiceRequest::query()->firstOrFail();
        $this->assertSame('9.00', $serviceRequest->total_credits);
    }

    public function test_protected_fields_cannot_be_spoofed_through_input(): void
    {
        [$requester, $provider, $service] = $this->makeService('2.00');
        $impersonatedProvider = User::factory()->create();
        $otherCategory = $this->activeCategory('Other', 'other');

        $this->actingAs($requester)->post(route('service-requests.store'), [
            'service_id' => $service->id,
            'requester_id' => 999999,
            'provider_id' => $impersonatedProvider->id,
            'category_id' => $otherCategory->id,
            'status' => 'completed',
            'completed_at' => now()->toDateTimeString(),
            'title' => 'Spoofing attempt',
            'project_scope' => 'Testing protected fields',
            'estimated_hours' => '1.00',
        ]);

        $serviceRequest = ServiceRequest::query()->firstOrFail();
        $this->assertSame($requester->id, $serviceRequest->requester_id);
        $this->assertSame($provider->id, $serviceRequest->provider_id);
        $this->assertSame($service->category_id, $serviceRequest->category_id);
        $this->assertSame('pending', $serviceRequest->status);
        $this->assertNull($serviceRequest->completed_at);
    }

    public function test_user_cannot_request_their_own_service(): void
    {
        [, $provider, $service] = $this->makeService('2.00');

        $this->withoutExceptionHandling();

        $thrown = false;

        try {
            $this->actingAs($provider)->post(route('service-requests.store'), [
                'service_id' => $service->id,
                'title' => 'Self request',
                'project_scope' => 'Trying to request my own service',
                'estimated_hours' => '1.00',
            ]);
        } catch (\RuntimeException $exception) {
            $thrown = true;
            $this->assertSame('You cannot request your own service.', $exception->getMessage());
        }

        $this->assertTrue($thrown, 'Expected self-request exception was not thrown.');
    }

    public function test_inactive_service_cannot_be_requested(): void
    {
        [$requester, , $service] = $this->makeService('2.00', isActive: false);

        $response = $this->actingAs($requester)->post(route('service-requests.store'), [
            'service_id' => $service->id,
            'title' => 'Inactive service request',
            'project_scope' => 'Should fail validation',
            'estimated_hours' => '1.00',
        ]);

        $response->assertSessionHasErrors('service_id');
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_estimated_hours_must_be_positive(): void
    {
        [$requester, , $service] = $this->makeService('2.00');

        $response = $this->actingAs($requester)->post(route('service-requests.store'), [
            'service_id' => $service->id,
            'title' => 'Zero hours request',
            'project_scope' => 'Should fail validation',
            'estimated_hours' => '0.00',
        ]);

        $response->assertSessionHasErrors('estimated_hours');
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_desired_deadline_cannot_be_in_the_past(): void
    {
        [$requester, , $service] = $this->makeService('2.00');

        $response = $this->actingAs($requester)->post(route('service-requests.store'), [
            'service_id' => $service->id,
            'title' => 'Past deadline request',
            'project_scope' => 'Should fail validation',
            'estimated_hours' => '1.00',
            'desired_deadline' => now()->subDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors('desired_deadline');
        $this->assertDatabaseCount('service_requests', 0);
    }

    private function makeService(string $hourlyRate, bool $isActive = true): array
    {
        $provider = User::factory()->create();
        $requester = User::factory()->create();
        $category = $this->activeCategory(fake()->unique()->words(2, true), fake()->unique()->slug());

        $service = Service::query()->create([
            'user_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Sample Service',
            'description' => 'Sample description',
            'hourly_rate' => $hourlyRate,
            'tags' => null,
            'is_active' => $isActive,
        ]);

        return [$requester, $provider, $service];
    }

    private function activeCategory(string $name, string $slug): Category
    {
        return Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
    }
}
