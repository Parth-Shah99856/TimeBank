<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\LoginAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_triggers_login_alert_notification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'login-test@timebank.local',
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => 'login-test@timebank.local',
            'password' => 'password123',
        ], [
            'User-Agent' => 'TimeBank-Test-Agent/1.0',
            'REMOTE_ADDR' => '192.168.1.100',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));

        Notification::assertSentTo(
            $user,
            LoginAlertNotification::class,
            function (LoginAlertNotification $notification, array $channels) use ($user): bool {
                $this->assertContains('mail', $channels);

                $mail = $notification->toMail($user);
                $this->assertSame('Security Alert: Successful Login to TimeBank', $mail->subject);
                $this->assertStringContainsString($user->name, $mail->greeting);

                $mailData = $mail->toArray();
                $renderedLines = implode(' ', $mailData['introLines'] ?? []);

                $this->assertStringContainsString('logged into successfully', $renderedLines);
                $this->assertStringNotContainsString('password', strtolower($renderedLines));

                return true;
            }
        );
    }

    public function test_failed_login_does_not_trigger_login_alert_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'secure-user@timebank.local',
            'password' => 'correct-password',
        ]);

        $response = $this->post('/login', [
            'email' => 'secure-user@timebank.local',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_correct_user_receives_login_notification(): void
    {
        Notification::fake();

        $user1 = User::factory()->create(['email' => 'user1@timebank.local', 'password' => 'secret123']);
        $user2 = User::factory()->create(['email' => 'user2@timebank.local', 'password' => 'secret123']);

        $this->post('/login', [
            'email' => 'user1@timebank.local',
            'password' => 'secret123',
        ]);

        Notification::assertSentTo($user1, LoginAlertNotification::class);
        Notification::assertNotSentTo($user2, LoginAlertNotification::class);
    }

    public function test_notification_mail_content_renders_context_safely(): void
    {
        $user = User::factory()->create(['name' => 'Dr. Elena Rostova']);

        $notification = new LoginAlertNotification(
            ipAddress: '10.0.0.42',
            userAgent: 'Mozilla/5.0 (TimeBank Chronal Terminal)',
            loginTime: now()
        );

        $mail = $notification->toMail($user);

        $this->assertSame('Security Alert: Successful Login to TimeBank', $mail->subject);
        $this->assertSame('Hello Dr. Elena Rostova,', $mail->greeting);

        $lines = implode("\n", array_merge($mail->introLines, $mail->outroLines));

        $this->assertStringContainsString('10.0.0.42', $lines);
        $this->assertStringContainsString('Mozilla/5.0 (TimeBank Chronal Terminal)', $lines);
        $this->assertStringContainsString('Access TimeBank Dashboard', $mail->actionText);
        $this->assertStringContainsString('/dashboard', $mail->actionUrl);
    }

    public function test_logout_does_not_trigger_login_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        Notification::assertNothingSent();
    }
}
