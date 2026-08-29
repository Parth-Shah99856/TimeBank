<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ServiceRequestReviewController extends Controller
{
    public function __invoke(
        StoreReviewRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestReviewService $reviewService,
    ): JsonResponse|RedirectResponse {
        $review = $reviewService->createReview($serviceRequest, $request->user(), $request->validated());

        if ($request->expectsJson()) {
            return response()->json($review->fresh());
        }

        return redirect()->route('dashboard')->with('status', 'review-created');
    }
}
