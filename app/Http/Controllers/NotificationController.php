<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarkNotificationAsReadRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $notificationService): JsonResponse
    {
        return response()->json($notificationService->listForUser($request->user()));
    }

    public function markAsRead(
        MarkNotificationAsReadRequest $request,
        DatabaseNotification $notification,
        NotificationService $notificationService,
    ): JsonResponse {
        return response()->json($notificationService->markAsRead($notification));
    }

    public function markAllAsRead(Request $request, NotificationService $notificationService): JsonResponse
    {
        $count = $notificationService->markAllAsRead($request->user());

        return response()->json(['marked_read' => $count]);
    }
}
