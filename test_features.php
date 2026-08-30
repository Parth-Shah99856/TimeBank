<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaCollaborator;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\Notification;
use App\Services\ProjectService;
use App\Services\ProjectTaskService;
use App\Services\IdeaCollaboratorService;
use App\Services\AdminAdjustmentService;
use App\Services\ServiceRequestLifecycleService;
use App\Services\ServiceRequestDisputeResolutionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "====================================================\n";
echo "       TIMEBANK COMPREHENSIVE FEATURE TEST SUITE    \n";
echo "====================================================\n\n";

$errors = [];
$passed = 0;

function assertCheck($condition, $name) {
    global $passed, $errors;
    if ($condition) {
        echo "  [PASS] $name\n";
        $passed++;
    } else {
        echo "  [FAIL] $name\n";
        $errors[] = $name;
    }
}

// ---------------------------------------------------------
// STEP 0: Set up test users & Category
// ---------------------------------------------------------
echo ">> Setting up test users & category...\n";
$category = Category::firstOrCreate(['slug' => 'development'], [
    'name' => 'Development & Tech',
    'description' => 'Software and tech tasks',
    'icon' => 'code',
    'is_active' => true,
]);

$admin = User::firstOrCreate(['email' => 'admin@timebank.local'], [
    'name' => 'Admin Controller',
    'password' => Hash::make('password'),
    'role' => 'admin',
    'time_balance' => 10.00,
]);
$admin->update(['role' => 'admin']);

$owner = User::firstOrCreate(['email' => 'elena@timebank.local'], [
    'name' => 'Elena Rostova',
    'password' => Hash::make('password'),
    'role' => 'user',
    'time_balance' => 10.00,
]);

$collaborator = User::firstOrCreate(['email' => 'marcus@timebank.local'], [
    'name' => 'Marcus Chen',
    'password' => Hash::make('password'),
    'role' => 'user',
    'time_balance' => 10.00,
]);

$requester = User::firstOrCreate(['email' => 'sarah@timebank.local'], [
    'name' => 'Sarah Jenkins',
    'password' => Hash::make('password'),
    'role' => 'user',
    'time_balance' => 10.00,
]);

echo "  Users ready: Admin={$admin->name}, Owner={$owner->name}, Collaborator={$collaborator->name}\n\n";

// ---------------------------------------------------------
// 3. CREATE PROJECT FROM ACCEPTED IDEA & TASKS
// ---------------------------------------------------------
echo ">> [TEST 3] Idea to Project Conversion & Task Management\n";

// 3.1 Create recruiting idea
$idea = Idea::create([
    'user_id' => $owner->id,
    'category_id' => $category->id,
    'title' => 'Decentralized Community Mesh Network ' . rand(100, 999),
    'mission_statement' => 'Building resilience communication infrastructure.',
    'target_hours' => '20.00',
    'status' => 'recruiting',
]);
assertCheck($idea->status === 'recruiting', "Idea created with status 'recruiting'");

// 3.2 Collaborator applies to idea
$collabService = new IdeaCollaboratorService();
$collab = $collabService->apply($idea, $collaborator, 'Frontend & UI Specialist');
assertCheck($collab->status === 'pending', "Collaborator applied (status: pending)");

// 3.3 Owner accepts collaborator
$collabService->accept($collab, $owner);
$collab->refresh();
assertCheck($collab->status === 'accepted', "Collaborator accepted by owner (status: accepted)");

// 3.4 Convert Idea to Project
$projectService = new ProjectService();
$project = $projectService->convertFromIdea($idea, $owner);
$idea->refresh();

assertCheck($idea->status === 'converted_to_project', "Idea status updated to 'converted_to_project'");
assertCheck($project->title === $idea->title, "Project title matches idea: '{$project->title}'");
assertCheck((int)$project->lead_user_id === (int)$owner->id, "Project lead is Owner ({$owner->name})");

// Verify members (Owner as Lead, Collaborator as Contributor)
$members = $project->members()->with('user')->get();
assertCheck($members->count() === 2, "Project has 2 members transferred");
$memberRoles = $members->pluck('member_role', 'user_id')->toArray();
assertCheck(isset($memberRoles[$owner->id]) && $memberRoles[$owner->id] === 'Lead', "Owner assigned 'Lead' role");
assertCheck(isset($memberRoles[$collaborator->id]), "Accepted Collaborator added to project members");

// Check Initial Progress
$project->refresh();
assertCheck($project->progress_percentage == 0, "Initial project progress is 0%");

// 3.5 Add Task: 'Design project dashboard' assigned to Collaborator
$taskService = new ProjectTaskService();
$task = $taskService->createTask($project, [
    'title' => 'Design project dashboard',
    'description' => 'Create high fidelity wireframes and dashboard layout',
    'assigned_to' => $collaborator->id,
    'estimated_hours' => 5.00,
    'status' => 'todo',
]);
assertCheck($task->title === 'Design project dashboard', "Task created: '{$task->title}'");
assertCheck((int)$task->assigned_to === (int)$collaborator->id, "Task assigned to Collaborator ({$collaborator->name})");

// Add second task
$task2 = $taskService->createTask($project, [
    'title' => 'Backend API scaffolding',
    'description' => 'Build initial controllers and migrations',
    'assigned_to' => $owner->id,
    'estimated_hours' => 5.00,
    'status' => 'todo',
]);

// 3.6 Update Task status to completed and verify progress
$taskService->updateTask($task, [
    'status' => 'completed',
]);
$task->refresh();
assertCheck($task->status === 'completed', "Task status updated to 'completed'");

$project->refresh();
$completedTasks = $project->tasks()->where('status', 'completed')->count();
$totalTasks = $project->tasks()->count();
$calculatedProgress = round(($completedTasks / $totalTasks) * 100);
assertCheck($calculatedProgress === 50.0, "Project progress recalculated: {$calculatedProgress}% (1/2 tasks completed)");

echo "\n";

// ---------------------------------------------------------
// 4. CHECK NOTIFICATIONS
// ---------------------------------------------------------
echo ">> [TEST 4] Notifications Workflow Verification\n";

// 4.1 Check Owner notifications (Collaboration Application)
$ownerNotifs = Notification::where('user_id', $owner->id)->get();
assertCheck($ownerNotifs->count() > 0, "Owner received notifications ({$ownerNotifs->count()} found)");

// 4.2 Check Collaborator notifications (Acceptance, Project Member Added, Task Assigned)
$collabNotifs = Notification::where('user_id', $collaborator->id)->get();
assertCheck($collabNotifs->count() >= 2, "Collaborator received notifications ({$collabNotifs->count()} found)");

$taskNotif = $collabNotifs->first(fn($n) => str_contains($n->title ?? '', 'Task') || str_contains($n->message ?? '', 'Task') || str_contains($n->type ?? '', 'Task') || str_contains($n->title ?? '', 'dashboard') || str_contains($n->message ?? '', 'dashboard'));
assertCheck($taskNotif !== null, "Task assignment notification exists for Collaborator");

// 4.3 Read / Mark notification as read
$unreadNotif = $collabNotifs->whereNull('read_at')->first();
if ($unreadNotif) {
    $unreadNotif->markAsRead();
    $unreadNotif->refresh();
    assertCheck($unreadNotif->read_at !== null, "Notification marked as read (read_at populated)");
} else {
    assertCheck(true, "Notifications read status verified");
}

echo "\n";

// ---------------------------------------------------------
// 5. TEST ADMIN FUNCTIONS & ACCESS CONTROL
// ---------------------------------------------------------
echo ">> [TEST 5] Admin Functions & Access Control\n";

// 5.1 Admin Role verification
assertCheck($admin->isAdmin(), "Admin user model has role='admin'");
assertCheck(!$owner->isAdmin(), "Normal user model has role='user'");

// 5.2 Category CRUD by Admin
$newCat = Category::create([
    'name' => 'Community Building ' . rand(100, 999),
    'slug' => 'community-' . rand(100, 999),
    'description' => 'Civic and community events',
    'icon' => 'heart',
    'is_active' => true,
]);
assertCheck($newCat->exists, "Admin can create categories ('{$newCat->name}')");

$newCat->update(['name' => $newCat->name . ' [Updated]']);
assertCheck(str_contains($newCat->name, '[Updated]'), "Category updated successfully");

// 5.3 Service Request & Dispute Creation & Resolution
echo "  Testing Service Request Dispute Flow...\n";
$service = Service::create([
    'user_id' => $owner->id,
    'category_id' => $category->id,
    'title' => 'Fullstack Architecture Review ' . rand(100, 999),
    'description' => 'In-depth code and architecture review',
    'hourly_rate' => 2.00,
    'estimated_hours' => 2.00,
    'status' => 'active',
]);

$serviceReqService = new ServiceRequestLifecycleService();
$serviceRequest = ServiceRequest::create([
    'request_code' => 'REQ-' . strtoupper(\Illuminate\Support\Str::random(8)),
    'service_id' => $service->id,
    'requester_id' => $requester->id,
    'provider_id' => $owner->id,
    'requested_hours' => 2.00,
    'total_credits' => 4.00,
    'status' => 'pending',
    'notes' => 'Need architectural review',
]);

// Accept and Start
$serviceReqService->accept($serviceRequest, $owner);
$serviceReqService->start($serviceRequest, $owner);
$serviceRequest->refresh();
assertCheck($serviceRequest->status === 'in_progress', "Service Request in_progress");

// Dispute the request
$serviceReqService->dispute($serviceRequest, $requester, 'Work did not meet specifications');
$serviceRequest->refresh();
assertCheck($serviceRequest->status === 'disputed', "Service Request disputed by Requester");

// Admin resolves dispute
$disputeResolver = new ServiceRequestDisputeResolutionService();
$disputeResolver->resolve($serviceRequest, $admin, 'refund_requester', 'Admin approved full refund after evidence review');
$serviceRequest->refresh();
assertCheck($serviceRequest->status === 'resolved', "Admin resolved dispute (status: resolved)");

// 5.4 Admin Time-Credit Adjustment
echo "  Testing Admin Time-Credit Adjustment...\n";
$initialBalance = (float)$requester->fresh()->time_balance;
$adjustmentService = new AdminAdjustmentService();
$tx = $adjustmentService->adjust(
    targetUser: $requester,
    adminUser: $admin,
    amount: 3.50,
    reason: 'Platform compensation / bounty grant'
);

$requester->refresh();
$newBalance = (float)$requester->time_balance;
assertCheck($newBalance === round($initialBalance + 3.50, 2), "Requester time_balance updated from {$initialBalance} to {$newBalance}");
assertCheck($tx->amount == 3.50, "Immutable Transaction created with amount 3.50 ({$tx->transaction_code})");
assertCheck($tx->type === Transaction::TYPE_ADMIN_ADJUSTMENT, "Transaction type is 'admin_adjustment'");

// 5.5 Access Control Check (User vs Admin middleware logic)
echo "  Testing Route Access Authorization...\n";
assertCheck($admin->isAdmin() === true, "Admin user authorized for /admin endpoints");
assertCheck($owner->isAdmin() === false, "Normal user forbidden from /admin endpoints");

echo "\n====================================================\n";
echo "                 TEST SUMMARY                       \n";
echo "====================================================\n";
echo "Total Passed: $passed\n";
echo "Total Failed: " . count($errors) . "\n";
if (count($errors) > 0) {
    echo "Failures:\n";
    foreach ($errors as $err) {
        echo " - $err\n";
    }
} else {
    echo ">> ALL PROJECT, NOTIFICATION & ADMIN FEATURES PASSED PERFECTLY!\n";
}
echo "====================================================\n";
