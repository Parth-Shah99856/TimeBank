<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_view_idea_detail_with_relationships(): void
    {
        $owner = User::factory()->create();
        $collaboratorUser = User::factory()->create();
        $category = $this->activeCategory();

        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Community Garden',
            'mission_statement' => 'Grow food together',
            'target_hours' => '15.00',
            'required_skills' => ['gardening'],
            'status' => 'recruiting',
        ]);
        $idea->collaborators()->create([
            'user_id' => $collaboratorUser->id,
            'role_offered' => 'Planter',
            'hours_pledged' => '4.00',
            'status' => 'accepted',
        ]);

        $response = $this->getJson(route('ideas.show', $idea));

        $response->assertOk();
        $response->assertJsonPath('id', $idea->id);
        $response->assertJsonPath('category.id', $category->id);
        $response->assertJsonPath('user.id', $owner->id);
        $response->assertJsonCount(1, 'collaborators');
        $response->assertJsonPath('collaborators.0.user.id', $collaboratorUser->id);
    }

    public function test_authenticated_non_owner_can_also_view_idea_detail(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $category = $this->activeCategory();

        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Bike Repair Co-op',
            'mission_statement' => 'Fix bikes for the community',
            'target_hours' => '10.00',
            'required_skills' => ['mechanics'],
            'status' => 'open',
        ]);

        $response = $this->actingAs($viewer)->getJson(route('ideas.show', $idea));

        $response->assertOk();
        $response->assertJsonPath('id', $idea->id);
    }

    public function test_nonexistent_idea_returns_not_found(): void
    {
        $response = $this->getJson(route('ideas.show', ['idea' => 999999]));

        $response->assertNotFound();
    }

    public function test_collaborators_from_other_ideas_are_not_leaked(): void
    {
        $owner = User::factory()->create();
        $category = $this->activeCategory();

        $ideaA = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Idea A',
            'mission_statement' => 'Mission A',
            'target_hours' => '5.00',
            'required_skills' => null,
            'status' => 'open',
        ]);
        $ideaB = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Idea B',
            'mission_statement' => 'Mission B',
            'target_hours' => '5.00',
            'required_skills' => null,
            'status' => 'open',
        ]);
        $ideaB->collaborators()->create([
            'user_id' => User::factory()->create()->id,
            'role_offered' => 'Helper',
            'hours_pledged' => '2.00',
            'status' => 'pending',
        ]);

        $response = $this->getJson(route('ideas.show', $ideaA));

        $response->assertOk();
        $response->assertJsonCount(0, 'collaborators');
    }

    public function test_query_parameters_cannot_change_which_idea_is_returned(): void
    {
        $owner = User::factory()->create();
        $category = $this->activeCategory();

        $idea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Neighborhood Watch',
            'mission_statement' => 'Keep the block safe',
            'target_hours' => '8.00',
            'required_skills' => null,
            'status' => 'open',
        ]);
        $otherIdea = $owner->ideas()->create([
            'category_id' => $category->id,
            'title' => 'Other Idea',
            'mission_statement' => 'Other mission',
            'target_hours' => '8.00',
            'required_skills' => null,
            'status' => 'open',
        ]);

        $response = $this->getJson(route('ideas.show', $idea).'?idea='.$otherIdea->id.'&id='.$otherIdea->id);

        $response->assertOk();
        $response->assertJsonPath('id', $idea->id);
    }

    private function activeCategory(): Category
    {
        return Category::query()->create([
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);
    }
}
