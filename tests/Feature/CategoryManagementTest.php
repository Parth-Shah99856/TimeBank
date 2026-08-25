<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson(route('categories.store'), [
            'name' => 'Design',
            'slug' => 'design',
            'description' => 'Design work',
            'icon' => 'palette',
            'is_active' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', [
            'name' => 'Design',
            'slug' => 'design',
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_create_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('categories.store'), [
            'name' => 'Design',
            'slug' => 'design',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_category_and_toggle_active_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create([
            'name' => 'Writing',
            'slug' => 'writing',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patchJson(route('categories.update', $category), [
            'name' => 'Writing & Editing',
            'slug' => 'writing-editing',
            'description' => 'Words and editing',
            'icon' => 'pen',
            'is_active' => false,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Writing & Editing',
            'slug' => 'writing-editing',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create([
            'name' => 'Research',
            'slug' => 'research',
            'description' => null,
            'icon' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('categories.destroy', $category));

        $response->assertNoContent();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
