<?php

namespace App\Http\Controllers;

use App\Http\Requests\DisputeServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DisputeServiceRequestController extends Controller
{
    public function __invoke(
        DisputeServiceRequestRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestLifecycleService $lifecycleService,
    ): JsonResponse|RedirectResponse {
        $lifecycleService->dispute($serviceRequest, $request->user());

        if ($request->expectsJson()) {
            return response()->json($serviceRequest->fresh());
        }

        return redirect()->route('dashboard')->with('status', 'service-request-disputed');
    }
}
