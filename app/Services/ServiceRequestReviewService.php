<?php

namespace App\Services;

use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\ReviewReceivedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ServiceRequestReviewService
{
    public function createReview(ServiceRequest $serviceRequest, User $actor, array $attributes): Review
    {
        return DB::transaction(function () use ($serviceRequest, $actor, $attributes): Review {
            $serviceRequest = ServiceRequest::query()
                ->whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($actor->id, [$serviceRequest->requester_id, $serviceRequest->provider_id], true)) {
                throw new AuthorizationException('Only service request participants can leave a review.');
            }

            if ($serviceRequest->status !== 'completed' || $serviceRequest->completed_at === null) {
                throw new RuntimeException('Reviews are only allowed after completed exchanges.');
            }

            if ($serviceRequest->review()->exists()) {
                throw new RuntimeException('A review has already been submitted for this service request.');
            }

            $revieweeId = $actor->id === $serviceRequest->requester_id
                ? $serviceRequest->provider_id
                : $serviceRequest->requester_id;

            $review = Review::query()->create([
                'service_request_id' => $serviceRequest->id,
                'reviewer_id' => $actor->id,
                'reviewee_id' => $revieweeId,
                'rating' => $attributes['rating'],
                'comment' => $attributes['comment'] ?? null,
            ]);

            $review->reviewee->notify(new ReviewReceivedNotification($review));

            return $review;
        });
    }
}
