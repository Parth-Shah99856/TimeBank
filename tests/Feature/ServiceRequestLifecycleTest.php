<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_accept_a_pending_request(): void
    {
        [$requester, $provider, $serviceRequest] = $this->makeServiceRequest('pending');

        $response = $this->actingAs($provider)->post(route('service-requests.accept', $serviceRequest));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame('accepted', $serviceRequest->fresh()->status);
    }

    public function test_provider_can_accept_a_pending_request_with_json_response(): void
    {
        [, $provider, $serviceRequest] = $this->makeServiceRequest('pending');

        $response = $this->actingAs($provider)->postJson(route('service-requests.accept', $serviceRequest));

        $response->assertOk();
        $response->assertJsonPath('status', 'accepted');
        $this->assertSame('accepted', $serviceRequest->fresh()->status);
    }

    public function test_only_provider_can_accept_a_pending_request(): void
    {
        [$requester, $provider, $serviceRequest] = $this->makeServiceRequest('pending');

        $response = $this->actingAs($requester)->post(route('service-requests.accept', $serviceRequest));

        $response->assertForbidden();
        $this->assertSame('pending', $serviceRequest->fresh()->status);
    }

    public function test_invalid_accept_transition_is_rejected(): void
    {
        [, $provider, $serviceRequest] = $this->makeServiceRequest('accepted');

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($provider)->post(route('service-requests.accept', $serviceRequest));
            $this->fail('Expected invalid transition exception was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Invalid service request transition from [accepted] to [accepted].', $exception->getMessage());
        }
    }

    public function test_requester_can_cancel_a_pending_request(): void
    {
        [$requester, , $serviceRequest] = $this->makeServiceRequest('pending');

        $response = $this->actingAs($requester)->post(route('service-requests.cancel', $serviceRequest));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame('cancelled', $serviceRequest->fresh()->status);
    }

    public function test_requester_can_cancel_an_accepted_request(): void
    {
        [$requester, , $serviceRequest] = $this->makeServiceRequest('accepted');

        $response = $this->actingAs($requester)->post(route('service-requests.cancel', $serviceRequest));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame('cancelled', $serviceRequest->fresh()->status);
    }

    public function test_only_requester_can_cancel_a_request(): void
    {
        [, $provider, $serviceRequest] = $this->makeServiceRequest('pending');

        $response = $this->actingAs($provider)->post(route('service-requests.cancel', $serviceRequest));

        $response->assertForbidden();
        $this->assertSame('pending', $serviceRequest->fresh()->status);
    }

    public function test_cannot_cancel_in_progress_request(): void
    {
        [$requester, , $serviceRequest] = $this->makeServiceRequest('in_progress');

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($requester)->post(route('service-requests.cancel', $serviceRequest));
            $this->fail('Expected invalid cancel transition exception was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Invalid service request transition from [in_progress] to [cancelled].', $exception->getMessage());
        }
    }

    public function test_participant_can_start_an_accepted_request(): void
    {
        [$requester, , $serviceRequest] = $this->makeServiceRequest('accepted');

        $response = $this->actingAs($requester)->post(route('service-requests.start', $serviceRequest));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame('in_progress', $serviceRequest->fresh()->status);
    }

    public function test_non_participant_cannot_start_request(): void
    {
        [, , $serviceRequest] = $this->makeServiceRequest('accepted');
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->post(route('service-requests.start', $serviceRequest));

        $response->assertForbidden();
        $this->assertSame('accepted', $serviceRequest->fresh()->status);
    }

    public function test_cannot_start_pending_request(): void
    {
        [$requester, , $serviceRequest] = $this->makeServiceRequest('pending');

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($requester)->post(route('service-requests.start', $serviceRequest));
            $this->fail('Expected invalid start transition exception was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Invalid service request transition from [pending] to [in_progress].', $exception->getMessage());
        }
    }

    public function test_participant_can_dispute_accepted_request(): void
    {
        [$requester, , $serviceRequest] = $this->makeServiceRequest('accepted');

        $response = $this->actingAs($requester)->post(route('service-requests.dispute', $serviceRequest));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame('disputed', $serviceRequest->fresh()->status);
    }

    public function test_participant_can_dispute_in_progress_request(): void
    {
        [, $provider, $serviceRequest] = $this->makeServiceRequest('in_progress');

        $response = $this->actingAs($provider)->post(route('service-requests.dispute', $serviceRequest));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame('disputed', $serviceRequest->fresh()->status);
    }

    public function test_non_participant_cannot_dispute_request(): void
    {
        [, , $serviceRequest] = $this->makeServiceRequest('in_progress');
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->post(route('service-requests.dispute', $serviceRequest));

        $response->assertForbidden();
        $this->assertSame('in_progress', $serviceRequest->fresh()->status);
    }

    public function test_cannot_dispute_pending_request(): void
    {
        [$requester, , $serviceRequest] = $this->makeServiceRequest('pending');

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($requester)->post(route('service-requests.dispute', $serviceRequest));
            $this->fail('Expected invalid dispute transition exception was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Invalid service request transition from [pending] to [disputed].', $exception->getMessage());
        }
    }

    public function test_transition_endpoints_ignore_protected_fields_in_post_body(): void
    {
        [$requester, $provider, $serviceRequest] = $this->makeServiceRequest('pending');
        $otherUser = User::factory()->create();

        $this->actingAs($provider)->post(route('service-requests.accept', $serviceRequest), [
            'status' => 'completed',
            'requester_id' => $otherUser->id,
            'provider_id' => $otherUser->id,
            'total_credits' => '999.99',
        ]);

        $serviceRequest->refresh();

        $this->assertSame('accepted', $serviceRequest->status);
        $this->assertSame($requester->id, $serviceRequest->requester_id);
        $this->assertSame($provider->id, $serviceRequest->provider_id);
        $this->assertSame('2.00', $serviceRequest->total_credits);
    }

    private function makeServiceRequest(string $status): array
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

        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Test request',
            'project_scope' => 'Test scope',
            'estimated_hours' => '1.00',
            'total_credits' => '2.00',
            'desired_deadline' => null,
            'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);

        return [$requester, $provider, $serviceRequest];
    }
}
