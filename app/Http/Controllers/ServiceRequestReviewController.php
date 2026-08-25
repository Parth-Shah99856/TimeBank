<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestReviewService;
use Illuminate\Http\RedirectResponse;

class ServiceRequestReviewController extends Controller
{
    public function __invoke(
        StoreReviewRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestReviewService $reviewService,
    ): RedirectResponse {
        $reviewService->createReview($serviceRequest, $request->user(), $request->validated());

        return redirect()->route('dashboard')->with('status', 'review-created');
    }
}
