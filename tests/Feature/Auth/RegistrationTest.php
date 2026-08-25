<?php

namespace Tests\Feature\Auth;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_users_receive_the_signup_bonus_with_a_ledger_entry(): void
    {
        $this->post('/register', [
            'name' => 'Bonus User',
            'email' => 'bonus@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'bonus@example.com')->firstOrFail();
        $transactions = Transaction::query()->where('to_user_id', $user->id)->get();
        $transaction = $transactions->sole();

        $this->assertSame('5.00', $user->fresh()->time_balance);
        $this->assertCount(1, $transactions);
        $this->assertSame(Transaction::TYPE_SIGNUP_BONUS, $transaction->type);
        $this->assertSame('5.00', $transaction->amount);
        $this->assertNull($transaction->from_user_id);
        $this->assertNull($transaction->service_request_id);
        $this->assertNotEmpty($transaction->transaction_code);
        $this->assertFalse(isset($transaction->updated_at));
    }
}
