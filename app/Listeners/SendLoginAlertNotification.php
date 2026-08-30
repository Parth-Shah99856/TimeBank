<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\LoginAlertNotification;
use Illuminate\Auth\Events\Login;

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

        $request = request();

        $ipAddress = $request ? $request->ip() : null;
        $userAgent = $request ? $request->userAgent() : null;

        $event->user->notify(new LoginAlertNotification(
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            loginTime: now(),
        ));
    }
}
