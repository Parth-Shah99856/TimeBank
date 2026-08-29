<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CancelServiceRequestController extends Controller
{
    public function __invoke(
        CancelServiceRequestRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestLifecycleService $lifecycleService,
    ): JsonResponse|RedirectResponse {
        $lifecycleService->cancel($serviceRequest, $request->user());

        if ($request->expectsJson()) {
            return response()->json($serviceRequest->fresh());
        }

        return redirect()->route('dashboard')->with('status', 'service-request-cancelled');
    }
}
