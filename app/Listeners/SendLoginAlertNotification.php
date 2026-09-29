<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\LoginAlertNotification;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendLoginAlertNotification
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if (! ($event->user instanceof User)) {
            return;
        }

        try {
            $request = request();

            $ipAddress = $request ? $request->ip() : null;
            $userAgent = $request ? $request->userAgent() : null;

            $event->user->notify(new LoginAlertNotification(
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                loginTime: now(),
            ));
        } catch (Throwable $e) {
            Log::warning('Login alert notification failed to dispatch or deliver', [
                'user_id' => $event->user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
