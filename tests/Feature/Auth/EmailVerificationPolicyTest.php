<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationPolicyTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 19 Requirements 1 & 2: Legacy Users Policy (Pre-existing production users)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_existing_legacy_user_with_null_email_verified_at_has_verified_email_true(): void
    {
        $rollout = Carbon::parse(config('auth.verification_rollout_at'));

        $legacyUser = User::factory()->unverified()->create([
            'email' => 'legacy.null@example.com',
            'created_at' => $rollout->copy()->subDay(),
        ]);

        $this->assertNull($legacyUser->email_verified_at);
        $this->assertTrue($legacyUser->isLegacyUser());
        $this->assertFalse($legacyUser->isDemoAccount());
        $this->assertTrue($legacyUser->hasVerifiedEmail());
    }

    public function test_existing_legacy_user_with_email_verified_at_populated_has_verified_email_true(): void
    {
        $rollout = Carbon::parse(config('auth.verification_rollout_at'));

        $legacyUser = User::factory()->create([
            'email' => 'legacy.verified@example.com',
            'email_verified_at' => $rollout->copy()->subDays(2),
            'created_at' => $rollout->copy()->subDays(3),
        ]);

        $this->assertNotNull($legacyUser->email_verified_at);
        $this->assertTrue($legacyUser->isLegacyUser());
        $this->assertTrue($legacyUser->hasVerifiedEmail());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 19 Requirements 3 & 4: New Normal Users
    // ─────────────────────────────────────────────────────────────────────────

    public function test_new_normal_user_with_null_email_verified_at_has_verified_email_false(): void
    {
        $rollout = Carbon::parse(config('auth.verification_rollout_at'));

        $newUser = User::factory()->unverified()->create([
            'email' => 'new.unverified@example.com',
            'created_at' => $rollout->copy()->addDay(),
        ]);

        $this->assertNull($newUser->email_verified_at);
        $this->assertFalse($newUser->isLegacyUser());
        $this->assertFalse($newUser->isDemoAccount());
        $this->assertFalse($newUser->hasVerifiedEmail());
    }

    public function test_new_normal_user_with_email_verified_at_populated_has_verified_email_true(): void
    {
        $rollout = Carbon::parse(config('auth.verification_rollout_at'));

        $newUser = User::factory()->create([
            'email' => 'new.verified@example.com',
            'email_verified_at' => $rollout->copy()->addDays(2),
            'created_at' => $rollout->copy()->addDay(),
        ]);

        $this->assertNotNull($newUser->email_verified_at);
        $this->assertFalse($newUser->isLegacyUser());
        $this->assertTrue($newUser->hasVerifiedEmail());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 19 Requirement 5: Official Demo Accounts Whitelist
    // ─────────────────────────────────────────────────────────────────────────

    public function test_official_demo_account_can_operate_without_email_verification(): void
    {
        $demoUser = User::factory()->unverified()->create([
            'email' => 'elena@timebank.local',
        ]);

        $this->assertTrue($demoUser->isDemoAccount());
        $this->assertTrue($demoUser->hasVerifiedEmail());

        // Directly accesses verified dashboard without redirect
        $response = $this->actingAs($demoUser)->get('/dashboard');
        $response->assertOk();
    }

    public function test_all_official_seeded_demo_accounts_are_recognized(): void
    {
        $expectedDemos = [
            'admin@timebank.local',
            'elena@timebank.local',
            'marcus@timebank.local',
            'sarah@timebank.local',
            'alex@timebank.local',
            'maya@timebank.local',
            'daniel@timebank.local',
            'sophia@timebank.local',
        ];

        foreach ($expectedDemos as $demoEmail) {
            $user = User::factory()->unverified()->create(['email' => $demoEmail]);
            $this->assertTrue(
                $user->isDemoAccount(),
                "Failed asserting that {$demoEmail} is recognized as an approved demo account."
            );
            $this->assertTrue(
                $user->hasVerifiedEmail(),
                "Failed asserting that {$demoEmail} hasVerifiedEmail() returns true."
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 19 Requirements 6 & 7: No Broad Pattern Bypasses
    // ─────────────────────────────────────────────────────────────────────────

    public function test_arbitrary_timebank_local_address_follows_normal_verification_rules(): void
    {
        $rollout = Carbon::parse(config('auth.verification_rollout_at'));

        $user = User::factory()->unverified()->create([
            'email' => 'intruder@timebank.local',
            'created_at' => $rollout->copy()->addDay(),
        ]);

        $this->assertFalse($user->isDemoAccount());
        $this->assertFalse($user->isLegacyUser());
        $this->assertFalse($user->hasVerifiedEmail());

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_arbitrary_email_containing_demo_follows_normal_verification_rules(): void
    {
        $rollout = Carbon::parse(config('auth.verification_rollout_at'));

        $user = User::factory()->unverified()->create([
            'email' => 'demo-user-random@gmail.com',
            'created_at' => $rollout->copy()->addDay(),
        ]);

        $this->assertFalse($user->isDemoAccount());
        $this->assertFalse($user->isLegacyUser());
        $this->assertFalse($user->hasVerifiedEmail());

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('verification.notice'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 19 Requirements 8 & 9: Newly Registered Normal User Flow
    // ─────────────────────────────────────────────────────────────────────────

    public function test_newly_registered_normal_user_cannot_access_verified_route_before_verification(): void
    {
        $this->post('/register', [
            'name' => 'Gate Normal User',
            'email' => 'gate.normal@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'gate.normal@example.com')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_newly_registered_normal_user_can_access_verified_route_after_valid_verification(): void
    {
        $this->post('/register', [
            'name' => 'Verified Path User',
            'email' => 'verified.path@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'verified.path@example.com')->firstOrFail();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $verifyResponse = $this->actingAs($user)->get($verificationUrl);
        $verifyResponse->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $dashboardResponse = $this->actingAs($user->fresh())->get('/dashboard');
        $dashboardResponse->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 19 Requirement 10: Legacy User Access Without Verification Force
    // ─────────────────────────────────────────────────────────────────────────

    public function test_legacy_user_can_access_verified_route_without_being_forced_through_verification(): void
    {
        $rollout = Carbon::parse(config('auth.verification_rollout_at'));

        // Legacy user with NULL email_verified_at in database
        $legacyUser = User::factory()->unverified()->create([
            'email' => 'legacy.accessible@example.com',
            'created_at' => $rollout->copy()->subDay(),
        ]);

        $this->assertNull($legacyUser->email_verified_at);
        $this->assertTrue($legacyUser->isLegacyUser());
        $this->assertTrue($legacyUser->hasVerifiedEmail());

        // Accesses dashboard directly with no redirect to verification.notice
        $response = $this->actingAs($legacyUser)->get('/dashboard');
        $response->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 19 Requirement 11: Invalid & Expired Link Rejection
    // ─────────────────────────────────────────────────────────────────────────

    public function test_invalid_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'tampered@example.com',
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('tampered-wrong-email@example.com')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_expired_or_invalid_signature_is_rejected(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'expired@example.com',
        ]);

        $expiredUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(10),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($expiredUrl);
        $response->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 19 Requirement 12 & 13: Regression Protections (Auth, Logout, Resets)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_existing_login_still_works(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_remember_me_still_works(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertCookie(\Illuminate\Support\Facades\Auth::getRecallerName());
    }

    public function test_existing_logout_still_works(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_existing_forgot_password_request_still_works(): void
    {
        $user = User::factory()->create([
            'email' => 'forgot-check@example.com',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'forgot-check@example.com',
        ]);

        $response->assertSessionHas('status');
    }

    public function test_existing_password_reset_still_works(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-check@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'reset-check@example.com',
            'password' => 'new-secret-password-123',
            'password_confirmation' => 'new-secret-password-123',
        ]);

        $response->assertSessionHas('status');
        $this->assertTrue(auth()->attempt([
            'email' => 'reset-check@example.com',
            'password' => 'new-secret-password-123',
        ]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 19 Requirement 14: Admin Authentication Intact
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_authentication_and_access_remains_intact(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin.controller@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index'));
        $response->assertOk();
        $response->assertSee('Platform Control');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 19 Requirements 15 & 16: Safe Migration & No Data Modification
    // ─────────────────────────────────────────────────────────────────────────

    public function test_no_production_data_is_modified_and_environment_is_safe(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertNotSame('parth99856.helioho.st', config('database.connections.mysql.host'));
    }

    public function test_database_migration_does_not_modify_existing_user_rows(): void
    {
        $userBefore = User::factory()->unverified()->create([
            'name' => 'Untouched Preexisting User',
            'email' => 'untouched@example.com',
        ]);

        // Re-run pending migrations
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $userAfter = User::where('id', $userBefore->id)->firstOrFail();
        $this->assertSame($userBefore->name, $userAfter->name);
        $this->assertSame($userBefore->email, $userAfter->email);
        $this->assertNull($userAfter->email_verified_at, 'Migration must NOT populate email_verified_at on existing rows');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Critical Guard: Prove newly created user is NOT accidentally classified as verified
    // ─────────────────────────────────────────────────────────────────────────

    public function test_legacy_compatibility_does_not_accidentally_classify_newly_created_user_as_verified(): void
    {
        $this->post('/register', [
            'name' => 'Contemporary Newcomer',
            'email' => 'contemporary.newcomer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'contemporary.newcomer@example.com')->firstOrFail();

        // Must NOT be classified as legacy
        $this->assertFalse($user->isLegacyUser(), 'Newly created user must not be classified as a legacy user');
        // Must NOT be verified
        $this->assertFalse($user->hasVerifiedEmail(), 'Newly created user must not be classified as verified');
        $this->assertNull($user->email_verified_at);

        // Cannot access protected route
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_resend_verification_notification_works(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'resend@example.com',
        ]);

        $response = $this->actingAs($user)->post(route('verification.send'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
