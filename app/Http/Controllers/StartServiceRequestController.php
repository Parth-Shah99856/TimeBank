<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class StartServiceRequestController extends Controller
{
    public function __invoke(
        StartServiceRequestRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestLifecycleService $lifecycleService,
    ): JsonResponse|RedirectResponse {
        $lifecycleService->start($serviceRequest, $request->user());

        if ($request->expectsJson()) {
            return response()->json($serviceRequest->fresh());
        }

        return redirect()->route('dashboard')->with('status', 'service-request-started');
    }
}
