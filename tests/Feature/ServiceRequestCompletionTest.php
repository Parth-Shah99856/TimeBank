<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SkillExchangeOtpService;
use LogicException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_can_complete_an_in_progress_service_request_and_transfer_credits(): void
    {
        $requester = User::factory()->create(['time_balance' => '8.00']);
        $provider = User::factory()->create(['time_balance' => '2.00']);
        $category = Category::query()->create([
            'name' => 'Design',
            'slug' => 'design',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $service = Service::query()->create([
            'user_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Logo review',
            'description' => 'Review a logo concept',
            'hourly_rate' => '2.00',
            'tags' => ['branding'],
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => $service->id,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Need logo feedback',
            'project_scope' => 'Review existing draft and provide feedback',
            'estimated_hours' => '2.00',
            'total_credits' => '4.00',
            'desired_deadline' => now()->addWeek()->toDateString(),
            'status' => 'in_progress',
        ]);

        $otp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        $response = $this->actingAs($requester)->post(route('service-requests.complete', $serviceRequest), [
            'otp' => $otp,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $requester->refresh();
        $provider->refresh();
        $serviceRequest->refresh();
        $transaction = Transaction::query()->where('service_request_id', $serviceRequest->id)->first();

        $this->assertSame('4.00', $requester->time_balance);
        $this->assertSame('6.00', $provider->time_balance);
        $this->assertSame('completed', $serviceRequest->status);
        $this->assertNotNull($serviceRequest->completed_at);
        $this->assertNotNull($transaction);
        $this->assertSame(Transaction::TYPE_SERVICE_EXCHANGE, $transaction->type);
        $this->assertSame('4.00', $transaction->amount);
        $this->assertSame($requester->id, $transaction->from_user_id);
        $this->assertSame($provider->id, $transaction->to_user_id);
        $this->assertNotEmpty($transaction->transaction_code);
    }

    public function test_requester_can_complete_an_in_progress_service_request_with_json_response(): void
    {
        $requester = User::factory()->create(['time_balance' => '8.00']);
        $provider = User::factory()->create(['time_balance' => '2.00']);
        $category = Category::query()->create([
            'name' => 'Design',
            'slug' => 'design-json',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $service = Service::query()->create([
            'user_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Logo review',
            'description' => 'Review a logo concept',
            'hourly_rate' => '2.00',
            'tags' => ['branding'],
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => $service->id,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Need logo feedback',
            'project_scope' => 'Review existing draft and provide feedback',
            'estimated_hours' => '2.00',
            'total_credits' => '4.00',
            'desired_deadline' => now()->addWeek()->toDateString(),
            'status' => 'in_progress',
        ]);

        $otp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        $response = $this->actingAs($requester)->postJson(route('service-requests.complete', $serviceRequest), [
            'otp' => $otp,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'completed');
        $this->assertSame('completed', $serviceRequest->fresh()->status);
    }

    public function test_double_completion_is_prevented_after_successful_transfer(): void
    {
        $requester = User::factory()->create(['time_balance' => '8.00']);
        $provider = User::factory()->create(['time_balance' => '2.00']);
        $category = Category::query()->create([
            'name' => 'Illustration',
            'slug' => 'illustration',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Need illustration feedback',
            'project_scope' => 'Review one illustration concept',
            'estimated_hours' => '2.00',
            'total_credits' => '4.00',
            'desired_deadline' => null,
            'status' => 'in_progress',
        ]);

        $otp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        $this->actingAs($requester)->post(route('service-requests.complete', $serviceRequest), [
            'otp' => $otp,
        ]);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($requester)->post(route('service-requests.complete', $serviceRequest), [
                'otp' => $otp,
            ]);
            $this->fail('Expected double completion exception was not thrown.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('otp', $exception->errors());
        }

        $requester->refresh();
        $provider->refresh();
        $serviceRequest->refresh();

        $this->assertSame('4.00', $requester->time_balance);
        $this->assertSame('6.00', $provider->time_balance);
        $this->assertSame('completed', $serviceRequest->status);
        $this->assertEquals(1, Transaction::query()->where('service_request_id', $serviceRequest->id)->count());
    }

    public function test_transaction_records_are_immutable(): void
    {
        $requester = User::factory()->create(['time_balance' => '8.00']);
        $provider = User::factory()->create(['time_balance' => '2.00']);
        $category = Category::query()->create([
            'name' => 'Audio',
            'slug' => 'audio',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Review podcast audio',
            'project_scope' => 'Review one short episode draft',
            'estimated_hours' => '1.00',
            'total_credits' => '2.00',
            'desired_deadline' => null,
            'status' => 'in_progress',
        ]);

        $otp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        $this->actingAs($requester)->post(route('service-requests.complete', $serviceRequest), [
            'otp' => $otp,
        ]);

        $transaction = Transaction::query()->where('service_request_id', $serviceRequest->id)->firstOrFail();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Transactions are immutable and cannot be updated.');

        $transaction->description = 'Tampered description';
        $transaction->save();
    }

    public function test_non_requester_cannot_complete_a_service_request(): void
    {
        $requester = User::factory()->create(['time_balance' => '8.00']);
        $provider = User::factory()->create(['time_balance' => '2.00']);
        $otherUser = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Writing',
            'slug' => 'writing',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Edit article',
            'project_scope' => 'Proofread and edit article copy',
            'estimated_hours' => '1.00',
            'total_credits' => '2.00',
            'desired_deadline' => null,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($otherUser)->post(route('service-requests.complete', $serviceRequest));

        $response->assertForbidden();
        $this->assertDatabaseMissing('transactions', [
            'service_request_id' => $serviceRequest->id,
            'type' => Transaction::TYPE_SERVICE_EXCHANGE,
        ]);
    }

    public function test_completion_fails_when_requester_has_insufficient_balance(): void
    {
        $requester = User::factory()->create(['time_balance' => '1.00']);
        $provider = User::factory()->create(['time_balance' => '2.00']);
        $category = Category::query()->create([
            'name' => 'Development',
            'slug' => 'development',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Fix bug',
            'project_scope' => 'Investigate and fix one application bug',
            'estimated_hours' => '2.00',
            'total_credits' => '3.00',
            'desired_deadline' => null,
            'status' => 'in_progress',
        ]);

        $otp = app(SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($requester)->post(route('service-requests.complete', $serviceRequest), [
                'otp' => $otp,
            ]);
            $this->fail('Expected insufficient balance exception was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Insufficient time balance to complete this exchange.', $exception->getMessage());
        }

        $requester->refresh();
        $provider->refresh();
        $serviceRequest->refresh();

        $this->assertSame('1.00', $requester->time_balance);
        $this->assertSame('2.00', $provider->time_balance);
        $this->assertSame('in_progress', $serviceRequest->status);
        $this->assertDatabaseMissing('transactions', [
            'service_request_id' => $serviceRequest->id,
            'type' => Transaction::TYPE_SERVICE_EXCHANGE,
        ]);
    }
}
