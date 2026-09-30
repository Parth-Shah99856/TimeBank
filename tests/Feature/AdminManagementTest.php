<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\Idea;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectTask;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AdminUserDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin-test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->user = User::factory()->create([
            'role' => 'user',
            'email' => 'user-test@example.com',
            'email_verified_at' => now(),
        ]);
    }

    private function makeCategory(string $name = 'Design', string $slug = 'design'): Category
    {
        return Category::firstOrCreate(['slug' => $slug], [
            'name' => $name,
            'slug' => $slug,
            'icon' => 'category',
            'color' => '#5de6ff',
            'is_active' => true,
        ]);
    }

    private function makeService(User $owner, Category $category, string $title = 'Test Service'): Service
    {
        return Service::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => $title,
            'description' => 'Test description for service.',
            'hourly_rate' => '2.50',
            'tags' => ['Test', 'Skill'],
            'is_active' => true,
        ]);
    }

    private function makeIdea(User $owner, Category $category, string $title = 'Test Idea'): Idea
    {
        return Idea::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => $title,
            'mission_statement' => 'Mission statement for test idea.',
            'target_hours' => 10.0,
            'status' => 'open',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 13.1 & 13.2: Authorization & Guest Protection
    // ─────────────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_admin_users(): void
    {
        $response = $this->get(route('admin.users.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_admin_services(): void
    {
        $response = $this->get(route('admin.services.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_admin_maintenance(): void
    {
        $response = $this->get(route('admin.maintenance'));
        $response->assertRedirect(route('login'));
    }

    public function test_normal_user_receives_403_on_admin_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.index'));
        $response->assertForbidden();
    }

    public function test_normal_user_receives_403_on_admin_users(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.users.index'));
        $response->assertForbidden();
    }

    public function test_normal_user_receives_403_on_admin_services(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.services.index'));
        $response->assertForbidden();
    }

    public function test_normal_user_receives_403_on_admin_maintenance(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.maintenance'));
        $response->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 13.3: Admin Dashboard
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_can_access_admin_dashboard(): void
    {
        $this->makeCategory('Architecture', 'architecture');

        $response = $this->actingAs($this->admin)->get(route('admin.index'));
        $response->assertOk();
        $response->assertSee('Platform Control');
        $response->assertSee('Global Skill Liquidity');
    }

    public function test_admin_can_access_admin_dashboard_alias(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('Platform Control');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 13.4 & 13.5: User Listing & Search
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_can_view_users_list(): void
    {
        $target = User::factory()->create(['name' => 'Target Tester', 'email' => 'target@tester.local']);

        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $response->assertOk();
        $response->assertSee('Target Tester');
        $response->assertSee('target@tester.local');
    }

    public function test_user_search_by_name_and_email_works(): void
    {
        $alice = User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@test.local']);
        $bob = User::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@test.local']);

        // Search by name
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['search' => 'Alice']));
        $response->assertOk();
        $response->assertSee('Alice Wonder');
        $response->assertDontSee('Bob Builder');

        // Search by email
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['search' => 'bob@test.local']));
        $response->assertOk();
        $response->assertSee('Bob Builder');
        $response->assertDontSee('Alice Wonder');
    }

    public function test_user_filter_by_role_works(): void
    {
        $normalUser = User::factory()->create(['name' => 'Normal Unique Person', 'role' => 'user']);

        $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['role' => 'admin']));
        $response->assertOk();
        $response->assertSee($this->admin->name);
        $response->assertDontSee('Normal Unique Person');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 13.6: User Details
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_can_view_user_details(): void
    {
        $category = $this->makeCategory();
        $target = User::factory()->create(['name' => 'Detailed User']);
        $service = $this->makeService($target, $category, 'Special Skill');

        $response = $this->actingAs($this->admin)->get(route('admin.users.show', $target));
        $response->assertOk();
        $response->assertSee('Detailed User');
        $response->assertSee('Special Skill');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 13.7 & 13.8: Safe User Deletion
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin), [
            'confirm' => 'DELETE',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_admin_must_provide_explicit_delete_confirmation_word(): void
    {
        $target = User::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $target), [
            'confirm' => 'WRONG',
        ]);

        $response->assertSessionHasErrors('confirm');
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_admin_can_view_user_delete_confirmation_page(): void
    {
        $target = User::factory()->create(['name' => 'Delete Me Soon']);

        $response = $this->actingAs($this->admin)->get(route('admin.users.delete', $target));
        $response->assertOk();
        $response->assertSee('Delete User Account');
        $response->assertSee('Delete Me Soon');
        $response->assertSee('DELETE');
    }

    public function test_admin_can_safely_delete_a_user_and_handle_relationships(): void
    {
        $category = $this->makeCategory();
        $target = User::factory()->create(['name' => 'To Be Deleted', 'email' => 'delete-target@test.local']);

        // 1. Service owned by target
        $service = $this->makeService($target, $category, 'Service To Cascade');

        // 2. Service request involving target
        $request = ServiceRequest::create([
            'service_id' => $service->id,
            'category_id' => $category->id,
            'requester_id' => $this->user->id,
            'provider_id' => $target->id,
            'title' => 'Test Service Request',
            'project_scope' => 'Test scope details',
            'estimated_hours' => 2.0,
            'total_credits' => 5.0,
            'status' => 'pending',
        ]);

        // 3. Immutable transaction referencing target
        $tx = Transaction::create([
            'transaction_code' => 'TX-TEST-DELE-1234',
            'from_user_id' => $target->id,
            'to_user_id' => $this->user->id,
            'amount' => 5.00,
            'type' => Transaction::TYPE_SIGNUP_BONUS,
            'description' => 'Test Transaction',
            'created_at' => now(),
        ]);

        // 4. Idea owned by target
        $idea = $this->makeIdea($target, $category, 'Target Idea');

        // 5. Project led by target
        $project = Project::create([
            'title' => 'Target Project',
            'description' => 'Test Project',
            'status' => 'active',
            'lead_user_id' => $target->id,
            'category_id' => $category->id,
            'target_hours' => 20.0,
            'idea_id' => $idea->id,
        ]);
        $task = ProjectTask::create([
            'project_id' => $project->id,
            'title' => 'Project Task',
            'status' => 'pending',
        ]);
        $member = ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $this->user->id,
            'role' => 'contributor',
        ]);

        // Execute deletion
        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $target), [
            'confirm' => 'DELETE',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status');

        // Target user is deleted
        $this->assertDatabaseMissing('users', ['id' => $target->id]);

        // Service is deleted via cascade
        $this->assertDatabaseMissing('services', ['id' => $service->id]);

        // Led project and its tasks/members are deleted
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('project_tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('project_members', ['id' => $member->id]);

        // Immutable transaction still exists, but FK was safely nullified
        $this->assertDatabaseHas('transactions', [
            'id' => $tx->id,
            'from_user_id' => null,
            'to_user_id' => $this->user->id,
        ]);

        // Audit log was recorded
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'user.deleted',
            'target_type' => 'user',
            'target_id' => $target->id,
        ]);
    }

    public function test_failed_user_deletion_rolls_back_cleanly(): void
    {
        $target = User::factory()->create(['name' => 'Rollback Target']);

        $mockService = $this->partialMock(AdminUserDeletionService::class, function ($mock) {
            $mock->shouldReceive('delete')->andThrow(new \RuntimeException('Simulated DB Failure'));
        });

        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $target), [
            'confirm' => 'DELETE',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 13.11 - 13.13: Service Management & Deletion
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_can_view_services_list(): void
    {
        $category = $this->makeCategory('Cybersecurity', 'cybersecurity');
        $service = $this->makeService($this->user, $category, 'Quantum Cryptography');

        $response = $this->actingAs($this->admin)->get(route('admin.services.index'));
        $response->assertOk();
        $response->assertSee('Quantum Cryptography');
    }

    public function test_admin_can_view_service_detail(): void
    {
        $category = $this->makeCategory();
        $service = $this->makeService($this->user, $category, 'Distributed Storage');

        $response = $this->actingAs($this->admin)->get(route('admin.services.show', $service));
        $response->assertOk();
        $response->assertSee('Distributed Storage');
        $response->assertSee($this->user->name);
    }

    public function test_admin_can_view_service_delete_confirmation(): void
    {
        $category = $this->makeCategory();
        $service = $this->makeService($this->user, $category, 'Inappropriate Test Service');

        $response = $this->actingAs($this->admin)->get(route('admin.services.delete', $service));
        $response->assertOk();
        $response->assertSee('Delete Service');
        $response->assertSee('Inappropriate Test Service');
    }

    public function test_admin_can_delete_a_service_safely(): void
    {
        $category = $this->makeCategory();
        $service = $this->makeService($this->user, $category, 'Service To Remove');

        $response = $this->actingAs($this->admin)->delete(route('admin.services.destroy', $service), [
            'confirm' => 'DELETE',
        ]);

        $response->assertRedirect(route('admin.services.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('services', ['id' => $service->id]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'service.deleted',
            'target_type' => 'service',
            'target_id' => $service->id,
        ]);
    }

    public function test_normal_user_cannot_delete_services_via_admin_endpoint(): void
    {
        $category = $this->makeCategory();
        $service = $this->makeService($this->user, $category);

        $response = $this->actingAs($this->user)->delete(route('admin.services.destroy', $service), [
            'confirm' => 'DELETE',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Phase 13.9: Maintenance Page
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_can_view_maintenance_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.maintenance'));
        $response->assertOk();
        $response->assertSee('Maintenance');
        $response->assertSee('Application Health');
    }
}
