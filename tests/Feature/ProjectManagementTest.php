<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_idea_owner_can_convert_recruiting_idea_into_project(): void
    {
        $owner = User::factory()->create();
        $collaboratorUser = User::factory()->create();
        $category = $this->activeCategory('Civic Lab', 'civic-lab');
        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Community Repair Hub',
            'mission_statement' => 'Build a local repair hub.',
            'target_hours' => '50.00',
            'required_skills' => ['repair', 'coordination'],
            'status' => 'recruiting',
        ]);
        $idea->collaborators()->create([
            'user_id' => $collaboratorUser->id,
            'role_offered' => 'Repair Mentor',
            'hours_pledged' => '8.00',
            'status' => 'accepted',
        ]);
        $idea->collaborators()->create([
            'user_id' => User::factory()->create()->id,
            'role_offered' => 'Observer',
            'hours_pledged' => '2.00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->postJson(route('ideas.project.store', $idea));

        $response->assertCreated();

        $project = Project::query()->firstOrFail();
        $this->assertSame($idea->id, $project->idea_id);
        $this->assertSame($owner->id, $project->lead_user_id);
        $this->assertSame($idea->title, $project->title);
        $this->assertSame($idea->mission_statement, $project->description);
        $this->assertSame('converted_to_project', $idea->fresh()->status);
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'member_role' => 'Lead',
        ]);
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $collaboratorUser->id,
            'member_role' => 'Repair Mentor',
        ]);
        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'member_role' => 'Observer',
        ]);
    }

    public function test_non_owner_cannot_convert_idea_into_project(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = $this->activeCategory('Green', 'green');
        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Tree Care Team',
            'mission_statement' => 'Care for neighborhood trees.',
            'target_hours' => '12.00',
            'required_skills' => ['gardening'],
            'status' => 'recruiting',
        ]);

        $response = $this->actingAs($otherUser)->postJson(route('ideas.project.store', $idea));

        $response->assertForbidden();
        $this->assertDatabaseMissing('projects', ['idea_id' => $idea->id]);
    }

    public function test_only_recruiting_ideas_can_be_converted(): void
    {
        $owner = User::factory()->create();
        $category = $this->activeCategory('Learning', 'learning');
        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Study Circle',
            'mission_statement' => 'Run a weekly study circle.',
            'target_hours' => '10.00',
            'required_skills' => ['teaching'],
            'status' => 'open',
        ]);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($owner)->postJson(route('ideas.project.store', $idea));
            $this->fail('Expected recruiting-only conversion restriction was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Only recruiting ideas can be converted into projects.', $exception->getMessage());
        }
    }

    public function test_project_member_can_view_project(): void
    {
        [$project, $member] = $this->makeProjectWithMember();

        $response = $this->actingAs($member)->getJson(route('projects.show', $project));

        $response->assertOk();
        $response->assertJsonFragment(['title' => $project->title]);
    }

    public function test_non_member_cannot_view_project(): void
    {
        [$project] = $this->makeProjectWithMember();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->getJson(route('projects.show', $project));

        $response->assertForbidden();
    }

    public function test_project_lead_can_add_member_without_duplicates(): void
    {
        [$project, $member, $lead] = $this->makeProjectWithMember();
        $newUser = User::factory()->create();

        $response = $this->actingAs($lead)->postJson(route('projects.members.store', $project), [
            'user_id' => $newUser->id,
            'member_role' => 'Coordinator',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $newUser->id,
            'member_role' => 'Coordinator',
        ]);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($lead)->postJson(route('projects.members.store', $project), [
                'user_id' => $newUser->id,
                'member_role' => 'Coordinator',
            ]);
            $this->fail('Expected duplicate project member exception was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('This user is already a project member.', $exception->getMessage());
        }
    }

    public function test_non_lead_cannot_manage_members(): void
    {
        [$project, $member] = $this->makeProjectWithMember();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($member)->postJson(route('projects.members.store', $project), [
            'user_id' => $targetUser->id,
        ]);

        $response->assertForbidden();
    }

    public function test_project_lead_can_update_member_role(): void
    {
        [$project, $member, $lead] = $this->makeProjectWithMember();
        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $response = $this->actingAs($lead)->patchJson(route('project-members.update', $projectMember), [
            'member_role' => 'Coordinator',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('project_members', [
            'id' => $projectMember->id,
            'project_id' => $project->id,
            'user_id' => $member->id,
            'member_role' => 'Coordinator',
        ]);
    }

    public function test_non_lead_cannot_update_member_role(): void
    {
        [$project, $member] = $this->makeProjectWithMember();
        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $response = $this->actingAs($member)->patchJson(route('project-members.update', $projectMember), [
            'member_role' => 'Coordinator',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('project_members', [
            'id' => $projectMember->id,
            'member_role' => 'Researcher',
        ]);
    }

    public function test_project_member_update_ignores_project_id_and_user_id_input(): void
    {
        [$project, $member, $lead] = $this->makeProjectWithMember();
        $otherProject = $this->makeStandaloneProject(User::factory()->create());
        $otherUser = User::factory()->create();
        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $response = $this->actingAs($lead)->patchJson(route('project-members.update', $projectMember), [
            'project_id' => $otherProject->id,
            'user_id' => $otherUser->id,
            'member_role' => 'Coordinator',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('project_members', [
            'id' => $projectMember->id,
            'project_id' => $project->id,
            'user_id' => $member->id,
            'member_role' => 'Coordinator',
        ]);
    }

    public function test_invalid_member_role_is_rejected(): void
    {
        [$project, $member, $lead] = $this->makeProjectWithMember();
        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $response = $this->actingAs($lead)->patchJson(route('project-members.update', $projectMember), [
            'member_role' => '',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('member_role');
    }

    public function test_project_lead_can_remove_non_lead_member(): void
    {
        [$project, $member, $lead] = $this->makeProjectWithMember();
        $projectMember = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $response = $this->actingAs($lead)->delete(route('project-members.destroy', $projectMember));

        $response->assertNoContent();
        $this->assertDatabaseMissing('project_members', ['id' => $projectMember->id]);
    }

    public function test_project_lead_can_create_update_and_delete_task(): void
    {
        [$project, $member, $lead] = $this->makeProjectWithMember();

        $createResponse = $this->actingAs($lead)->postJson(route('projects.tasks.store', $project), [
            'assigned_to' => $member->id,
            'title' => 'Plan kickoff',
            'description' => 'Prepare kickoff agenda',
            'target_hours' => '3.50',
            'status' => 'pending',
            'order_index' => 1,
        ]);

        $createResponse->assertCreated();
        $task = ProjectTask::query()->firstOrFail();
        $this->assertSame($member->id, $task->assigned_to);

        $updateResponse = $this->actingAs($lead)->patchJson(route('project-tasks.update', $task), [
            'assigned_to' => $member->id,
            'title' => 'Plan kickoff updated',
            'description' => 'Finalize kickoff agenda',
            'target_hours' => '4.00',
            'status' => 'in_progress',
            'order_index' => 2,
        ]);

        $updateResponse->assertOk();
        $task->refresh();
        $this->assertSame('Plan kickoff updated', $task->title);
        $this->assertSame('in_progress', $task->status);
        $this->assertSame(2, $task->order_index);

        $deleteResponse = $this->actingAs($lead)->delete(route('project-tasks.destroy', $task));

        $deleteResponse->assertNoContent();
        $this->assertDatabaseMissing('project_tasks', ['id' => $task->id]);
    }

    public function test_task_assignment_is_limited_to_project_members(): void
    {
        [$project, , $lead] = $this->makeProjectWithMember();
        $outsider = User::factory()->create();

        $response = $this->actingAs($lead)->postJson(route('projects.tasks.store', $project), [
            'assigned_to' => $outsider->id,
            'title' => 'Outsider task',
            'description' => 'Should fail',
            'target_hours' => '1.00',
            'status' => 'pending',
            'order_index' => 0,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('assigned_to');
    }

    public function test_non_lead_cannot_manage_tasks(): void
    {
        [$project, $member] = $this->makeProjectWithMember();

        $response = $this->actingAs($member)->postJson(route('projects.tasks.store', $project), [
            'assigned_to' => $member->id,
            'title' => 'Unauthorized task',
            'description' => 'Should fail',
            'target_hours' => '1.00',
            'status' => 'pending',
            'order_index' => 0,
        ]);

        $response->assertForbidden();
    }

    private function makeProjectWithMember(): array
    {
        $lead = User::factory()->create();
        $member = User::factory()->create();
        $category = $this->activeCategory('Project Space', 'project-space');
        $idea = $lead->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Community Archive',
            'mission_statement' => 'Build a local archive.',
            'target_hours' => '35.00',
            'required_skills' => ['research'],
            'status' => 'recruiting',
        ]);
        $project = Project::query()->create([
            'idea_id' => $idea->id,
            'lead_user_id' => $lead->id,
            'category_id' => $category->id,
            'title' => 'Community Archive',
            'description' => 'Build a local archive.',
            'target_hours' => '35.00',
            'hours_contributed' => '0.00',
            'status' => 'planning',
        ]);
        $project->members()->create([
            'user_id' => $lead->id,
            'member_role' => 'Lead',
            'hours_logged' => '0.00',
            'joined_at' => now(),
        ]);
        $project->members()->create([
            'user_id' => $member->id,
            'member_role' => 'Researcher',
            'hours_logged' => '0.00',
            'joined_at' => now(),
        ]);

        return [$project, $member, $lead];
    }

    private function makeStandaloneProject(User $lead): Project
    {
        $category = $this->activeCategory(fake()->unique()->words(2, true), fake()->unique()->slug());
        $idea = $lead->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Standalone Project Idea',
            'mission_statement' => 'Standalone mission.',
            'target_hours' => '10.00',
            'required_skills' => ['planning'],
            'status' => 'recruiting',
        ]);

        $project = Project::query()->create([
            'idea_id' => $idea->id,
            'lead_user_id' => $lead->id,
            'category_id' => $category->id,
            'title' => 'Standalone Project',
            'description' => 'Standalone mission.',
            'target_hours' => '10.00',
            'hours_contributed' => '0.00',
            'status' => 'planning',
        ]);

        $project->members()->create([
            'user_id' => $lead->id,
            'member_role' => 'Lead',
            'hours_logged' => '0.00',
            'joined_at' => now(),
        ]);

        return $project;
    }

    private function activeCategory(string $name, string $slug): Category
    {
        return Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
    }
}
