<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveServiceRequestDisputeRequest;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestDisputeResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ServiceRequestDisputeResolutionController extends Controller
{
    public function __invoke(
        ResolveServiceRequestDisputeRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestDisputeResolutionService $resolutionService,
    ): JsonResponse|RedirectResponse {
        $resolved = $resolutionService->resolve($serviceRequest, $request->validated('resolution'));

        if ($request->expectsJson()) {
            return response()->json($resolved);
        }

        return redirect()->route('admin.index')->with('status', 'Dispute #SR-'.$serviceRequest->id.' resolved successfully as '.$request->validated('resolution').'.');
    }
}
