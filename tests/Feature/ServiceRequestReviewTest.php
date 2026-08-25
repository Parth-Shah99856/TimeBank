<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_can_review_provider_after_completed_exchange(): void
    {
        $requester = User::factory()->create();
        $provider = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Mentoring',
            'slug' => 'mentoring',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Career mentoring',
            'project_scope' => 'One mentoring session',
            'estimated_hours' => '1.00',
            'total_credits' => '2.00',
            'desired_deadline' => null,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($requester)->post(route('service-requests.reviews.store', $serviceRequest), [
            'rating' => 5,
            'comment' => 'Very helpful session.',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $review = Review::query()->where('service_request_id', $serviceRequest->id)->first();

        $this->assertNotNull($review);
        $this->assertSame($requester->id, $review->reviewer_id);
        $this->assertSame($provider->id, $review->reviewee_id);
        $this->assertSame(5, $review->rating);
        $this->assertSame('Very helpful session.', $review->comment);
    }

    public function test_provider_can_review_requester_after_completed_exchange(): void
    {
        $requester = User::factory()->create();
        $provider = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Translation',
            'slug' => 'translation',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Translate short text',
            'project_scope' => 'Translate a short paragraph',
            'estimated_hours' => '1.00',
            'total_credits' => '1.00',
            'desired_deadline' => null,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($provider)->post(route('service-requests.reviews.store', $serviceRequest), [
            'rating' => 4,
            'comment' => 'Clear brief and prompt follow-up.',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $review = Review::query()->where('service_request_id', $serviceRequest->id)->first();

        $this->assertNotNull($review);
        $this->assertSame($provider->id, $review->reviewer_id);
        $this->assertSame($requester->id, $review->reviewee_id);
        $this->assertSame(4, $review->rating);
    }

    public function test_review_requires_completed_exchange(): void
    {
        $requester = User::factory()->create();
        $provider = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Editing',
            'slug' => 'editing',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Edit CV',
            'project_scope' => 'Review and edit CV',
            'estimated_hours' => '1.00',
            'total_credits' => '1.00',
            'desired_deadline' => null,
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($requester)->post(route('service-requests.reviews.store', $serviceRequest), [
                'rating' => 5,
            ]);
            $this->fail('Expected completed exchange restriction exception was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Reviews are only allowed after completed exchanges.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('reviews', [
            'service_request_id' => $serviceRequest->id,
        ]);
    }

    public function test_only_one_review_is_allowed_per_service_request(): void
    {
        $requester = User::factory()->create();
        $provider = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Photography',
            'slug' => 'photography',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Photo selection help',
            'project_scope' => 'Help choose best photos',
            'estimated_hours' => '1.00',
            'total_credits' => '1.00',
            'desired_deadline' => null,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Review::query()->create([
            'service_request_id' => $serviceRequest->id,
            'reviewer_id' => $requester->id,
            'reviewee_id' => $provider->id,
            'rating' => 5,
            'comment' => 'Great help.',
        ]);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($provider)->post(route('service-requests.reviews.store', $serviceRequest), [
                'rating' => 4,
                'comment' => 'Good communication.',
            ]);
            $this->fail('Expected duplicate review exception was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('A review has already been submitted for this service request.', $exception->getMessage());
        }
    }

    public function test_non_participant_cannot_leave_review(): void
    {
        $requester = User::factory()->create();
        $provider = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Research',
            'slug' => 'research',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Find resources',
            'project_scope' => 'Find relevant resources',
            'estimated_hours' => '1.00',
            'total_credits' => '1.00',
            'desired_deadline' => null,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($otherUser)->post(route('service-requests.reviews.store', $serviceRequest), [
            'rating' => 3,
        ]);

        $response->assertForbidden();
    }

    public function test_review_rating_must_be_between_one_and_five(): void
    {
        $requester = User::factory()->create();
        $provider = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Coaching',
            'slug' => 'coaching',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Coaching session',
            'project_scope' => 'One coaching call',
            'estimated_hours' => '1.00',
            'total_credits' => '1.00',
            'desired_deadline' => null,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($requester)->post(route('service-requests.reviews.store', $serviceRequest), [
            'rating' => 6,
        ]);

        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseMissing('reviews', [
            'service_request_id' => $serviceRequest->id,
        ]);
    }
}
