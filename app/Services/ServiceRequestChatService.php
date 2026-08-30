<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Models\User;
use App\Notifications\ChatMessageReceivedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ServiceRequestChatService
{
    /**
     * Send a new message in the service request conversation.
     *
     * @throws AuthorizationException
     */
    public function sendMessage(ServiceRequest $serviceRequest, User $sender, string $content): ServiceRequestMessage
    {
        return DB::transaction(function () use ($serviceRequest, $sender, $content): ServiceRequestMessage {
            if ($sender->id !== $serviceRequest->requester_id && $sender->id !== $serviceRequest->provider_id) {
                throw new AuthorizationException('You are not authorized to send messages in this exchange.');
            }

            $message = $serviceRequest->messages()->create([
                'sender_id' => $sender->id,
                'content' => trim($content),
            ]);

            // Determine recipient and notify
            $recipientId = ($sender->id === $serviceRequest->requester_id)
                ? $serviceRequest->provider_id
                : $serviceRequest->requester_id;

            $recipient = User::query()->find($recipientId);

            if ($recipient) {
                $recipient->notify(new ChatMessageReceivedNotification($serviceRequest, $message, $sender));
            }

            return $message->load('sender');
        });
    }

    /**
     * Mark incoming unread messages as read for a given user.
     */
    public function markMessagesAsRead(ServiceRequest $serviceRequest, User $reader): int
    {
        if ($reader->id !== $serviceRequest->requester_id && $reader->id !== $serviceRequest->provider_id) {
            return 0;
        }

        return $serviceRequest->messages()
            ->where('sender_id', '!=', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Retrieve chronological messages for the conversation.
     *
     * @throws AuthorizationException
     */
    public function getMessages(ServiceRequest $serviceRequest, User $user): Collection
    {
        if ($user->id !== $serviceRequest->requester_id && $user->id !== $serviceRequest->provider_id) {
            throw new AuthorizationException('You are not authorized to view messages for this exchange.');
        }

        return $serviceRequest->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
