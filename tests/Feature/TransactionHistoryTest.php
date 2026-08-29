<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_their_own_transactions(): void
    {
        $user = User::factory()->create();
        $otherA = User::factory()->create();
        $otherB = User::factory()->create();

        $incoming = $this->makeTransaction(null, $user->id, '5.00', Transaction::TYPE_SIGNUP_BONUS);
        $outgoing = $this->makeTransaction($user->id, $otherA->id, '3.00', Transaction::TYPE_SERVICE_EXCHANGE);
        $unrelated = $this->makeTransaction($otherA->id, $otherB->id, '2.00', Transaction::TYPE_SERVICE_EXCHANGE);

        $response = $this->actingAs($user)->getJson(route('transactions.index'));

        $response->assertOk();
        $ids = array_column($response->json(), 'id');

        $this->assertContains($incoming->id, $ids);
        $this->assertContains($outgoing->id, $ids);
        $this->assertNotContains($unrelated->id, $ids);
        $this->assertCount(2, $ids);
    }

    public function test_transaction_direction_is_correct_for_credit_and_debit(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $credit = $this->makeTransaction(null, $user->id, '5.00', Transaction::TYPE_SIGNUP_BONUS);
        $debit = $this->makeTransaction($user->id, $other->id, '3.00', Transaction::TYPE_SERVICE_EXCHANGE);

        $response = $this->actingAs($user)->getJson(route('transactions.index'));

        $response->assertOk();
        $payload = collect($response->json())->keyBy('id');

        $this->assertSame('credit', $payload[$credit->id]['direction']);
        $this->assertSame('debit', $payload[$debit->id]['direction']);
    }

    public function test_transaction_includes_service_request_relation_when_available(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = \App\Models\Category::query()->create([
            'name' => 'Sample',
            'slug' => 'sample-'.uniqid(),
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $serviceRequest = \App\Models\ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $user->id,
            'provider_id' => $other->id,
            'category_id' => $category->id,
            'title' => 'Linked request',
            'project_scope' => 'Scope',
            'estimated_hours' => '1.00',
            'total_credits' => '2.00',
            'desired_deadline' => null,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $transaction = Transaction::create([
            'transaction_code' => 'TX-LINKED-'.uniqid(),
            'from_user_id' => $user->id,
            'to_user_id' => $other->id,
            'service_request_id' => $serviceRequest->id,
            'amount' => '2.00',
            'type' => Transaction::TYPE_SERVICE_EXCHANGE,
            'description' => 'Service exchange completion',
            'created_at' => now(),
        ]);

        $bonusOnly = $this->makeTransaction(null, $user->id, '5.00', Transaction::TYPE_SIGNUP_BONUS);

        $response = $this->actingAs($user)->getJson(route('transactions.index'));

        $response->assertOk();
        $payload = collect($response->json())->keyBy('id');

        $this->assertSame($serviceRequest->id, $payload[$transaction->id]['service_request']['id']);
        $this->assertNull($payload[$bonusOnly->id]['service_request']);
    }

    public function test_empty_result_when_user_has_no_transactions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('transactions.index'));

        $response->assertOk();
        $this->assertSame([], $response->json());
    }

    public function test_unauthenticated_user_cannot_list_transactions(): void
    {
        $response = $this->getJson(route('transactions.index'));

        $response->assertUnauthorized();
    }

    public function test_query_parameters_cannot_be_used_to_view_another_users_transactions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $ownTransaction = $this->makeTransaction(null, $user->id, '5.00', Transaction::TYPE_SIGNUP_BONUS);
        $othersOnlyTransaction = $this->makeTransaction(null, $other->id, '5.00', Transaction::TYPE_SIGNUP_BONUS);

        $response = $this->actingAs($user)->getJson(route('transactions.index', [
            'user_id' => $other->id,
            'from_user_id' => $other->id,
            'to_user_id' => $other->id,
        ]));

        $response->assertOk();
        $ids = array_column($response->json(), 'id');

        $this->assertSame([$ownTransaction->id], $ids);
        $this->assertNotContains($othersOnlyTransaction->id, $ids);
    }

    public function test_transactions_remain_immutable_through_listing_workflow(): void
    {
        $user = User::factory()->create();
        $transaction = $this->makeTransaction(null, $user->id, '5.00', Transaction::TYPE_SIGNUP_BONUS);

        $this->actingAs($user)->getJson(route('transactions.index'));

        $this->expectException(\LogicException::class);
        $transaction->update(['amount' => '999.00']);
    }

    private function makeTransaction(?int $fromUserId, ?int $toUserId, string $amount, string $type): Transaction
    {
        return Transaction::create([
            'transaction_code' => 'TX-'.strtoupper(uniqid()),
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'service_request_id' => null,
            'amount' => $amount,
            'type' => $type,
            'description' => 'Test transaction',
            'created_at' => now(),
        ]);
    }
}
