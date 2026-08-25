<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReviewReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Review $review) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'review_id' => $this->review->id,
            'service_request_id' => $this->review->service_request_id,
            'reviewer_id' => $this->review->reviewer_id,
            'rating' => $this->review->rating,
            'comment' => $this->review->comment,
        ];
    }
}
