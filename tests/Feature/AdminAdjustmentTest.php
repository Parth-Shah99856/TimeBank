<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_credit_a_users_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['time_balance' => '5.00']);

        $response = $this->actingAs($admin)->postJson(route('admin.adjustments.store'), [
            'user_id' => $target->id,
            'amount' => '10.00',
            'description' => 'Goodwill credit for reporting a bug',
        ]);

        $response->assertCreated();

        $this->assertSame('15.00', $target->fresh()->time_balance);

        $transaction = Transaction::query()->firstOrFail();
        $this->assertSame(Transaction::TYPE_ADMIN_ADJUSTMENT, $transaction->type);
        $this->assertSame('10.00', $transaction->amount);
        $this->assertNull($transaction->from_user_id);
        $this->assertSame($target->id, $transaction->to_user_id);
        $this->assertNull($transaction->service_request_id);
    }

    public function test_admin_can_debit_a_users_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['time_balance' => '10.00']);

        $response = $this->actingAs($admin)->postJson(route('admin.adjustments.store'), [
            'user_id' => $target->id,
            'amount' => '-4.00',
            'description' => 'Penalty for policy violation',
        ]);

        $response->assertCreated();

        $this->assertSame('6.00', $target->fresh()->time_balance);

        $transaction = Transaction::query()->firstOrFail();
        $this->assertSame(Transaction::TYPE_ADMIN_ADJUSTMENT, $transaction->type);
        $this->assertSame('4.00', $transaction->amount);
        $this->assertSame($target->id, $transaction->from_user_id);
        $this->assertNull($transaction->to_user_id);
    }

    public function test_non_admin_cannot_perform_adjustment(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create(['time_balance' => '5.00']);

        $response = $this->actingAs($user)->postJson(route('admin.adjustments.store'), [
            'user_id' => $target->id,
            'amount' => '10.00',
            'description' => 'Should not be allowed',
        ]);

        $response->assertForbidden();
        $this->assertSame('5.00', $target->fresh()->time_balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_unauthenticated_user_cannot_perform_adjustment(): void
    {
        $target = User::factory()->create(['time_balance' => '5.00']);

        $response = $this->postJson(route('admin.adjustments.store'), [
            'user_id' => $target->id,
            'amount' => '10.00',
            'description' => 'Should not be allowed',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_debit_fails_when_balance_is_insufficient(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['time_balance' => '2.00']);

        $this->withoutExceptionHandling();

        $thrown = false;

        try {
            $this->actingAs($admin)->postJson(route('admin.adjustments.store'), [
                'user_id' => $target->id,
                'amount' => '-5.00',
                'description' => 'Attempting an oversized penalty',
            ]);
        } catch (\RuntimeException $exception) {
            $thrown = true;
            $this->assertSame('Insufficient balance for this adjustment.', $exception->getMessage());
        }

        $this->assertTrue($thrown, 'Expected insufficient balance exception was not thrown.');
        $this->assertSame('2.00', $target->fresh()->time_balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_zero_amount_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['time_balance' => '5.00']);

        $response = $this->actingAs($admin)->postJson(route('admin.adjustments.store'), [
            'user_id' => $target->id,
            'amount' => '0.00',
            'description' => 'Zero adjustment',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('amount');
        $this->assertSame('5.00', $target->fresh()->time_balance);
    }

    public function test_protected_fields_cannot_be_spoofed_through_input(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['time_balance' => '5.00']);
        $impersonated = User::factory()->create();

        $this->actingAs($admin)->postJson(route('admin.adjustments.store'), [
            'user_id' => $target->id,
            'amount' => '10.00',
            'description' => 'Legit adjustment',
            'from_user_id' => $impersonated->id,
            'to_user_id' => $impersonated->id,
            'type' => 'service_exchange',
            'transaction_code' => 'SPOOFED-CODE',
            'service_request_id' => 999,
        ]);

        $transaction = Transaction::query()->firstOrFail();
        $this->assertSame(Transaction::TYPE_ADMIN_ADJUSTMENT, $transaction->type);
        $this->assertSame($target->id, $transaction->to_user_id);
        $this->assertNull($transaction->from_user_id);
        $this->assertNull($transaction->service_request_id);
        $this->assertNotSame('SPOOFED-CODE', $transaction->transaction_code);
    }

    public function test_missing_description_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['time_balance' => '5.00']);

        $response = $this->actingAs($admin)->postJson(route('admin.adjustments.store'), [
            'user_id' => $target->id,
            'amount' => '10.00',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('description');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_nonexistent_user_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson(route('admin.adjustments.store'), [
            'user_id' => 999999,
            'amount' => '10.00',
            'description' => 'Adjustment for missing user',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('user_id');
        $this->assertDatabaseCount('transactions', 0);
    }
}
