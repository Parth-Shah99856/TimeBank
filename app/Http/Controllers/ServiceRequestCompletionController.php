<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestCompletionService;
use Illuminate\Http\RedirectResponse;

class ServiceRequestCompletionController extends Controller
{
    public function __invoke(
        CompleteServiceRequestRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestCompletionService $completionService,
    ): RedirectResponse {
        $completionService->complete($serviceRequest, $request->user());

        return redirect()->route('dashboard')->with('status', 'service-request-completed');
    }
}
