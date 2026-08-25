<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    public function listForUser(User $user): Collection
    {
        return $user->notifications()->orderByDesc('created_at')->get();
    }

    public function markAsRead(DatabaseNotification $notification): DatabaseNotification
    {
        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return $notification->fresh();
    }

    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
