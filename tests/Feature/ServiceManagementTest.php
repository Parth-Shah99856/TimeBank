<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_their_own_service(): void
    {
        $user = User::factory()->create();
        $category = $this->activeCategory('Design', 'design');

        $response = $this->actingAs($user)->postJson(route('services.store'), [
            'user_id' => 999,
            'category_id' => $category->id,
            'title' => 'Logo Review',
            'description' => 'Review your logo drafts',
            'hourly_rate' => '2.50',
            'tags' => ['branding', 'feedback'],
            'is_active' => true,
        ]);

        $response->assertCreated();

        $service = Service::query()->firstOrFail();

        $this->assertSame($user->id, $service->user_id);
        $this->assertNotSame(999, $service->user_id);
        $this->assertSame($category->id, $service->category_id);
        $this->assertSame(['branding', 'feedback'], $service->tags);
    }

    public function test_hourly_rate_must_be_positive_when_creating_service(): void
    {
        $user = User::factory()->create();
        $category = $this->activeCategory('Writing', 'writing');

        $response = $this->actingAs($user)->postJson(route('services.store'), [
            'category_id' => $category->id,
            'title' => 'Proofreading',
            'description' => 'Proofread text',
            'hourly_rate' => '0.00',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('hourly_rate');
    }

    public function test_user_can_update_only_their_own_service(): void
    {
        $user = User::factory()->create();
        $category = $this->activeCategory('Coaching', 'coaching');
        $service = Service::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Career Coaching',
            'description' => 'Career support',
            'hourly_rate' => '3.00',
            'tags' => ['career'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->patchJson(route('services.update', $service), [
            'user_id' => User::factory()->create()->id,
            'category_id' => $category->id,
            'title' => 'Career Coaching Plus',
            'description' => 'Career support and CV review',
            'hourly_rate' => '4.00',
            'tags' => ['career', 'cv'],
            'is_active' => false,
        ]);

        $response->assertOk();
        $service->refresh();

        $this->assertSame($user->id, $service->user_id);
        $this->assertSame('Career Coaching Plus', $service->title);
        $this->assertSame('4.00', $service->hourly_rate);
        $this->assertFalse($service->is_active);
    }

    public function test_user_cannot_update_another_users_service(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = $this->activeCategory('Audio', 'audio');
        $service = Service::query()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Podcast Review',
            'description' => 'Review your episode',
            'hourly_rate' => '2.00',
            'tags' => ['audio'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($otherUser)->patchJson(route('services.update', $service), [
            'category_id' => $category->id,
            'title' => 'Tampered',
            'description' => 'Tampered',
            'hourly_rate' => '9.00',
            'is_active' => false,
        ]);

        $response->assertForbidden();
        $this->assertSame('Podcast Review', $service->fresh()->title);
    }

    public function test_user_can_delete_only_their_own_service(): void
    {
        $user = User::factory()->create();
        $category = $this->activeCategory('Translation', 'translation');
        $service = Service::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Translate Text',
            'description' => 'Translate short text',
            'hourly_rate' => '1.50',
            'tags' => ['language'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('services.destroy', $service));

        $response->assertNoContent();
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_user_cannot_delete_another_users_service(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = $this->activeCategory('Mentoring', 'mentoring');
        $service = Service::query()->create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Mentoring Session',
            'description' => 'Mentoring support',
            'hourly_rate' => '2.00',
            'tags' => ['mentoring'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($otherUser)->delete(route('services.destroy', $service));

        $response->assertForbidden();
        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_inactive_services_do_not_appear_in_public_browsing(): void
    {
        $user = User::factory()->create();
        $activeCategory = $this->activeCategory('Design Browse', 'design-browse');
        $inactiveCategory = Category::query()->create([
            'name' => 'Archived',
            'slug' => 'archived',
            'description' => null,
            'icon' => null,
            'is_active' => false,
        ]);

        $visibleService = Service::query()->create([
            'user_id' => $user->id,
            'category_id' => $activeCategory->id,
            'title' => 'Visible Service',
            'description' => 'Visible description',
            'hourly_rate' => '1.00',
            'tags' => ['visible'],
            'is_active' => true,
        ]);

        Service::query()->create([
            'user_id' => $user->id,
            'category_id' => $activeCategory->id,
            'title' => 'Inactive Service',
            'description' => 'Inactive description',
            'hourly_rate' => '1.00',
            'tags' => ['hidden'],
            'is_active' => false,
        ]);

        Service::query()->create([
            'user_id' => $user->id,
            'category_id' => $inactiveCategory->id,
            'title' => 'Inactive Category Service',
            'description' => 'Hidden because category inactive',
            'hourly_rate' => '1.00',
            'tags' => ['hidden'],
            'is_active' => true,
        ]);

        $response = $this->getJson(route('services.index'));

        $response->assertOk();
        $response->assertJsonFragment(['title' => $visibleService->title]);
        $response->assertJsonMissing(['title' => 'Inactive Service']);
        $response->assertJsonMissing(['title' => 'Inactive Category Service']);
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
