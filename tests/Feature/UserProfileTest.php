<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helper: creates an active category
    // -----------------------------------------------------------------------

    private function activeCategory(string $name = 'Web Dev', string $slug = 'web-dev'): Category
    {
        return Category::query()->create([
            'name'        => $name,
            'slug'        => $slug,
            'description' => null,
            'icon'        => null,
            'is_active'   => true,
        ]);
    }

    // Helper: creates a Service belonging to $user
    private function makeService(User $user, Category $category, string $title = 'Test Service'): Service
    {
        return Service::query()->create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => $title,
            'description' => 'A test service description.',
            'hourly_rate' => '2.00',
            'tags'        => [],
            'is_active'   => true,
        ]);
    }

    // Helper: creates a completed ServiceRequest between $requester and $provider
    private function makeCompletedSR(Service $service, User $requester, User $provider): ServiceRequest
    {
        return ServiceRequest::query()->create([
            'service_id'   => $service->id,
            'requester_id' => $requester->id,
            'provider_id'  => $provider->id,
            'category_id'  => $service->category_id,
            'title'        => 'Test exchange',
            'project_scope' => 'Scope of work.',
            'estimated_hours' => '1.00',
            'total_credits'   => '2.00',
            'desired_deadline' => null,
            'status'          => 'completed',
            'completed_at'    => now(),
        ]);
    }

    // -----------------------------------------------------------------------
    // Route / access
    // -----------------------------------------------------------------------

    public function test_guest_can_view_a_public_user_profile(): void
    {
        $user = User::factory()->create(['name' => 'Alice Temporal', 'headline' => 'Cloud Architect']);

        $response = $this->get(route('users.show', $user->id));

        $response->assertOk();
        $response->assertSee('Alice Temporal');
    }

    public function test_authenticated_user_can_view_another_users_profile(): void
    {
        $viewer  = User::factory()->create();
        $subject = User::factory()->create(['name' => 'Bob Builder']);

        $response = $this->actingAs($viewer)->get(route('users.show', $subject->id));

        $response->assertOk();
        $response->assertSee('Bob Builder');
    }

    public function test_authenticated_user_can_view_their_own_profile(): void
    {
        $user = User::factory()->create(['name' => 'Carol Self']);

        $response = $this->actingAs($user)->get(route('users.show', $user->id));

        $response->assertOk();
        $response->assertSee('Carol Self');
    }

    public function test_non_existent_user_returns_404(): void
    {
        $response = $this->get(route('users.show', 99999));

        $response->assertNotFound();
    }

    // -----------------------------------------------------------------------
    // Profile information displayed
    // -----------------------------------------------------------------------

    public function test_profile_displays_name_headline_and_bio(): void
    {
        $user = User::factory()->create([
            'name'     => 'Dana Pulse',
            'headline' => 'Full-Stack Temporal Engineer',
            'bio'      => 'I build decentralised systems.',
        ]);

        $response = $this->get(route('users.show', $user->id));

        $response->assertOk();
        $response->assertSee('Dana Pulse');
        $response->assertSee('Full-Stack Temporal Engineer');
        $response->assertSee('I build decentralised systems.');
    }

    public function test_profile_displays_active_services(): void
    {
        $category = $this->activeCategory();
        $user     = User::factory()->create();
        $this->makeService($user, $category, 'Advanced Laravel Architecture');

        $response = $this->get(route('users.show', $user->id));

        $response->assertOk();
        $response->assertSee('Advanced Laravel Architecture');
    }

    public function test_profile_does_not_display_inactive_services(): void
    {
        $category = $this->activeCategory();
        $user     = User::factory()->create();

        Service::query()->create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => 'Secret Inactive Service',
            'description' => 'Hidden from profile.',
            'hourly_rate' => '1.00',
            'tags'        => [],
            'is_active'   => false,
        ]);

        $response = $this->get(route('users.show', $user->id));

        $response->assertOk();
        $response->assertDontSee('Secret Inactive Service');
    }

    public function test_profile_displays_reviews_received(): void
    {
        $category = $this->activeCategory();
        $subject  = User::factory()->create();
        $reviewer = User::factory()->create(['name' => 'Eve Starr']);
        $service  = $this->makeService($subject, $category);
        $sr       = $this->makeCompletedSR($service, $reviewer, $subject);

        Review::query()->create([
            'service_request_id' => $sr->id,
            'reviewer_id'        => $reviewer->id,
            'reviewee_id'        => $subject->id,
            'rating'             => 5,
            'comment'            => 'Outstanding temporal work!',
        ]);

        $response = $this->get(route('users.show', $subject->id));

        $response->assertOk();
        $response->assertSee('Outstanding temporal work!');
    }

    public function test_profile_displays_open_ideas_posted_by_user(): void
    {
        $category = $this->activeCategory('Civic', 'civic');
        $user     = User::factory()->create();

        Idea::query()->create([
            'user_id'           => $user->id,
            'category_id'       => $category->id,
            'title'             => 'Build a Temporal Mesh Network',
            'mission_statement' => 'Connect time-bankers globally.',
            'target_hours'      => '100.00',
            'required_skills'   => null,
            'status'            => 'open',
        ]);

        $response = $this->get(route('users.show', $user->id));

        $response->assertOk();
        $response->assertSee('Build a Temporal Mesh Network');
    }

    public function test_profile_does_not_display_archived_ideas(): void
    {
        $category = $this->activeCategory('Civic', 'civic');
        $user     = User::factory()->create();

        Idea::query()->create([
            'user_id'           => $user->id,
            'category_id'       => $category->id,
            'title'             => 'Completed Private Initiative',
            'mission_statement' => 'Done and dusted.',
            'target_hours'      => '50.00',
            'required_skills'   => null,
            'status'            => 'archived',
        ]);

        $response = $this->get(route('users.show', $user->id));

        $response->assertOk();
        $response->assertDontSee('Completed Private Initiative');
    }

    // -----------------------------------------------------------------------
    // Empty states
    // -----------------------------------------------------------------------

    public function test_profile_shows_empty_state_when_no_active_services(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('users.show', $user->id));

        $response->assertOk();
        $response->assertSee('No Active Services');
    }

    public function test_profile_shows_empty_state_when_no_reviews(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('users.show', $user->id));

        $response->assertOk();
        $response->assertSee('No Reviews Yet');
    }

    // -----------------------------------------------------------------------
    // Security: private information must NOT be exposed
    // -----------------------------------------------------------------------

    public function test_profile_does_not_expose_email_address(): void
    {
        $user = User::factory()->create(['email' => 'private@secret.com']);

        $response = $this->get(route('users.show', $user->id));

        $response->assertOk();
        $response->assertDontSee('private@secret.com');
    }

    public function test_profile_does_not_expose_time_balance(): void
    {
        $subject = User::factory()->create(['name' => 'Wallet Test User', 'time_balance' => '999.99']);

        $response = $this->get(route('users.show', $subject->id));

        $response->assertOk();
        $response->assertDontSee('999.99');
    }

    public function test_profile_does_not_expose_private_service_request_chat_messages(): void
    {
        $category = $this->activeCategory();
        $subject  = User::factory()->create();
        $other    = User::factory()->create();
        $service  = $this->makeService($subject, $category);

        $sr = ServiceRequest::query()->create([
            'service_id'      => $service->id,
            'requester_id'    => $other->id,
            'provider_id'     => $subject->id,
            'category_id'     => $category->id,
            'title'           => 'Live exchange',
            'project_scope'   => 'Scope.',
            'estimated_hours' => '1.00',
            'total_credits'   => '2.00',
            'desired_deadline' => null,
            'status'          => 'in_progress',
            'completed_at'    => null,
        ]);

        ServiceRequestMessage::query()->create([
            'service_request_id' => $sr->id,
            'sender_id'          => $other->id,
            'content'            => 'This is a confidential chat message.',
        ]);

        $response = $this->get(route('users.show', $subject->id));

        $response->assertOk();
        $response->assertDontSee('confidential chat message');
    }

    // -----------------------------------------------------------------------
    // Profile links in existing pages
    // -----------------------------------------------------------------------

    public function test_service_show_page_contains_profile_link_for_provider(): void
    {
        $category = $this->activeCategory();
        $provider = User::factory()->create(['name' => 'Linked Provider']);
        $service  = $this->makeService($provider, $category);

        $response = $this->get(route('services.show', $service->id));

        $response->assertOk();
        $response->assertSee(route('users.show', $provider->id), false);
    }

    public function test_leaderboard_contains_profile_links(): void
    {
        $category  = $this->activeCategory();
        $provider  = User::factory()->create(['name' => 'Leaderboard Hero']);
        $requester = User::factory()->create();
        $service   = $this->makeService($provider, $category);

        $sr = $this->makeCompletedSR($service, $requester, $provider);

        // A transaction is needed so the user appears on the leaderboard
        Transaction::query()->create([
            'transaction_code'   => 'TXN-LEADER-001',
            'service_request_id' => $sr->id,
            'from_user_id'       => $requester->id,
            'to_user_id'         => $provider->id,
            'type'               => Transaction::TYPE_SERVICE_EXCHANGE,
            'amount'             => '2.00',
            'description'        => 'Exchange payment',
        ]);

        $response = $this->get(route('leaderboard'));

        $response->assertOk();
        $response->assertSee(route('users.show', $provider->id), false);
    }

    // -----------------------------------------------------------------------
    // Edit shortcut only visible to profile owner
    // -----------------------------------------------------------------------

    public function test_edit_profile_shortcut_is_visible_only_to_owner(): void
    {
        $owner  = User::factory()->create();
        $viewer = User::factory()->create();

        // Owner sees the "Edit My Profile" button on their own profile
        $this->actingAs($owner)
             ->get(route('users.show', $owner->id))
             ->assertSee('Edit My Profile');

        // Another user does NOT see the "Edit My Profile" button on someone else's profile
        $this->actingAs($viewer)
             ->get(route('users.show', $owner->id))
             ->assertDontSee('Edit My Profile');
    }
}
