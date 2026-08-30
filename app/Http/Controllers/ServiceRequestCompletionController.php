<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestCompletionService;
use App\Services\SkillExchangeOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ServiceRequestCompletionController extends Controller
{
    public function __invoke(
        CompleteServiceRequestRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestCompletionService $completionService,
        SkillExchangeOtpService $otpService,
    ): JsonResponse|RedirectResponse {
        $otpService->verifyAndConsume($serviceRequest, $request->user(), (string) $request->input('otp'));

        $completionService->complete($serviceRequest, $request->user());

        if ($request->expectsJson()) {
            return response()->json($serviceRequest->fresh());
        }

        return redirect()->route('dashboard')->with('status', 'service-request-completed');
    }
}
