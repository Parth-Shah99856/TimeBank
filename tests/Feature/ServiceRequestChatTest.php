<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Models\User;
use App\Notifications\ChatMessageReceivedNotification;
use App\Services\ServiceRequestChatService;
use App\Services\SkillExchangeOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ServiceRequestChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_can_view_chat_and_send_messages(): void
    {
        Notification::fake();

        [$serviceRequest, $requester, $provider] = $this->createServiceRequest();

        // Requester accesses chat view
        $viewResponse = $this->actingAs($requester)->get(route('service-requests.chat', $serviceRequest));
        $viewResponse->assertOk();
        $viewResponse->assertSee($provider->name);
        $viewResponse->assertSee($serviceRequest->title);

        // Requester sends a message
        $sendResponse = $this->actingAs($requester)->postJson(route('service-requests.messages.store', $serviceRequest), [
            'content' => 'Hello Bob! Looking forward to reviewing the quantum topology blueprint.',
        ]);

        $sendResponse->assertCreated();
        $sendResponse->assertJsonPath('content', 'Hello Bob! Looking forward to reviewing the quantum topology blueprint.');

        $this->assertDatabaseHas('service_request_messages', [
            'service_request_id' => $serviceRequest->id,
            'sender_id' => $requester->id,
            'content' => 'Hello Bob! Looking forward to reviewing the quantum topology blueprint.',
        ]);

        // Provider should receive a notification
        Notification::assertSentTo(
            $provider,
            ChatMessageReceivedNotification::class,
            function (ChatMessageReceivedNotification $notification) use ($serviceRequest, $requester): bool {
                $data = $notification->toArray($requester);
                $this->assertSame($serviceRequest->id, $data['service_request_id']);
                $this->assertSame($requester->id, $data['sender_id']);
                $this->assertStringContainsString('quantum topology', $data['message']);

                return true;
            }
        );
        Notification::assertNotSentTo($requester, ChatMessageReceivedNotification::class);
    }

    public function test_provider_can_view_chat_and_send_messages(): void
    {
        Notification::fake();

        [$serviceRequest, $requester, $provider] = $this->createServiceRequest();

        // Provider accesses chat view
        $viewResponse = $this->actingAs($provider)->get(route('service-requests.chat', $serviceRequest));
        $viewResponse->assertOk();
        $viewResponse->assertSee($requester->name);

        // Provider replies
        $sendResponse = $this->actingAs($provider)->postJson(route('service-requests.messages.store', $serviceRequest), [
            'content' => 'Hi Alice! Initial draft has been uploaded to the shared repository.',
        ]);

        $sendResponse->assertCreated();
        $this->assertDatabaseHas('service_request_messages', [
            'service_request_id' => $serviceRequest->id,
            'sender_id' => $provider->id,
            'content' => 'Hi Alice! Initial draft has been uploaded to the shared repository.',
        ]);

        Notification::assertSentTo($requester, ChatMessageReceivedNotification::class);
        Notification::assertNotSentTo($provider, ChatMessageReceivedNotification::class);
    }

    public function test_both_users_see_conversation_history_in_chronological_order(): void
    {
        [$serviceRequest, $requester, $provider] = $this->createServiceRequest();

        $chatService = app(ServiceRequestChatService::class);

        $msg1 = $chatService->sendMessage($serviceRequest, $requester, 'First message from requester');
        $msg2 = $chatService->sendMessage($serviceRequest, $provider, 'Second message from provider');
        $msg3 = $chatService->sendMessage($serviceRequest, $requester, 'Third message from requester');

        // Fetch messages via API for requester
        $requesterRes = $this->actingAs($requester)->getJson(route('service-requests.chat', $serviceRequest));
        $requesterRes->assertOk();

        $returnedMessages = $requesterRes->json('messages');
        $this->assertCount(3, $returnedMessages);
        $this->assertSame($msg1->id, $returnedMessages[0]['id']);
        $this->assertSame($msg2->id, $returnedMessages[1]['id']);
        $this->assertSame($msg3->id, $returnedMessages[2]['id']);

        // Fetch messages via API for provider
        $providerRes = $this->actingAs($provider)->getJson(route('service-requests.chat', $serviceRequest));
        $providerRes->assertOk();
        $this->assertCount(3, $providerRes->json('messages'));
    }

    public function test_unauthorized_user_cannot_view_or_send_messages(): void
    {
        [$serviceRequest, $requester, $provider] = $this->createServiceRequest();
        $outsider = User::factory()->create(['name' => 'Eve Outsider']);

        // Attempt viewing chat
        $viewResponse = $this->actingAs($outsider)->get(route('service-requests.chat', $serviceRequest));
        $viewResponse->assertForbidden();

        // Attempt sending message
        $sendResponse = $this->actingAs($outsider)->postJson(route('service-requests.messages.store', $serviceRequest), [
            'content' => 'Malicious injection attempt',
        ]);
        $sendResponse->assertForbidden();

        $this->assertDatabaseMissing('service_request_messages', [
            'service_request_id' => $serviceRequest->id,
            'content' => 'Malicious injection attempt',
        ]);
    }

    public function test_empty_and_whitespace_messages_are_rejected(): void
    {
        [$serviceRequest, $requester] = $this->createServiceRequest();

        $responseEmpty = $this->actingAs($requester)->postJson(route('service-requests.messages.store', $serviceRequest), [
            'content' => '',
        ]);
        $responseEmpty->assertStatus(422);
        $responseEmpty->assertJsonValidationErrors(['content']);

        $responseWhitespace = $this->actingAs($requester)->postJson(route('service-requests.messages.store', $serviceRequest), [
            'content' => '     ',
        ]);
        $responseWhitespace->assertStatus(422);
        $responseWhitespace->assertJsonValidationErrors(['content']);
    }

    public function test_oversized_messages_are_rejected(): void
    {
        [$serviceRequest, $requester] = $this->createServiceRequest();

        $oversizedContent = str_repeat('A', 2001);

        $response = $this->actingAs($requester)->postJson(route('service-requests.messages.store', $serviceRequest), [
            'content' => $oversizedContent,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_messages_safely_escape_html_and_prevent_xss(): void
    {
        [$serviceRequest, $requester, $provider] = $this->createServiceRequest();

        $xssPayload = '<script>alert("xss")</script><img src="x" onerror="alert(1)">';

        $this->actingAs($requester)->postJson(route('service-requests.messages.store', $serviceRequest), [
            'content' => $xssPayload,
        ])->assertCreated();

        $viewResponse = $this->actingAs($provider)->get(route('service-requests.chat', $serviceRequest));
        $viewResponse->assertOk();

        // Verify JSON API delivers exact text without execution
        $jsonResponse = $this->actingAs($provider)->getJson(route('service-requests.chat', $serviceRequest));
        $jsonResponse->assertOk();
        $this->assertSame($xssPayload, $jsonResponse->json('messages.0.content'));
    }

    public function test_opening_chat_marks_incoming_messages_as_read(): void
    {
        [$serviceRequest, $requester, $provider] = $this->createServiceRequest();

        $chatService = app(ServiceRequestChatService::class);
        $msg = $chatService->sendMessage($serviceRequest, $requester, 'Unread message for provider');

        $this->assertNull($msg->read_at);
        $this->assertSame(1, $serviceRequest->unreadMessagesCountFor($provider));
        $this->assertSame(0, $serviceRequest->unreadMessagesCountFor($requester));

        // Provider visits chat
        $this->actingAs($provider)->get(route('service-requests.chat', $serviceRequest));

        $msg->refresh();
        $this->assertNotNull($msg->read_at);
        $this->assertSame(0, $serviceRequest->unreadMessagesCountFor($provider));
    }

    public function test_chat_does_not_break_service_request_otp_completion_lifecycle(): void
    {
        [$serviceRequest, $requester, $provider] = $this->createServiceRequest('10.00', '2.00', '4.00');

        $chatService = app(ServiceRequestChatService::class);
        $chatService->sendMessage($serviceRequest, $requester, 'Work is complete, sending credits now.');

        $otp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        $response = $this->actingAs($requester)->post(route('service-requests.complete', $serviceRequest), [
            'otp' => $otp,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertSame('completed', $serviceRequest->fresh()->status);
        $this->assertSame('6.00', $requester->fresh()->time_balance);
        $this->assertSame('6.00', $provider->fresh()->time_balance);
    }

    public function test_incremental_retrieval_returns_only_messages_after_specified_id(): void
    {
        [$serviceRequest, $requester, $provider] = $this->createServiceRequest();
        $chatService = app(ServiceRequestChatService::class);

        $msg1 = $chatService->sendMessage($serviceRequest, $requester, 'Alpha message');
        $msg2 = $chatService->sendMessage($serviceRequest, $provider, 'Beta response');
        $msg3 = $chatService->sendMessage($serviceRequest, $requester, 'Gamma followup');

        // Poll with after_id set to msg1->id => should return only msg2 and msg3
        $pollResponse = $this->actingAs($requester)->getJson(
            route('service-requests.chat', ['service_request' => $serviceRequest, 'after_id' => $msg1->id])
        );

        $pollResponse->assertOk();
        $messages = $pollResponse->json('messages');
        $this->assertCount(2, $messages);
        $this->assertSame($msg2->id, $messages[0]['id']);
        $this->assertSame('Beta response', $messages[0]['content']);
        $this->assertSame($msg3->id, $messages[1]['id']);
        $this->assertSame('Gamma followup', $messages[1]['content']);

        // Poll with after_id set to msg3->id => should return empty messages array
        $emptyPoll = $this->actingAs($requester)->getJson(
            route('service-requests.chat', ['service_request' => $serviceRequest, 'after_id' => $msg3->id])
        );

        $emptyPoll->assertOk();
        $this->assertEmpty($emptyPoll->json('messages'));
    }

    public function test_polling_endpoint_strictly_rejects_unauthorized_user(): void
    {
        [$serviceRequest, $requester] = $this->createServiceRequest();
        $outsider = User::factory()->create(['name' => 'Eve Outsider']);

        $pollResponse = $this->actingAs($outsider)->getJson(
            route('service-requests.chat', ['service_request' => $serviceRequest, 'after_id' => 0])
        );

        $pollResponse->assertForbidden();
    }

    public function test_polling_synchronizes_read_receipts_for_sender(): void
    {
        [$serviceRequest, $requester, $provider] = $this->createServiceRequest();
        $chatService = app(ServiceRequestChatService::class);

        // Requester sends a message
        $msg = $chatService->sendMessage($serviceRequest, $requester, 'Important project milestone reached');
        $this->assertNull($msg->read_at);

        // Provider polls chat (this automatically marks incoming messages as read)
        $providerPoll = $this->actingAs($provider)->getJson(
            route('service-requests.chat', ['service_request' => $serviceRequest, 'after_id' => 0])
        );
        $providerPoll->assertOk();

        $msg->refresh();
        $this->assertNotNull($msg->read_at);

        // Requester polls again with after_id set to their message id
        $requesterPoll = $this->actingAs($requester)->getJson(
            route('service-requests.chat', ['service_request' => $serviceRequest, 'after_id' => $msg->id])
        );
        $requesterPoll->assertOk();
        $this->assertSame($msg->id, $requesterPoll->json('last_read_id'));
    }

    public function test_two_user_simulated_live_chat_exchange_flow(): void
    {
        [$serviceRequest, $requester, $provider] = $this->createServiceRequest();

        // Step 1: Requester sends a message
        $sendResponse1 = $this->actingAs($requester)->postJson(route('service-requests.messages.store', $serviceRequest), [
            'content' => 'Hello provider, initiating live channel exchange.',
        ]);
        $sendResponse1->assertCreated();
        $msgId1 = $sendResponse1->json('id');

        // Step 2: Provider background polls with after_id = 0 (no page refresh)
        $providerPoll1 = $this->actingAs($provider)->getJson(
            route('service-requests.chat', ['service_request' => $serviceRequest, 'after_id' => 0])
        );
        $providerPoll1->assertOk();
        $messagesReceivedByProvider = $providerPoll1->json('messages');
        $this->assertCount(1, $messagesReceivedByProvider);
        $this->assertSame($msgId1, $messagesReceivedByProvider[0]['id']);
        $this->assertFalse($messagesReceivedByProvider[0]['is_me']);
        $this->assertSame($requester->name, $messagesReceivedByProvider[0]['sender_name']);

        // Step 3: Provider replies
        $sendResponse2 = $this->actingAs($provider)->postJson(route('service-requests.messages.store', $serviceRequest), [
            'content' => 'Transmission received loud and clear.',
        ]);
        $sendResponse2->assertCreated();
        $msgId2 = $sendResponse2->json('id');

        // Step 4: Requester background polls with after_id = msgId1 (no page refresh)
        $requesterPoll1 = $this->actingAs($requester)->getJson(
            route('service-requests.chat', ['service_request' => $serviceRequest, 'after_id' => $msgId1])
        );
        $requesterPoll1->assertOk();
        $messagesReceivedByRequester = $requesterPoll1->json('messages');
        $this->assertCount(1, $messagesReceivedByRequester);
        $this->assertSame($msgId2, $messagesReceivedByRequester[0]['id']);
        $this->assertFalse($messagesReceivedByRequester[0]['is_me']);
        $this->assertSame('Transmission received loud and clear.', $messagesReceivedByRequester[0]['content']);
        $this->assertSame($provider->name, $messagesReceivedByRequester[0]['sender_name']);

        // Step 5: Both users poll again with their latest IDs => both receive empty arrays (no duplicate payload)
        $this->actingAs($requester)->getJson(
            route('service-requests.chat', ['service_request' => $serviceRequest, 'after_id' => $msgId2])
        )->assertOk()->assertExactJson([
            'service_request_id' => $serviceRequest->id,
            'messages' => [],
            'last_read_id' => $msgId1,
        ]);

        $this->actingAs($provider)->getJson(
            route('service-requests.chat', ['service_request' => $serviceRequest, 'after_id' => $msgId2])
        )->assertOk()->assertExactJson([
            'service_request_id' => $serviceRequest->id,
            'messages' => [],
            'last_read_id' => $msgId2,
        ]);
    }

    public function test_invalid_or_negative_after_id_falls_back_gracefully(): void
    {
        [$serviceRequest, $requester] = $this->createServiceRequest();
        $chatService = app(ServiceRequestChatService::class);
        $chatService->sendMessage($serviceRequest, $requester, 'Sample message');

        $negativeResponse = $this->actingAs($requester)->getJson(
            route('service-requests.chat', ['service_request' => $serviceRequest, 'after_id' => -1])
        );
        $negativeResponse->assertOk();
        $this->assertCount(1, $negativeResponse->json('messages'));

        $stringResponse = $this->actingAs($requester)->getJson(
            route('service-requests.chat', ['service_request' => $serviceRequest, 'after_id' => 'invalid_string'])
        );
        $stringResponse->assertOk();
        $this->assertCount(1, $stringResponse->json('messages'));
    }

    private function createServiceRequest(string $reqBalance = '8.00', string $provBalance = '2.00', string $credits = '4.00'): array
    {
        $requester = User::factory()->create(['name' => 'Alice Requester', 'time_balance' => $reqBalance]);
        $provider = User::factory()->create(['name' => 'Bob Provider', 'time_balance' => $provBalance]);

        $category = Category::query()->create([
            'name' => 'Consulting',
            'slug' => 'consulting-' . uniqid(),
            'is_active' => true,
        ]);

        $service = Service::query()->create([
            'user_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Code Optimization & Review',
            'description' => 'System architectural analysis',
            'hourly_rate' => '2.00',
            'tags' => ['performance'],
            'is_active' => true,
        ]);

        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => $service->id,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Database Query Tuning',
            'project_scope' => 'Review SQL indexes and optimize slow queries',
            'estimated_hours' => '2.00',
            'total_credits' => $credits,
            'desired_deadline' => now()->addDays(3)->toDateString(),
            'status' => 'in_progress',
        ]);

        return [$serviceRequest, $requester, $provider];
    }
}
