<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaCollaborator;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectTask;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class DatabaseArchitectureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: User can exist with default role and starting time balance of 5.00.
     */
    public function test_user_can_exist_with_default_role_and_time_balance(): void
    {
        $user = User::create([
            'name' => 'Alice Developer',
            'email' => 'alice@example.com',
            'password' => bcrypt('password'),
            'headline' => 'Frontend Specialist',
            'bio' => 'Passionate about Vue and Tailwind.',
            'avatar_url' => '/avatars/alice.png',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
            'role' => 'user',
            'time_balance' => 5.00,
        ]);

        $user->refresh();

        $this->assertEquals('5.00', (string) $user->time_balance);
        $this->assertFalse($user->isAdmin());
    }

    /**
     * Test 2: Signup bonus transaction can be recorded and is immutable.
     */
    public function test_signup_bonus_can_be_recorded_and_is_immutable(): void
    {
        $user = User::create([
            'name' => 'Bob Engineer',
            'email' => 'bob@example.com',
            'password' => bcrypt('password'),
            'time_balance' => 5.00,
        ]);

        $transaction = Transaction::create([
            'transaction_code' => 'TX-BONUS-TEST01',
            'from_user_id' => null,
            'to_user_id' => $user->id,
            'service_request_id' => null,
            'amount' => 5.00,
            'type' => Transaction::TYPE_SIGNUP_BONUS,
            'description' => 'Initial signup bonus',
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('transactions', [
            'transaction_code' => 'TX-BONUS-TEST01',
            'to_user_id' => $user->id,
            'amount' => 5.00,
            'type' => 'signup_bonus',
        ]);

        // Verify transactions cannot be updated (immutable)
        $this->expectException(LogicException::class);
        $transaction->update(['amount' => 10.00]);
    }

    /**
     * Test 3: Category can be created with slug and active status.
     */
    public function test_category_can_be_created(): void
    {
        $category = Category::create([
            'name' => 'Full-Stack Development',
            'slug' => 'full-stack-dev',
            'description' => 'End to end web applications.',
            'icon' => 'code',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('categories', [
            'slug' => 'full-stack-dev',
            'name' => 'Full-Stack Development',
            'is_active' => true,
        ]);
    }

    /**
     * Test 4: Service belongs to user and category.
     */
    public function test_service_belongs_to_user_and_category(): void
    {
        $user = User::create([
            'name' => 'Charlie Designer',
            'email' => 'charlie@example.com',
            'password' => bcrypt('password'),
        ]);

        $category = Category::create([
            'name' => 'Graphic Design',
            'slug' => 'graphic-design',
        ]);

        $service = Service::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Logo & Brand Identity',
            'description' => 'Modern vector logos and complete guidelines.',
            'hourly_rate' => 1.50,
            'tags' => ['Logo', 'Figma', 'Illustrator'],
            'is_active' => true,
        ]);

        $this->assertEquals($user->id, $service->user->id);
        $this->assertEquals($category->id, $service->category->id);
        $this->assertCount(1, $user->services);
        $this->assertCount(1, $category->services);
    }

    /**
     * Test 5: Service request references requester and provider.
     */
    public function test_service_request_references_requester_and_provider(): void
    {
        $requester = User::create([
            'name' => 'Requester User',
            'email' => 'req@example.com',
            'password' => bcrypt('password'),
        ]);

        $provider = User::create([
            'name' => 'Provider User',
            'email' => 'prov@example.com',
            'password' => bcrypt('password'),
        ]);

        $category = Category::create([
            'name' => 'Copywriting',
            'slug' => 'copywriting',
        ]);

        $serviceRequest = ServiceRequest::create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Landing Page Copy Audit',
            'project_scope' => 'Audit the hero section copy for maximum conversion.',
            'estimated_hours' => 2.00,
            'total_credits' => 2.00,
            'status' => 'pending',
        ]);

        $this->assertEquals($requester->id, $serviceRequest->requester->id);
        $this->assertEquals($provider->id, $serviceRequest->provider->id);
        $this->assertCount(1, $requester->requestedServiceRequests);
        $this->assertCount(1, $provider->providedServiceRequests);
    }

    /**
     * Test 6: Transaction references users correctly.
     */
    public function test_transaction_references_users_correctly(): void
    {
        $sender = User::create([
            'name' => 'Sender User',
            'email' => 'sender@example.com',
            'password' => bcrypt('password'),
        ]);

        $receiver = User::create([
            'name' => 'Receiver User',
            'email' => 'receiver@example.com',
            'password' => bcrypt('password'),
        ]);

        $transaction = Transaction::create([
            'transaction_code' => 'TX-EXCHANGE-001',
            'from_user_id' => $sender->id,
            'to_user_id' => $receiver->id,
            'service_request_id' => null,
            'amount' => 2.50,
            'type' => Transaction::TYPE_SERVICE_EXCHANGE,
            'description' => 'Completed Web Development Session',
            'created_at' => now(),
        ]);

        $this->assertEquals($sender->id, $transaction->fromUser->id);
        $this->assertEquals($receiver->id, $transaction->toUser->id);
        $this->assertCount(1, $sender->outgoingTransactions);
        $this->assertCount(1, $receiver->incomingTransactions);
    }

    /**
     * Test 7: Idea collaborators enforce unique (idea_id, user_id).
     */
    public function test_idea_collaborators_enforce_unique_constraint(): void
    {
        $creator = User::create([
            'name' => 'Idea Creator',
            'email' => 'creator@example.com',
            'password' => bcrypt('password'),
        ]);

        $collaborator = User::create([
            'name' => 'Collaborator User',
            'email' => 'collab@example.com',
            'password' => bcrypt('password'),
        ]);

        $category = Category::create([
            'name' => 'Robotics',
            'slug' => 'robotics',
        ]);

        $idea = Idea::create([
            'user_id' => $creator->id,
            'category_id' => $category->id,
            'title' => 'Open Source Drone Swarm',
            'mission_statement' => 'Coordinated agricultural monitoring with drones.',
            'target_hours' => 150.00,
            'required_skills' => ['C++', 'Embedded Systems'],
            'status' => 'open',
        ]);

        IdeaCollaborator::create([
            'idea_id' => $idea->id,
            'user_id' => $collaborator->id,
            'role_offered' => 'Embedded Engineer',
            'hours_pledged' => 20.00,
            'status' => 'pending',
        ]);

        $this->expectException(QueryException::class);
        // Duplicate application should trigger SQL unique constraint violation
        IdeaCollaborator::create([
            'idea_id' => $idea->id,
            'user_id' => $collaborator->id,
            'role_offered' => 'Another Role',
            'hours_pledged' => 10.00,
            'status' => 'pending',
        ]);
    }

    /**
     * Test 8: Project members enforce unique (project_id, user_id).
     */
    public function test_project_members_enforce_unique_constraint(): void
    {
        $lead = User::create([
            'name' => 'Project Lead',
            'email' => 'lead@example.com',
            'password' => bcrypt('password'),
        ]);

        $member = User::create([
            'name' => 'Team Member',
            'email' => 'member@example.com',
            'password' => bcrypt('password'),
        ]);

        $category = Category::create([
            'name' => 'Architecture',
            'slug' => 'architecture',
        ]);

        $project = Project::create([
            'idea_id' => null,
            'lead_user_id' => $lead->id,
            'category_id' => $category->id,
            'title' => 'Smart City Infrastructure',
            'description' => 'Building modular urban sensors.',
            'target_hours' => 500.00,
            'hours_contributed' => 0.00,
            'status' => 'active',
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $member->id,
            'member_role' => 'Sensor Specialist',
            'hours_logged' => 0.00,
        ]);

        $this->expectException(QueryException::class);
        // Duplicate membership should fail unique constraint
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $member->id,
            'member_role' => 'Duplicate Role',
            'hours_logged' => 0.00,
        ]);
    }

    /**
     * Test 9: Review enforces one review per service request (unique: service_request_id).
     */
    public function test_review_enforces_one_review_per_service_request(): void
    {
        $requester = User::create([
            'name' => 'Requester',
            'email' => 'r1@example.com',
            'password' => bcrypt('password'),
        ]);

        $provider = User::create([
            'name' => 'Provider',
            'email' => 'p1@example.com',
            'password' => bcrypt('password'),
        ]);

        $category = Category::create([
            'name' => 'Consulting',
            'slug' => 'consulting',
        ]);

        $serviceRequest = ServiceRequest::create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Consulting Session',
            'project_scope' => 'Review business plan.',
            'estimated_hours' => 1.00,
            'total_credits' => 1.00,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        Review::create([
            'service_request_id' => $serviceRequest->id,
            'reviewer_id' => $requester->id,
            'reviewee_id' => $provider->id,
            'rating' => 5,
            'comment' => 'Outstanding consultation, highly recommended!',
        ]);

        $this->assertDatabaseHas('reviews', [
            'service_request_id' => $serviceRequest->id,
            'rating' => 5,
        ]);

        $this->expectException(QueryException::class);
        // Duplicate review for the same exchange should fail unique constraint
        Review::create([
            'service_request_id' => $serviceRequest->id,
            'reviewer_id' => $requester->id,
            'reviewee_id' => $provider->id,
            'rating' => 4,
            'comment' => 'Duplicate review attempt.',
        ]);
    }
}
