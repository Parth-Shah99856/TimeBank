<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequestMessageRequest;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Services\ServiceRequestChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceRequestMessageController extends Controller
{
    public function index(
        Request $request,
        ServiceRequest $serviceRequest,
        ServiceRequestChatService $chatService,
    ): View|JsonResponse {
        $user = $request->user();

        if ($user->id !== $serviceRequest->requester_id && $user->id !== $serviceRequest->provider_id) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $afterId = $request->query('after_id');
        $afterId = ($afterId !== null && is_numeric($afterId) && (int) $afterId >= 0) ? (int) $afterId : null;

        // Mark incoming messages as read
        $chatService->markMessagesAsRead($serviceRequest, $user);

        $messages = $chatService->getMessages($serviceRequest, $user, $afterId);

        if ($request->expectsJson()) {
            return response()->json([
                'service_request_id' => $serviceRequest->id,
                'messages' => $messages->map(fn (ServiceRequestMessage $msg) => [
                    'id' => $msg->id,
                    'service_request_id' => $msg->service_request_id,
                    'sender_id' => $msg->sender_id,
                    'sender_name' => $msg->sender?->name ?? 'User',
                    'sender_avatar' => $msg->sender?->avatar_url ?? null,
                    'content' => $msg->content,
                    'created_at' => $msg->created_at?->format('M j, g:i A') ?? '',
                    'created_at_iso' => $msg->created_at?->toISOString(),
                    'is_me' => $msg->sender_id === $user->id,
                    'is_read' => $msg->read_at !== null,
                    'read_at' => $msg->read_at?->toISOString(),
                    'sender' => $msg->sender ? [
                        'id' => $msg->sender->id,
                        'name' => $msg->sender->name,
                        'avatar_url' => $msg->sender->avatar_url,
                    ] : null,
                ]),
                'last_read_id' => $serviceRequest->messages()
                    ->where('sender_id', $user->id)
                    ->whereNotNull('read_at')
                    ->max('id'),
            ]);
        }

        $serviceRequest->load(['service', 'requester', 'provider', 'category']);

        $partner = ($user->id === $serviceRequest->requester_id)
            ? $serviceRequest->provider
            : $serviceRequest->requester;

        return view('service-requests.chat', compact('serviceRequest', 'messages', 'partner'));
    }

    public function store(
        StoreServiceRequestMessageRequest $request,
        ServiceRequest $serviceRequest,
        ServiceRequestChatService $chatService,
    ): JsonResponse|RedirectResponse {
        $message = $chatService->sendMessage(
            $serviceRequest,
            $request->user(),
            (string) $request->input('content'),
        );

        if ($request->expectsJson()) {
            return response()->json($message, 201);
        }

        return redirect()->route('service-requests.chat', $serviceRequest);
    }
}
