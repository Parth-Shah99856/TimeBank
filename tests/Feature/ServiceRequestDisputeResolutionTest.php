<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestDisputeResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_resolve_dispute_as_completed_and_transfer_credits(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$serviceRequest, $requester, $provider] = $this->makeDisputedRequest('4.00', '10.00', '1.00');

        $response = $this->actingAs($admin)->postJson(
            route('service-requests.resolve-dispute', $serviceRequest),
            ['resolution' => 'completed'],
        );

        $response->assertOk();

        $requester->refresh();
        $provider->refresh();
        $serviceRequest->refresh();

        $this->assertSame('6.00', $requester->time_balance);
        $this->assertSame('5.00', $provider->time_balance);
        $this->assertSame('completed', $serviceRequest->status);
        $this->assertNotNull($serviceRequest->completed_at);

        $transaction = Transaction::query()->where('service_request_id', $serviceRequest->id)->firstOrFail();
        $this->assertSame(Transaction::TYPE_SERVICE_EXCHANGE, $transaction->type);
        $this->assertSame('4.00', $transaction->amount);
        $this->assertSame($requester->id, $transaction->from_user_id);
        $this->assertSame($provider->id, $transaction->to_user_id);

        $this->assertNotNull($requester->notifications()->first());
        $this->assertNotNull($provider->notifications()->first());
    }

    public function test_admin_can_resolve_dispute_as_cancelled_without_transferring_credits(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$serviceRequest, $requester, $provider] = $this->makeDisputedRequest('4.00', '10.00', '1.00');

        $response = $this->actingAs($admin)->postJson(
            route('service-requests.resolve-dispute', $serviceRequest),
            ['resolution' => 'cancelled'],
        );

        $response->assertOk();

        $requester->refresh();
        $provider->refresh();
        $serviceRequest->refresh();

        $this->assertSame('10.00', $requester->time_balance);
        $this->assertSame('1.00', $provider->time_balance);
        $this->assertSame('cancelled', $serviceRequest->status);
        $this->assertNull($serviceRequest->completed_at);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_non_admin_participant_cannot_resolve_dispute(): void
    {
        [$serviceRequest, $requester] = $this->makeDisputedRequest('4.00', '10.00', '1.00');

        $response = $this->actingAs($requester)->postJson(
            route('service-requests.resolve-dispute', $serviceRequest),
            ['resolution' => 'completed'],
        );

        $response->assertForbidden();
        $this->assertSame('disputed', $serviceRequest->fresh()->status);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_unauthenticated_user_cannot_resolve_dispute(): void
    {
        [$serviceRequest] = $this->makeDisputedRequest('4.00', '10.00', '1.00');

        $response = $this->postJson(
            route('service-requests.resolve-dispute', $serviceRequest),
            ['resolution' => 'completed'],
        );

        $response->assertUnauthorized();
    }

    public function test_non_disputed_request_cannot_be_resolved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$serviceRequest] = $this->makeDisputedRequest('4.00', '10.00', '1.00', 'in_progress');

        $this->withoutExceptionHandling();
        $thrown = false;

        try {
            $this->actingAs($admin)->postJson(
                route('service-requests.resolve-dispute', $serviceRequest),
                ['resolution' => 'completed'],
            );
        } catch (\RuntimeException $exception) {
            $thrown = true;
            $this->assertSame('Only disputed service requests can be resolved.', $exception->getMessage());
        }

        $this->assertTrue($thrown, 'Expected invalid-state exception was not thrown.');
    }

    public function test_invalid_resolution_value_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$serviceRequest] = $this->makeDisputedRequest('4.00', '10.00', '1.00');

        $response = $this->actingAs($admin)->postJson(
            route('service-requests.resolve-dispute', $serviceRequest),
            ['resolution' => 'accepted'],
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('resolution');
        $this->assertSame('disputed', $serviceRequest->fresh()->status);
    }

    public function test_duplicate_resolution_attempt_does_not_transfer_credits_twice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$serviceRequest, $requester, $provider] = $this->makeDisputedRequest('4.00', '10.00', '1.00');

        $this->actingAs($admin)->postJson(
            route('service-requests.resolve-dispute', $serviceRequest),
            ['resolution' => 'completed'],
        )->assertOk();

        $this->withoutExceptionHandling();
        $thrown = false;

        try {
            $this->actingAs($admin)->postJson(
                route('service-requests.resolve-dispute', $serviceRequest),
                ['resolution' => 'completed'],
            );
        } catch (\RuntimeException $exception) {
            $thrown = true;
            $this->assertSame('Only disputed service requests can be resolved.', $exception->getMessage());
        }

        $this->assertTrue($thrown, 'Expected duplicate-resolution exception was not thrown.');
        $this->assertSame('6.00', $requester->fresh()->time_balance);
        $this->assertSame('5.00', $provider->fresh()->time_balance);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_insufficient_balance_blocks_completed_resolution(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$serviceRequest, $requester, $provider] = $this->makeDisputedRequest('4.00', '1.00', '1.00');

        $this->withoutExceptionHandling();
        $thrown = false;

        try {
            $this->actingAs($admin)->postJson(
                route('service-requests.resolve-dispute', $serviceRequest),
                ['resolution' => 'completed'],
            );
        } catch (\RuntimeException $exception) {
            $thrown = true;
            $this->assertSame('Insufficient time balance to complete this exchange.', $exception->getMessage());
        }

        $this->assertTrue($thrown, 'Expected insufficient-balance exception was not thrown.');
        $this->assertSame('disputed', $serviceRequest->fresh()->status);
        $this->assertSame('1.00', $requester->fresh()->time_balance);
        $this->assertSame('1.00', $provider->fresh()->time_balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_protected_fields_cannot_be_spoofed_through_input(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $impersonated = User::factory()->create();
        [$serviceRequest, $requester, $provider] = $this->makeDisputedRequest('4.00', '10.00', '1.00');

        $this->actingAs($admin)->postJson(
            route('service-requests.resolve-dispute', $serviceRequest),
            [
                'resolution' => 'completed',
                'total_credits' => '999.00',
                'requester_id' => $impersonated->id,
                'provider_id' => $impersonated->id,
                'status' => 'in_progress',
            ],
        )->assertOk();

        $serviceRequest->refresh();
        $requester->refresh();
        $provider->refresh();

        $this->assertSame('4.00', $serviceRequest->total_credits);
        $this->assertSame($requester->id, $serviceRequest->requester_id);
        $this->assertSame($provider->id, $serviceRequest->provider_id);
        $this->assertSame('6.00', $requester->time_balance);
        $this->assertSame('5.00', $provider->time_balance);
    }

    private function makeDisputedRequest(
        string $totalCredits,
        string $requesterBalance,
        string $providerBalance,
        string $status = 'disputed',
    ): array {
        $requester = User::factory()->create(['time_balance' => $requesterBalance]);
        $provider = User::factory()->create(['time_balance' => $providerBalance]);
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
            'title' => 'Disputed request',
            'project_scope' => 'Some scope',
            'estimated_hours' => '2.00',
            'total_credits' => $totalCredits,
            'desired_deadline' => null,
            'status' => $status,
            'completed_at' => null,
        ]);

        return [$serviceRequest, $requester, $provider];
    }
}
