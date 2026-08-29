<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarkNotificationAsReadRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $notificationService): JsonResponse|View
    {
        if ($request->expectsJson()) {
            return response()->json($notificationService->listForUser($request->user()));
        }

        return view('notifications.index');
    }

    public function markAsRead(
        MarkNotificationAsReadRequest $request,
        DatabaseNotification $notification,
        NotificationService $notificationService,
    ): JsonResponse|RedirectResponse {
        $result = $notificationService->markAsRead($notification);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->route('notifications.index')->with('status', 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request, NotificationService $notificationService): JsonResponse|RedirectResponse
    {
        $count = $notificationService->markAllAsRead($request->user());

        if ($request->expectsJson()) {
            return response()->json(['marked_read' => $count]);
        }

        return redirect()->route('notifications.index')->with('status', 'All notifications marked as read.');
    }
}
