<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Idea;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendViewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Category $category;
    protected Service $service;
    protected Idea $idea;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Architecture & Design',
            'slug' => 'architecture-design',
            'description' => 'System architecture and UI design',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'name' => 'Test Architect',
            'email' => 'architect@test.local',
            'time_balance' => '10.00',
            'role' => 'user',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Controller',
            'email' => 'admin@test.local',
            'time_balance' => '50.00',
            'role' => 'admin',
        ]);

        $this->service = Service::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Advanced Distributed Systems Review',
            'description' => 'Comprehensive architecture review of distributed nodes.',
            'hourly_rate' => '2.50',
            'tags' => ['Distributed', 'Architecture', 'Go'],
            'is_active' => true,
        ]);

        $this->idea = Idea::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'OpenMesh Wireless Grid',
            'mission_statement' => 'Decentralized mesh networking for rural communities.',
            'target_hours' => '250.00',
            'hours_funded' => '50.00',
            'required_skills' => ['Rust', 'Hardware', 'RF'],
            'status' => 'recruiting',
        ]);

        $this->project = Project::create([
            'idea_id' => $this->idea->id,
            'category_id' => $this->category->id,
            'lead_user_id' => $this->user->id,
            'title' => 'OpenMesh Pilot Deployment',
            'description' => 'First phase deployment of 50 open mesh radio units.',
            'status' => 'active',
            'target_hours' => '500.00',
            'hours_contributed' => '150.00',
        ]);
    }

    public function test_public_landing_page_renders(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Give Time.');
        $response->assertSee('Build Ideas.');
    }

    public function test_public_login_page_renders(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSee('INITIALIZE CONNECTION');
    }

    public function test_public_register_page_renders(): void
    {
        $response = $this->get('/register');
        $response->assertOk();
        $response->assertSee('Initialize Account');
    }

    public function test_public_services_marketplace_renders(): void
    {
        $response = $this->get('/services');
        $response->assertOk();
        $response->assertSee('Explore Services');
        $response->assertSee('Advanced Distributed Systems Review');
    }

    public function test_public_service_details_renders(): void
    {
        $response = $this->get('/services/' . $this->service->id);
        $response->assertOk();
        $response->assertSee('Advanced Distributed Systems Review');
        $response->assertSee('Test Architect');
    }

    public function test_public_ideavault_renders(): void
    {
        $response = $this->get('/ideas');
        $response->assertOk();
        $response->assertSee('Vault');
        $response->assertSee('OpenMesh Wireless Grid');
    }

    public function test_public_idea_details_renders(): void
    {
        $response = $this->get('/ideas/' . $this->idea->id);
        $response->assertOk();
        $response->assertSee('OpenMesh Wireless Grid');
        $response->assertSee('Decentralized mesh networking');
    }

    public function test_public_leaderboard_renders(): void
    {
        $response = $this->get('/leaderboard');
        $response->assertOk();
        $response->assertSee('Community Leaderboard');
    }

    public function test_authenticated_dashboard_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Time Credit Balance');
        $response->assertSee('10.00');
    }

    public function test_authenticated_profile_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/profile');
        $response->assertOk();
        $response->assertSee('Test Architect');
        $response->assertSee('Account Configuration');
    }

    public function test_authenticated_offer_skill_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/services/create');
        $response->assertOk();
        $response->assertSee('Offer a Skill');
        $response->assertSee('PUBLISH SKILL OFFERING');
    }

    public function test_authenticated_my_services_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/my-services');
        $response->assertOk();
        $response->assertSee('My Skills & Offerings');
        $response->assertSee('Advanced Distributed Systems Review');
    }

    public function test_authenticated_service_requests_index_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/service-requests');
        $response->assertOk();
        $response->assertSeeText('My Requests & Exchanges');
    }

    public function test_authenticated_create_service_request_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/service-requests/create?service_id=' . $this->service->id);
        $response->assertOk();
        $response->assertSee('Service Request');
        $response->assertSee('TRANSACTION SUMMARY');
    }

    public function test_authenticated_wallet_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/wallet');
        $response->assertOk();
        $response->assertSee('AVAILABLE TIME CREDITS');
        $response->assertSee('10.0');
    }

    public function test_authenticated_transactions_ledger_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/transactions');
        $response->assertOk();
        $response->assertSee('Net Time Balance');
    }

    public function test_authenticated_create_idea_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/ideas/create');
        $response->assertOk();
        $response->assertSee('Post an Initiative');
    }

    public function test_authenticated_project_details_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/projects/' . $this->project->id);
        $response->assertOk();
        $response->assertSee('OpenMesh Pilot Deployment');
        $response->assertSee('Project Progress');
    }

    public function test_authenticated_notifications_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/notifications');
        $response->assertOk();
        $response->assertSee('Notifications Center');
    }

    public function test_admin_platform_control_renders(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertOk();
        $response->assertSee('Platform Control');
        $response->assertSee('Global Skill Liquidity');
    }

    public function test_admin_categories_management_renders(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/categories');
        $response->assertOk();
        $response->assertSee('Category Domains');
        $response->assertSee('Architecture & Design');
    }

    public function test_non_admin_cannot_access_admin_platform_control(): void
    {
        $response = $this->actingAs($this->user)->get('/admin');
        $response->assertForbidden();
    }

    public function test_non_admin_cannot_access_admin_categories(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/categories');
        $response->assertForbidden();
    }

    public function test_user_can_submit_offer_skill_web_form_and_redirect(): void
    {
        $response = $this->actingAs($this->user)->post('/services', [
            'category_id' => $this->category->id,
            'title' => 'Kubernetes Cluster Architecture',
            'description' => 'Architecting resilient Kubernetes nodes and ingress controllers.',
            'hourly_rate' => '3.00',
            'tags' => ['Kubernetes', 'DevOps', 'Cloud'],
        ]);

        $service = Service::where('title', 'Kubernetes Cluster Architecture')->first();
        $this->assertNotNull($service);
        $response->assertRedirect(route('services.show', $service));
    }

    public function test_user_can_submit_idea_web_form_and_redirect(): void
    {
        $response = $this->actingAs($this->user)->post('/ideas', [
            'category_id' => $this->category->id,
            'title' => 'Decentralized Solar Microgrid',
            'mission_statement' => 'Establishing peer-to-peer renewable microgrids.',
            'target_hours' => '300.00',
            'required_skills' => ['Solar', 'Electrical', 'Firmware'],
        ]);

        $idea = Idea::where('title', 'Decentralized Solar Microgrid')->first();
        $this->assertNotNull($idea);
        $response->assertRedirect(route('ideas.show', $idea));
    }

    public function test_idea_owner_can_convert_idea_to_project_web_form(): void
    {
        $newIdea = Idea::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Decentralized Water Purifier',
            'mission_statement' => 'Community clean water initiative',
            'target_hours' => '100.00',
            'status' => 'recruiting',
        ]);

        $response = $this->actingAs($this->user)->post('/ideas/' . $newIdea->id . '/project');

        $project = Project::where('idea_id', $newIdea->id)->first();
        $this->assertNotNull($project);
        $response->assertRedirect(route('projects.show', $project));
    }

    public function test_project_lead_can_add_member_web_form(): void
    {
        $newMember = User::factory()->create(['name' => 'Collaborator Node']);

        $response = $this->actingAs($this->user)->post('/projects/' . $this->project->id . '/members', [
            'user_id' => $newMember->id,
            'member_role' => 'Hardware Specialist',
        ]);

        $response->assertRedirect(route('projects.show', $this->project));
        $this->assertDatabaseHas('project_members', [
            'project_id' => $this->project->id,
            'user_id' => $newMember->id,
            'member_role' => 'Hardware Specialist',
        ]);
    }

    public function test_project_lead_can_add_task_web_form(): void
    {
        $response = $this->actingAs($this->user)->post('/projects/' . $this->project->id . '/tasks', [
            'title' => 'Fabricate radio antenna modules',
            'description' => 'CNC mill PCB enclosures and test SWR.',
            'target_hours' => '8.00',
            'status' => 'pending',
            'order_index' => 1,
        ]);

        $response->assertRedirect(route('projects.show', $this->project));
        $this->assertDatabaseHas('project_tasks', [
            'project_id' => $this->project->id,
            'title' => 'Fabricate radio antenna modules',
        ]);
    }

    public function test_admin_can_resolve_dispute_web_form(): void
    {
        $provider = User::factory()->create(['time_balance' => '5.00']);
        $serviceRequest = \App\Models\ServiceRequest::create([
            'service_id' => $this->service->id,
            'requester_id' => $this->user->id,
            'provider_id' => $provider->id,
            'category_id' => $this->category->id,
            'title' => 'Disputed Task',
            'project_scope' => 'Deliverable disputed by client',
            'estimated_hours' => '2.00',
            'total_credits' => '5.00',
            'status' => 'disputed',
        ]);

        $response = $this->actingAs($this->admin)->post('/service-requests/' . $serviceRequest->id . '/resolve-dispute', [
            'resolution' => 'completed',
        ]);

        $response->assertRedirect(route('admin.index'));
        $this->assertSame('completed', $serviceRequest->fresh()->status);
    }
}
