<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectTask;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\IdeaApplicationReceivedNotification;
use App\Notifications\IdeaApplicationStatusNotification;
use App\Notifications\ProjectMemberAddedNotification;
use App\Notifications\ProjectMemberRoleChangedNotification;
use App\Notifications\ProjectTaskAssignedNotification;
use App\Notifications\ReviewReceivedNotification;
use App\Notifications\ServiceRequestStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_request_accept_creates_notification_for_requester(): void
    {
        [$serviceRequest, $requester, $provider] = $this->makeServiceRequest('pending');

        $this->actingAs($provider)->post(route('service-requests.accept', $serviceRequest));

        $notification = $requester->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(ServiceRequestStatusChangedNotification::class, $notification->type);
        $this->assertSame('accepted', $notification->data['status']);
        $this->assertSame($serviceRequest->id, $notification->data['service_request_id']);
    }

    public function test_invalid_service_request_transition_does_not_create_notification(): void
    {
        [$serviceRequest, $requester, $provider] = $this->makeServiceRequest('accepted');

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($provider)->post(route('service-requests.accept', $serviceRequest));
            $this->fail('Expected invalid transition exception was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Invalid service request transition from [accepted] to [accepted].', $exception->getMessage());
        }

        $this->assertCount(0, $requester->fresh()->notifications);
        $this->assertCount(0, $provider->fresh()->notifications);
    }

    public function test_service_request_completion_creates_notification_for_provider(): void
    {
        [$serviceRequest, $requester, $provider] = $this->makeServiceRequest('in_progress', '4.00', '8.00');

        $otp = app(\App\Services\SkillExchangeOtpService::class)->generateAndSend($serviceRequest, $requester);

        $this->actingAs($requester)->post(route('service-requests.complete', $serviceRequest), [
            'otp' => $otp,
        ]);

        $notification = $provider->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(ServiceRequestStatusChangedNotification::class, $notification->type);
        $this->assertSame('completed', $notification->data['status']);
    }

    public function test_review_creates_notification_for_reviewee(): void
    {
        [$serviceRequest, $requester, $provider] = $this->makeServiceRequest('completed');
        $serviceRequest->forceFill(['completed_at' => now()])->save();

        $this->actingAs($requester)->post(route('service-requests.reviews.store', $serviceRequest), [
            'rating' => 5,
            'comment' => 'Excellent help.',
        ]);

        $notification = $provider->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(ReviewReceivedNotification::class, $notification->type);
        $this->assertSame($serviceRequest->id, $notification->data['service_request_id']);
        $this->assertSame(5, $notification->data['rating']);
    }

    public function test_idea_application_creates_notification_for_idea_owner(): void
    {
        $owner = User::factory()->create();
        $applicant = User::factory()->create();
        $idea = $this->makeIdea($owner, 'recruiting');

        $this->actingAs($applicant)->postJson(route('ideas.collaborators.store', $idea), [
            'role_offered' => 'Planner',
            'hours_pledged' => '4.00',
        ]);

        $notification = $owner->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(IdeaApplicationReceivedNotification::class, $notification->type);
        $this->assertSame($idea->id, $notification->data['idea_id']);
        $this->assertSame($applicant->id, $notification->data['applicant_id']);
    }

    public function test_idea_application_status_change_notifies_applicant(): void
    {
        $owner = User::factory()->create();
        $applicant = User::factory()->create();
        $idea = $this->makeIdea($owner, 'recruiting');
        $collaborator = $idea->collaborators()->create([
            'user_id' => $applicant->id,
            'role_offered' => 'Builder',
            'hours_pledged' => '5.00',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)->patchJson(route('idea-collaborators.update', $collaborator), [
            'status' => 'accepted',
        ]);

        $notification = $applicant->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(IdeaApplicationStatusNotification::class, $notification->type);
        $this->assertSame('accepted', $notification->data['status']);
    }

    public function test_adding_project_member_notifies_added_user(): void
    {
        [$project, $lead] = $this->makeProject();
        $newUser = User::factory()->create();

        $this->actingAs($lead)->postJson(route('projects.members.store', $project), [
            'user_id' => $newUser->id,
            'member_role' => 'Coordinator',
        ]);

        $notification = $newUser->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(ProjectMemberAddedNotification::class, $notification->type);
        $this->assertSame($project->id, $notification->data['project_id']);
        $this->assertSame('Coordinator', $notification->data['member_role']);
    }

    public function test_successful_project_member_role_change_creates_exactly_one_notification(): void
    {
        [$project, $lead, $member] = $this->makeProjectWithMember();
        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $this->actingAs($lead)->patchJson(route('project-members.update', $projectMember), [
            'member_role' => 'Coordinator',
        ])->assertOk();

        $notifications = $member->fresh()->notifications;

        $this->assertCount(1, $notifications);
        $this->assertSame(ProjectMemberRoleChangedNotification::class, $notifications->first()->type);
        $this->assertSame($project->id, $notifications->first()->data['project_id']);
        $this->assertSame('Contributor', $notifications->first()->data['previous_role']);
        $this->assertSame('Coordinator', $notifications->first()->data['member_role']);
    }

    public function test_unchanged_project_member_role_does_not_create_notification(): void
    {
        [$project, $lead, $member] = $this->makeProjectWithMember();
        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $this->actingAs($lead)->patchJson(route('project-members.update', $projectMember), [
            'member_role' => 'Contributor',
        ])->assertOk();

        $this->assertCount(0, $member->fresh()->notifications);
    }

    public function test_assigning_project_task_notifies_assignee(): void
    {
        [$project, $lead, $member] = $this->makeProjectWithMember();

        $this->actingAs($lead)->postJson(route('projects.tasks.store', $project), [
            'assigned_to' => $member->id,
            'title' => 'Kickoff task',
            'description' => 'Prepare kickoff materials',
            'target_hours' => '2.00',
            'status' => 'pending',
            'order_index' => 1,
        ]);

        $notification = $member->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(ProjectTaskAssignedNotification::class, $notification->type);
        $this->assertSame('Kickoff task', $notification->data['title']);
    }

    public function test_unauthorized_project_member_role_change_creates_no_notification(): void
    {
        [$project, $lead, $member] = $this->makeProjectWithMember();
        $outsider = User::factory()->create();
        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $this->actingAs($outsider)->patchJson(route('project-members.update', $projectMember), [
            'member_role' => 'Coordinator',
        ])->assertForbidden();

        $this->assertCount(0, $member->fresh()->notifications);
    }

    public function test_invalid_project_member_role_change_creates_no_notification(): void
    {
        [$project, $lead, $member] = $this->makeProjectWithMember();
        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $this->actingAs($lead)->patchJson(route('project-members.update', $projectMember), [
            'member_role' => '',
        ])->assertUnprocessable();

        $this->assertCount(0, $member->fresh()->notifications);
    }

    public function test_non_owner_cannot_trigger_idea_application_status_notification(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $applicant = User::factory()->create();
        $idea = $this->makeIdea($owner, 'recruiting');
        $collaborator = $idea->collaborators()->create([
            'user_id' => $applicant->id,
            'role_offered' => 'Volunteer',
            'hours_pledged' => '2.00',
            'status' => 'pending',
        ]);

        $this->actingAs($otherUser)->patchJson(route('idea-collaborators.update', $collaborator), [
            'status' => 'accepted',
        ])->assertForbidden();

        $this->assertCount(0, $applicant->fresh()->notifications);
    }

    private function makeServiceRequest(string $status, string $credits = '2.00', string $requesterBalance = '5.00'): array
    {
        $requester = User::factory()->create(['time_balance' => $requesterBalance]);
        $provider = User::factory()->create(['time_balance' => '1.00']);
        $category = Category::query()->create([
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::query()->create([
            'service_id' => null,
            'requester_id' => $requester->id,
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Test service request',
            'project_scope' => 'Test scope',
            'estimated_hours' => '1.00',
            'total_credits' => $credits,
            'desired_deadline' => null,
            'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);

        return [$serviceRequest, $requester, $provider];
    }

    private function makeIdea(User $owner, string $status): Idea
    {
        $category = Category::query()->create([
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);

        return $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Idea title',
            'mission_statement' => 'Idea mission',
            'target_hours' => '10.00',
            'required_skills' => ['planning'],
            'status' => $status,
        ]);
    }

    private function makeProject(): array
    {
        $lead = User::factory()->create();
        $category = Category::query()->create([
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
        $idea = $lead->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Project idea',
            'mission_statement' => 'Mission',
            'target_hours' => '20.00',
            'required_skills' => ['ops'],
            'status' => 'recruiting',
        ]);
        $project = Project::query()->create([
            'idea_id' => $idea->id,
            'lead_user_id' => $lead->id,
            'category_id' => $category->id,
            'title' => 'Project title',
            'description' => 'Project description',
            'target_hours' => '20.00',
            'hours_contributed' => '0.00',
            'status' => 'planning',
        ]);
        $project->members()->create([
            'user_id' => $lead->id,
            'member_role' => 'Lead',
            'hours_logged' => '0.00',
            'joined_at' => now(),
        ]);

        return [$project, $lead];
    }

    private function makeProjectWithMember(): array
    {
        [$project, $lead] = $this->makeProject();
        $member = User::factory()->create();
        $project->members()->create([
            'user_id' => $member->id,
            'member_role' => 'Contributor',
            'hours_logged' => '0.00',
            'joined_at' => now(),
        ]);

        return [$project, $lead, $member];
    }
}
