<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestLifecycleService;
use Illuminate\Http\RedirectResponse;

class AcceptServiceRequestController extends Controller
{
    public function __invoke(
        AcceptServiceRequestRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestLifecycleService $lifecycleService,
    ): RedirectResponse {
        $lifecycleService->accept($serviceRequest, $request->user());

        return redirect()->route('dashboard')->with('status', 'service-request-accepted');
    }
}
