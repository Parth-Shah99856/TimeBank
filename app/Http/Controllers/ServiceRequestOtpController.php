<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Services\SkillExchangeOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ServiceRequestOtpController extends Controller
{
    public function send(
        Request $request,
        ServiceRequest $serviceRequest,
        SkillExchangeOtpService $otpService,
    ): JsonResponse|RedirectResponse {
        $user = $request->user();

        if ($user->id !== $serviceRequest->requester_id) {
            abort(403, 'Unauthorized to request OTP for this exchange.');
        }

        if ($serviceRequest->status !== 'in_progress') {
            abort(422, 'OTP can only be requested for in-progress exchanges.');
        }

        $otpService->generateAndSend($serviceRequest, $user);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'otp_sent',
                'message' => 'Verification code sent to your registered email address.',
            ]);
        }

        return back()->with('status', 'otp-sent');
    }
}
