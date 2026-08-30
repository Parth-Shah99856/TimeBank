<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequestMessageRequest;
use App\Models\ServiceRequest;
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

        // Mark incoming messages as read
        $chatService->markMessagesAsRead($serviceRequest, $user);

        $messages = $chatService->getMessages($serviceRequest, $user);

        if ($request->expectsJson()) {
            return response()->json([
                'service_request_id' => $serviceRequest->id,
                'messages' => $messages,
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
