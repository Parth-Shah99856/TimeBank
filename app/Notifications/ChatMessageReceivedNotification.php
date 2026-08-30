<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ChatMessageReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ServiceRequest $serviceRequest,
        public readonly ServiceRequestMessage $message,
        public readonly User $sender,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'chat',
            'title' => 'New message from ' . ($this->sender->name ?? 'User'),
            'message' => Str::limit($this->message->content, 120),
            'service_request_id' => $this->serviceRequest->id,
            'message_id' => $this->message->id,
            'sender_id' => $this->sender->id,
            'action_url' => route('service-requests.chat', $this->serviceRequest),
        ];
    }
}
