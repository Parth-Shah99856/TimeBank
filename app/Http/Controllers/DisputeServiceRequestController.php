<?php

namespace App\Http\Controllers;

use App\Http\Requests\DisputeServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestLifecycleService;
use Illuminate\Http\RedirectResponse;

class DisputeServiceRequestController extends Controller
{
    public function __invoke(
        DisputeServiceRequestRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestLifecycleService $lifecycleService,
    ): RedirectResponse {
        $lifecycleService->dispute($serviceRequest, $request->user());

        return redirect()->route('dashboard')->with('status', 'service-request-disputed');
    }
}
