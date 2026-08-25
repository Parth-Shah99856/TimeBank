<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestLifecycleService;
use Illuminate\Http\RedirectResponse;

class CancelServiceRequestController extends Controller
{
    public function __invoke(
        CancelServiceRequestRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestLifecycleService $lifecycleService,
    ): RedirectResponse {
        $lifecycleService->cancel($serviceRequest, $request->user());

        return redirect()->route('dashboard')->with('status', 'service-request-cancelled');
    }
}
