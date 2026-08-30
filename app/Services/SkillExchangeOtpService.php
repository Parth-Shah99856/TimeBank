<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestOtp;
use App\Models\User;
use App\Notifications\SkillExchangeOtpNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SkillExchangeOtpService
{
    public const OTP_EXPIRY_MINUTES = 15;
    public const MAX_VERIFICATION_ATTEMPTS = 5;

    /**
     * Generate a new secure OTP, persist its hash, and email the requester.
     */
    public function generateAndSend(ServiceRequest $serviceRequest, User $actor): string
    {
        return DB::transaction(function () use ($serviceRequest, $actor): string {
            $serviceRequest = ServiceRequest::query()
                ->whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($actor->id !== $serviceRequest->requester_id) {
                throw new AuthorizationException('Only the exchange requester can request a completion OTP.');
            }

            if ($serviceRequest->status !== 'in_progress') {
                throw new RuntimeException('OTP can only be generated for in-progress skill exchanges.');
            }

            // Invalidate any existing unused OTPs for this service request
            ServiceRequestOtp::query()
                ->where('service_request_id', $serviceRequest->id)
                ->whereNull('used_at')
                ->delete();

            // Generate cryptographically secure 6-digit numeric OTP
            $plainOtp = sprintf('%06d', random_int(0, 999999));
            $expiresAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

            ServiceRequestOtp::create([
                'service_request_id' => $serviceRequest->id,
                'user_id' => $actor->id,
                'otp_hash' => Hash::make($plainOtp),
                'attempts' => 0,
                'max_attempts' => self::MAX_VERIFICATION_ATTEMPTS,
                'expires_at' => $expiresAt,
            ]);

            // Notify requester via email
            $serviceRequest->requester->notify(new SkillExchangeOtpNotification(
                serviceRequest: $serviceRequest,
                otp: $plainOtp,
                expiresAt: $expiresAt
            ));

            return $plainOtp;
        });
    }

    /**
     * Verify and consume the OTP for the service request.
     *
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function verifyAndConsume(ServiceRequest $serviceRequest, User $actor, string $otp): bool
    {
        if ($actor->id !== $serviceRequest->requester_id) {
            throw new AuthorizationException('Only the exchange requester is authorized to verify the completion OTP.');
        }

        $otpRecord = ServiceRequestOtp::query()
            ->where('service_request_id', $serviceRequest->id)
            ->where('user_id', $actor->id)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $otpRecord) {
            throw ValidationException::withMessages([
                'otp' => 'No active OTP found. Please request a new verification code.',
            ]);
        }

        if ($otpRecord->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => 'The verification OTP has expired. Please request a new code.',
            ]);
        }

        if ($otpRecord->hasExceededAttempts()) {
            throw ValidationException::withMessages([
                'otp' => 'Maximum verification attempts exceeded. Please request a new code.',
            ]);
        }

        $otpRecord->increment('attempts');

        if (! Hash::check($otp, $otpRecord->otp_hash)) {
            $remaining = max(0, $otpRecord->max_attempts - $otpRecord->attempts);
            throw ValidationException::withMessages([
                'otp' => "The entered OTP code is incorrect. ({$remaining} attempts remaining)",
            ]);
        }

        $otpRecord->forceFill([
            'used_at' => now(),
        ])->save();

        return true;
    }
}
