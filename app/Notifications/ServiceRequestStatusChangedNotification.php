<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ServiceRequestStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ServiceRequest $serviceRequest,
        private readonly string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'service_request_id' => $this->serviceRequest->id,
            'title' => $this->serviceRequest->title,
            'status' => $this->status,
        ];
    }
}
