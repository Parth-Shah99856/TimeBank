<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });

        // Verify password was actually changed in the database
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-secure-password', $user->fresh()->password));

        // Verify old password no longer authenticates
        $this->assertFalse(\Illuminate\Support\Facades\Auth::attempt([
            'email' => $user->email,
            'password' => 'password',
        ]));

        // Verify new password successfully authenticates
        $this->assertTrue(\Illuminate\Support\Facades\Auth::attempt([
            'email' => $user->email,
            'password' => 'new-secure-password',
        ]));
    }

    public function test_nonexistent_email_handled_safely_without_revealing_account_existence(): void
    {
        Notification::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'ghost-node@timebank.local',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status', 'If an active node matches this comm link, temporal recovery instructions have been transmitted.');
        Notification::assertNothingSent();
    }

    public function test_recovery_request_throttles_rapid_subsequent_attempts_with_cooldown(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        // First attempt succeeds
        $this->post('/forgot-password', ['email' => $user->email]);

        // Second immediate attempt triggers throttle
        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHasErrors('email');
        $errorMessage = session('errors')->first('email');
        $this->assertStringContainsString('Temporal cooldown active', $errorMessage);
        $this->assertStringContainsString('seconds', $errorMessage);
    }

    public function test_mail_transport_failure_does_not_cause_http_500_and_cleans_token(): void
    {
        $user = User::factory()->create();

        Notification::shouldReceive('send')
            ->andThrow(new \RuntimeException('SMTP Connection Timeout'));

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Temporal transmission failed', session('errors')->first('email'));

        // Ensure token was deleted so user isn't penalized by cooldown
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'invalid-fabricated-token-12345',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_password_cannot_be_reset_with_mismatched_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'some-token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
