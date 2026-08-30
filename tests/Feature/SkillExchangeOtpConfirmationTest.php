<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestOtp;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\SkillExchangeOtpNotification;
use App\Services\SkillExchangeOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SkillExchangeOtpConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_is_generated_and_emailed_to_requester_when_requested(): void
    {
        Notification::fake();

        [$serviceRequest, $requester, $provider] = $this->createActiveServiceRequest();

        $response = $this->actingAs($requester)->postJson(route('service-requests.send-otp', $serviceRequest));

        $response->assertOk();
        $response->assertJsonPath('status', 'otp_sent');

        $otpRecord = ServiceRequestOtp::query()->where('service_request_id', $serviceRequest->id)->first();
        $this->assertNotNull($otpRecord);
        $this->assertSame($requester->id, $otpRecord->user_id);
        $this->assertFalse($otpRecord->isUsed());
        $this->assertFalse($otpRecord->isExpired());
        $this->assertSame(0, $otpRecord->attempts);

        Notification::assertSentTo(
            $requester,
            SkillExchangeOtpNotification::class,
            function (SkillExchangeOtpNotification $notification, array $channels) use ($serviceRequest): bool {
                $this->assertContains('mail', $channels);
                $this->assertSame($serviceRequest->id, $notification->serviceRequest->id);
                $this->assertSame(6, strlen($notification->otp));
                $this->assertTrue(ctype_digit($notification->otp));

                return true;
            }
        );
        Notification::assertNotSentTo($provider, SkillExchangeOtpNotification::class);
    }

    public function test_valid_otp_successfully_completes_exchange_and_transfers_credits(): void
    {
        [$serviceRequest, $requester, $provider] = $this->createActiveServiceRequest('10.00', '2.00', '4.00');

        $otp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        $response = $this->actingAs($requester)->post(route('service-requests.complete', $serviceRequest), [
            'otp' => $otp,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $requester->refresh();
        $provider->refresh();
        $serviceRequest->refresh();

        $this->assertSame('6.00', $requester->time_balance);
        $this->assertSame('6.00', $provider->time_balance);
        $this->assertSame('completed', $serviceRequest->status);
        $this->assertNotNull($serviceRequest->completed_at);

        $otpRecord = ServiceRequestOtp::query()->where('service_request_id', $serviceRequest->id)->first();
        $this->assertNotNull($otpRecord);
        $this->assertTrue($otpRecord->isUsed());
    }

    public function test_incorrect_otp_is_rejected_and_does_not_complete_exchange(): void
    {
        [$serviceRequest, $requester, $provider] = $this->createActiveServiceRequest('10.00', '2.00', '4.00');

        app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        $response = $this->actingAs($requester)->postJson(route('service-requests.complete', $serviceRequest), [
            'otp' => '000000',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['otp']);

        $serviceRequest->refresh();
        $requester->refresh();
        $provider->refresh();

        $this->assertSame('in_progress', $serviceRequest->status);
        $this->assertSame('10.00', $requester->time_balance);
        $this->assertSame('2.00', $provider->time_balance);

        $otpRecord = ServiceRequestOtp::query()->where('service_request_id', $serviceRequest->id)->first();
        $this->assertSame(1, $otpRecord->attempts);
        $this->assertFalse($otpRecord->isUsed());
    }

    public function test_expired_otp_is_rejected(): void
    {
        [$serviceRequest, $requester] = $this->createActiveServiceRequest();

        $otp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        // Travel 20 minutes into the future (past 15m expiration)
        $this->travel(20)->minutes();

        $response = $this->actingAs($requester)->postJson(route('service-requests.complete', $serviceRequest), [
            'otp' => $otp,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['otp']);
        $this->assertSame('in_progress', $serviceRequest->fresh()->status);
    }

    public function test_used_otp_cannot_be_reused(): void
    {
        [$serviceRequest, $requester] = $this->createActiveServiceRequest('10.00', '2.00', '4.00');

        $otp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        // First use: success
        $this->actingAs($requester)->post(route('service-requests.complete', $serviceRequest), [
            'otp' => $otp,
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame('completed', $serviceRequest->fresh()->status);

        // Second use: must fail
        $response = $this->actingAs($requester)->postJson(route('service-requests.complete', $serviceRequest), [
            'otp' => $otp,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['otp']);
    }

    public function test_unauthorized_user_cannot_request_otp(): void
    {
        [$serviceRequest] = $this->createActiveServiceRequest();
        $outsider = User::factory()->create();

        $response = $this->actingAs($outsider)->postJson(route('service-requests.send-otp', $serviceRequest));
        $response->assertForbidden();
    }

    public function test_unauthorized_user_cannot_verify_another_users_otp(): void
    {
        [$serviceRequest, $requester] = $this->createActiveServiceRequest();
        $outsider = User::factory()->create();

        $otp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        $response = $this->actingAs($outsider)->postJson(route('service-requests.complete', $serviceRequest), [
            'otp' => $otp,
        ]);

        $response->assertForbidden();
        $this->assertSame('in_progress', $serviceRequest->fresh()->status);
    }

    public function test_otp_cannot_be_brute_forced_beyond_max_attempts(): void
    {
        [$serviceRequest, $requester] = $this->createActiveServiceRequest();

        $validOtp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        // Perform 5 wrong attempts
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($requester)->postJson(route('service-requests.complete', $serviceRequest), [
                'otp' => '999999',
            ])->assertStatus(422);
        }

        $otpRecord = ServiceRequestOtp::query()->where('service_request_id', $serviceRequest->id)->first();
        $this->assertSame(5, $otpRecord->attempts);

        // Even with the correct OTP, attempt is locked out
        $lockedResponse = $this->actingAs($requester)->postJson(route('service-requests.complete', $serviceRequest), [
            'otp' => $validOtp,
        ]);

        $lockedResponse->assertStatus(422);
        $this->assertSame('in_progress', $serviceRequest->fresh()->status);
    }

    public function test_resending_otp_invalidates_previous_otp(): void
    {
        [$serviceRequest, $requester] = $this->createActiveServiceRequest();

        $firstOtp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);
        $secondOtp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        $this->assertNotSame($firstOtp, $secondOtp);

        // First OTP should now be invalidated
        $response = $this->actingAs($requester)->postJson(route('service-requests.complete', $serviceRequest), [
            'otp' => $firstOtp,
        ]);

        $response->assertStatus(422);

        // Second OTP should succeed
        $validResponse = $this->actingAs($requester)->post(route('service-requests.complete', $serviceRequest), [
            'otp' => $secondOtp,
        ]);

        $validResponse->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame('completed', $serviceRequest->fresh()->status);
    }

    public function test_otp_cannot_be_generated_for_non_in_progress_service_requests(): void
    {
        [$serviceRequest, $requester] = $this->createActiveServiceRequest();
        $serviceRequest->update(['status' => 'pending']);

        $response = $this->actingAs($requester)->postJson(route('service-requests.send-otp', $serviceRequest));
        $response->assertStatus(422);
    }

    public function test_otp_notification_content_is_safe_and_informative(): void
    {
        [$serviceRequest, $requester, $provider] = $this->createActiveServiceRequest('8.00', '2.00', '3.50');

        $notification = new SkillExchangeOtpNotification(
            serviceRequest: $serviceRequest,
            otp: '482910',
            expiresAt: now()->addMinutes(15)
        );

        $mail = $notification->toMail($requester);

        $this->assertSame('TimeBank Skill Exchange Authorization Code: 482910', $mail->subject);
        $this->assertStringContainsString($requester->name, $mail->greeting);

        $rendered = implode("\n", array_merge($mail->introLines, $mail->outroLines));
        $this->assertStringContainsString('482910', $rendered);
        $this->assertStringContainsString('3.50 TC', $rendered);
        $this->assertStringContainsString($provider->name, $rendered);
        $this->assertStringContainsString('15 minutes', $rendered);
    }

    private function createActiveServiceRequest(string $reqBalance = '8.00', string $provBalance = '2.00', string $credits = '4.00'): array
    {
        $requester = User::factory()->create(['name' => 'Alice Requester', 'time_balance' => $reqBalance]);
        $provider = User::factory()->create(['name' => 'Bob Provider', 'time_balance' => $provBalance]);

        $category = Category::query()->create([
            'name' => 'Architecture',
            'slug' => 'architecture-' . uniqid(),
            'is_active' => true,
        ]);

        $service = Service::query()->create([
            'user_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Quantum Architecture Review',
            'description' => 'Reviewing system blueprints',
            'hourly_rate' => '2.00',
            'tags' => ['quantum'],
            'is_active' => true,
        ]);

        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => $service->id,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Quantum Blueprint Consultation',
            'project_scope' => 'Deep review of quantum node topology',
            'estimated_hours' => '2.00',
            'total_credits' => $credits,
            'desired_deadline' => now()->addDays(5)->toDateString(),
            'status' => 'in_progress',
        ]);

        return [$serviceRequest, $requester, $provider];
    }
}
