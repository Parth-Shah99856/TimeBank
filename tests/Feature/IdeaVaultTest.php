<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaCollaborator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaVaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_idea(): void
    {
        $user = User::factory()->create();
        $category = $this->activeCategory('Community', 'community');

        $response = $this->actingAs($user)->postJson(route('ideas.store'), [
            'user_id' => 999,
            'category_id' => $category->id,
            'title' => 'Tool Library',
            'mission_statement' => 'Create a shared tool library.',
            'target_hours' => '25.00',
            'required_skills' => ['coordination', 'outreach'],
            'status' => 'recruiting',
        ]);

        $response->assertCreated();

        $idea = Idea::query()->firstOrFail();
        $this->assertSame($user->id, $idea->user_id);
        $this->assertNotSame(999, $idea->user_id);
        $this->assertSame(['coordination', 'outreach'], $idea->required_skills);
    }

    public function test_idea_owner_can_update_their_own_idea(): void
    {
        $owner = User::factory()->create();
        $category = $this->activeCategory('Environment', 'environment');
        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Garden Project',
            'mission_statement' => 'Start a community garden.',
            'target_hours' => '30.00',
            'required_skills' => ['gardening'],
            'status' => 'open',
        ]);

        $response = $this->actingAs($owner)->patchJson(route('ideas.update', $idea), [
            'user_id' => User::factory()->create()->id,
            'category_id' => $category->id,
            'title' => 'Garden Project Expanded',
            'mission_statement' => 'Start and maintain a community garden.',
            'target_hours' => '40.00',
            'required_skills' => ['gardening', 'fundraising'],
            'status' => 'recruiting',
        ]);

        $response->assertOk();
        $idea->refresh();

        $this->assertSame($owner->id, $idea->user_id);
        $this->assertSame('Garden Project Expanded', $idea->title);
        $this->assertSame('recruiting', $idea->status);
    }

    public function test_non_owner_cannot_update_idea(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = $this->activeCategory('Skills', 'skills');
        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Skill Exchange Fair',
            'mission_statement' => 'Host a fair for skill exchange.',
            'target_hours' => '12.00',
            'required_skills' => ['events'],
            'status' => 'open',
        ]);

        $response = $this->actingAs($otherUser)->patchJson(route('ideas.update', $idea), [
            'category_id' => $category->id,
            'title' => 'Tampered',
            'mission_statement' => 'Tampered',
            'target_hours' => '99.00',
            'required_skills' => ['tamper'],
            'status' => 'archived',
        ]);

        $response->assertForbidden();
    }

    public function test_idea_owner_can_delete_their_own_idea(): void
    {
        $owner = User::factory()->create();
        $category = $this->activeCategory('Education', 'education');
        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Study Pods',
            'mission_statement' => 'Create peer study pods.',
            'target_hours' => '20.00',
            'required_skills' => ['teaching'],
            'status' => 'open',
        ]);

        $response = $this->actingAs($owner)->delete(route('ideas.destroy', $idea));

        $response->assertNoContent();
        $this->assertDatabaseMissing('ideas', ['id' => $idea->id]);
    }

    public function test_users_can_browse_ideas(): void
    {
        $user = User::factory()->create();
        $category = $this->activeCategory('Health', 'health');
        $idea = $user->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Walking Club',
            'mission_statement' => 'Organize a neighborhood walking club.',
            'target_hours' => '8.00',
            'required_skills' => ['organizing'],
            'status' => 'open',
        ]);

        $response = $this->getJson(route('ideas.index'));

        $response->assertOk();
        $response->assertJsonFragment(['title' => $idea->title]);
    }

    public function test_user_can_express_interest_in_an_idea(): void
    {
        $owner = User::factory()->create();
        $applicant = User::factory()->create();
        $category = $this->activeCategory('Arts', 'arts');
        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Community Mural',
            'mission_statement' => 'Paint a mural with the community.',
            'target_hours' => '18.00',
            'required_skills' => ['painting'],
            'status' => 'open',
        ]);

        $response = $this->actingAs($applicant)->postJson(route('ideas.collaborators.store', $idea), [
            'user_id' => 999,
            'status' => 'accepted',
            'role_offered' => 'Painter',
            'hours_pledged' => '6.50',
        ]);

        $response->assertCreated();

        $collaborator = IdeaCollaborator::query()->firstOrFail();
        $this->assertSame($applicant->id, $collaborator->user_id);
        $this->assertSame('pending', $collaborator->status);
        $this->assertSame('Painter', $collaborator->role_offered);
    }

    public function test_duplicate_application_for_same_idea_is_prevented(): void
    {
        $owner = User::factory()->create();
        $applicant = User::factory()->create();
        $category = $this->activeCategory('Music', 'music');
        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Choir Program',
            'mission_statement' => 'Start a neighborhood choir.',
            'target_hours' => '15.00',
            'required_skills' => ['singing'],
            'status' => 'open',
        ]);

        $this->actingAs($applicant)->postJson(route('ideas.collaborators.store', $idea), [
            'role_offered' => 'Singer',
            'hours_pledged' => '4.00',
        ]);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($applicant)->postJson(route('ideas.collaborators.store', $idea), [
                'role_offered' => 'Singer',
                'hours_pledged' => '5.00',
            ]);
            $this->fail('Expected duplicate application exception was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('You have already applied to this idea.', $exception->getMessage());
        }
    }

    public function test_idea_owner_can_accept_collaborator_application(): void
    {
        $owner = User::factory()->create();
        $applicant = User::factory()->create();
        $category = $this->activeCategory('Civic', 'civic');
        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Neighborhood Watch',
            'mission_statement' => 'Create a safer neighborhood network.',
            'target_hours' => '22.00',
            'required_skills' => ['coordination'],
            'status' => 'recruiting',
        ]);
        $collaborator = $idea->collaborators()->create([
            'user_id' => $applicant->id,
            'role_offered' => 'Coordinator',
            'hours_pledged' => '3.00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->patchJson(route('idea-collaborators.update', $collaborator), [
            'status' => 'accepted',
        ]);

        $response->assertOk();
        $this->assertSame('accepted', $collaborator->fresh()->status);
    }

    public function test_idea_owner_can_decline_collaborator_application(): void
    {
        $owner = User::factory()->create();
        $applicant = User::factory()->create();
        $category = $this->activeCategory('Tech', 'tech');
        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Repair Workshop',
            'mission_statement' => 'Teach basic device repair.',
            'target_hours' => '14.00',
            'required_skills' => ['repair'],
            'status' => 'recruiting',
        ]);
        $collaborator = $idea->collaborators()->create([
            'user_id' => $applicant->id,
            'role_offered' => 'Technician',
            'hours_pledged' => '2.50',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($owner)->patchJson(route('idea-collaborators.update', $collaborator), [
            'status' => 'declined',
        ]);

        $response->assertOk();
        $this->assertSame('declined', $collaborator->fresh()->status);
    }

    public function test_non_owner_cannot_manage_collaborator_application_status(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $applicant = User::factory()->create();
        $category = $this->activeCategory('Outreach', 'outreach');
        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Welcome Packs',
            'mission_statement' => 'Create welcome packs for newcomers.',
            'target_hours' => '9.00',
            'required_skills' => ['packing'],
            'status' => 'recruiting',
        ]);
        $collaborator = $idea->collaborators()->create([
            'user_id' => $applicant->id,
            'role_offered' => 'Volunteer',
            'hours_pledged' => '1.50',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($otherUser)->patchJson(route('idea-collaborators.update', $collaborator), [
            'status' => 'accepted',
        ]);

        $response->assertForbidden();
        $this->assertSame('pending', $collaborator->fresh()->status);
    }

    public function test_authenticated_user_can_create_idea_with_custom_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ideas.store'), [
            'category_id' => 'custom',
            'custom_category' => 'Quantum Computing & Telemetry',
            'title' => 'Quantum Mesh Network',
            'mission_statement' => 'Establishing entangled communication relay nodes.',
            'target_hours' => '50.00',
            'required_skills' => ['quantum physics', 'networking'],
            'status' => 'open',
        ]);

        $response->assertCreated();

        $category = Category::where('name', 'Quantum Computing & Telemetry')->first();
        $this->assertNotNull($category);
        $this->assertSame('quantum-computing-telemetry', $category->slug);
        $this->assertTrue($category->is_active);

        $idea = Idea::where('title', 'Quantum Mesh Network')->first();
        $this->assertNotNull($idea);
        $this->assertSame($category->id, $idea->category_id);
        $this->assertSame($user->id, $idea->user_id);
    }

    public function test_custom_category_appears_on_created_initiative_view(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('ideas.store'), [
            'category_id' => 'custom',
            'custom_category' => 'Biohacking & Genetics',
            'title' => 'Open CRISPR Lab',
            'mission_statement' => 'Democratizing genetic research in community maker spaces.',
            'target_hours' => '80.00',
        ]);

        $idea = Idea::where('title', 'Open CRISPR Lab')->firstOrFail();
        $this->assertSame('Biohacking & Genetics', $idea->category->name);

        $viewResponse = $this->actingAs($user)->get(route('ideas.show', $idea));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Biohacking & Genetics');
        $viewResponse->assertSee('Open CRISPR Lab');
    }

    public function test_custom_category_reuses_existing_category_case_insensitively(): void
    {
        $user = User::factory()->create();
        $existing = $this->activeCategory('Artificial Intelligence', 'artificial-intelligence');

        $response = $this->actingAs($user)->postJson(route('ideas.store'), [
            'category_id' => 'custom',
            'custom_category' => 'artificial intelligence',
            'title' => 'Autonomous Drone Fleet',
            'mission_statement' => 'Deploying autonomous drones for search and rescue.',
            'target_hours' => '35.00',
        ]);

        $response->assertCreated();

        // Ensure no duplicate category was created
        $this->assertSame(1, Category::whereRaw('LOWER(name) = ?', ['artificial intelligence'])->count());

        $idea = Idea::where('title', 'Autonomous Drone Fleet')->firstOrFail();
        $this->assertSame($existing->id, $idea->category_id);
    }

    public function test_empty_custom_category_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ideas.store'), [
            'category_id' => 'custom',
            'custom_category' => '',
            'title' => 'Empty Custom Category Test',
            'mission_statement' => 'This should fail validation.',
            'target_hours' => '10.00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['custom_category']);
    }

    public function test_oversized_custom_category_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ideas.store'), [
            'category_id' => 'custom',
            'custom_category' => str_repeat('A', 101),
            'title' => 'Oversized Category Test',
            'mission_statement' => 'This should fail validation.',
            'target_hours' => '10.00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['custom_category']);
    }

    public function test_invalid_category_id_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ideas.store'), [
            'category_id' => 99999,
            'title' => 'Invalid Category ID Test',
            'mission_statement' => 'This should fail validation.',
            'target_hours' => '10.00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    }

    public function test_unauthenticated_user_cannot_create_idea_with_custom_category(): void
    {
        $response = $this->postJson(route('ideas.store'), [
            'category_id' => 'custom',
            'custom_category' => 'Robotics',
            'title' => 'Guest Initiative',
            'mission_statement' => 'Should be unauthorized.',
            'target_hours' => '10.00',
        ]);

        $response->assertUnauthorized();
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
