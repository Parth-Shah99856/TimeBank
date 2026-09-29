<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaCollaborator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaCollaboratorFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(): Category
    {
        return Category::query()->create([
            'name' => 'Data Science & AI',
            'slug' => 'data-science-ai',
            'description' => 'Artificial intelligence initiatives',
            'icon' => null,
            'is_active' => true,
        ]);
    }

    public function test_eligible_user_can_join_as_collaborator(): void
    {
        $owner = User::factory()->create(['name' => 'Maya Patel']);
        $applicant = User::factory()->create(['name' => 'Elena Rostova']);
        $category = $this->createCategory();

        $idea = Idea::query()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Open Data Science Learning Hub',
            'mission_statement' => 'Create a curated repository of data science tutorials.',
            'target_hours' => '40.00',
            'required_skills' => ['python', 'data-science'],
            'status' => 'open',
        ]);

        $response = $this->actingAs($applicant)
            ->from(route('ideas.show', $idea))
            ->post(route('ideas.collaborators.store', $idea), [
                'role_offered' => 'Curriculum Contributor',
                'hours_pledged' => '10',
            ]);

        $response->assertRedirect(route('ideas.show', $idea));
        $response->assertSessionHas('status', 'Application submitted to project lead successfully.');

        $this->assertDatabaseHas('idea_collaborators', [
            'idea_id' => $idea->id,
            'user_id' => $applicant->id,
            'role_offered' => 'Curriculum Contributor',
            'hours_pledged' => '10.00',
            'status' => 'pending',
        ]);

        // Follow redirect and ensure UI shows application pending review
        $pageResponse = $this->actingAs($applicant)->get(route('ideas.show', $idea));
        $pageResponse->assertOk();
        $pageResponse->assertSee('Application Pending Review');
    }

    public function test_already_applied_user_cannot_reapply_and_receives_clean_error(): void
    {
        $owner = User::factory()->create();
        $applicant = User::factory()->create();
        $category = $this->createCategory();

        $idea = Idea::query()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Open Data Science Learning Hub',
            'mission_statement' => 'Community-maintained tutorials.',
            'target_hours' => '40.00',
            'status' => 'open',
        ]);

        IdeaCollaborator::query()->create([
            'idea_id' => $idea->id,
            'user_id' => $applicant->id,
            'role_offered' => 'Initial Role',
            'hours_pledged' => '10.00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($applicant)
            ->from(route('ideas.show', $idea))
            ->post(route('ideas.collaborators.store', $idea), [
                'role_offered' => 'Duplicate Role',
                'hours_pledged' => '15',
            ]);

        $response->assertRedirect(route('ideas.show', $idea));
        $response->assertSessionHas('error', 'You have already applied to this idea.');
        $response->assertStatus(302); // Not 500!
    }

    public function test_idea_owner_cannot_join_own_idea_as_collaborator(): void
    {
        $owner = User::factory()->create();
        $category = $this->createCategory();

        $idea = Idea::query()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Open Data Science Learning Hub',
            'mission_statement' => 'Owner cannot join as collaborator.',
            'target_hours' => '40.00',
            'status' => 'open',
        ]);

        $response = $this->actingAs($owner)
            ->post(route('ideas.collaborators.store', $idea), [
                'role_offered' => 'Owner as Collaborator',
                'hours_pledged' => '10',
            ]);

        $response->assertForbidden();
    }

    public function test_joining_as_collaborator_requires_positive_pledged_hours(): void
    {
        $owner = User::factory()->create();
        $applicant = User::factory()->create();
        $category = $this->createCategory();

        $idea = Idea::query()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Open Data Science Learning Hub',
            'mission_statement' => 'Testing hours validation.',
            'target_hours' => '40.00',
            'status' => 'open',
        ]);

        $response = $this->actingAs($applicant)
            ->from(route('ideas.show', $idea))
            ->post(route('ideas.collaborators.store', $idea), [
                'role_offered' => 'Helper',
                'hours_pledged' => '0',
            ]);

        $response->assertRedirect(route('ideas.show', $idea));
        $response->assertSessionHasErrors('hours_pledged');
    }

    public function test_accepted_collaborator_sees_active_collaborator_badge(): void
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $category = $this->createCategory();

        $idea = Idea::query()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Open Data Science Learning Hub',
            'mission_statement' => 'Check active status.',
            'target_hours' => '40.00',
            'status' => 'open',
        ]);

        IdeaCollaborator::query()->create([
            'idea_id' => $idea->id,
            'user_id' => $collaborator->id,
            'role_offered' => 'Data Engineer',
            'hours_pledged' => '10.00',
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($collaborator)->get(route('ideas.show', $idea));
        $response->assertOk();
        $response->assertSee('Active Collaborator');
        $response->assertDontSee('JOIN AS COLLABORATOR');
    }

    public function test_declined_collaborator_sees_declined_badge(): void
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $category = $this->createCategory();

        $idea = Idea::query()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Open Data Science Learning Hub',
            'mission_statement' => 'Check declined status.',
            'target_hours' => '40.00',
            'status' => 'open',
        ]);

        IdeaCollaborator::query()->create([
            'idea_id' => $idea->id,
            'user_id' => $collaborator->id,
            'role_offered' => 'Data Engineer',
            'hours_pledged' => '10.00',
            'status' => 'declined',
        ]);

        $response = $this->actingAs($collaborator)->get(route('ideas.show', $idea));
        $response->assertOk();
        $response->assertSee('Application Declined');
        $response->assertDontSee('JOIN AS COLLABORATOR');
    }
}
