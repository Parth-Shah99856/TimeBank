<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequestRequest;
use App\Services\ServiceRequestCreationService;
use Illuminate\Http\RedirectResponse;

class StoreServiceRequestController extends Controller
{
    public function __invoke(
        StoreServiceRequestRequest $request,
        ServiceRequestCreationService $creationService,
    ): RedirectResponse {
        $creationService->create($request->user(), $request->validated());

        return redirect()->route('dashboard')->with('status', 'service-request-created');
    }
}
