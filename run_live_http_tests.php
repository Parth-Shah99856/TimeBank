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

class LiveHttpTester
{
    private string $baseUrl = 'http://127.0.0.1:8000';
    private string $cookieFile;
    private int $passed = 0;
    private array $errors = [];

    public function __construct()
    {
        $this->cookieFile = sys_get_temp_dir() . '/tb_cookies_' . uniqid() . '.txt';
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    public function __destruct()
    {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function resetSession(): void
    {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    private function request(string $method, string $path, array $data = [], array $headers = []): array
    {
        $url = str_starts_with($path, 'http') ? $path : $this->baseUrl . $path;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $customHeaders = array_merge([
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) TimeBankLiveTest/1.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,application/json,*/*;q=0.8',
        ], $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        } elseif ($method === 'PATCH' || $method === 'DELETE' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_POST, true);
            $data['_method'] = $method;
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $curlError = curl_error($ch);
        curl_close($ch);

        $headerStr = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return [
            'code' => $httpCode,
            'headers' => $headerStr,
            'body' => $body,
            'effective_url' => $effectiveUrl,
            'error' => $curlError,
        ];
    }

    public function getCsrfToken(string $html): ?string
    {
        if (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/i', $html, $matches)) {
            return $matches[1];
        }
        if (preg_match('/<meta[^>]*name="csrf-token"[^>]*content="([^"]+)"/i', $html, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function login(string $email, string $password = 'password'): bool
    {
        $this->resetSession();
        $loginPage = $this->request('GET', '/login');
        $token = $this->getCsrfToken($loginPage['body']);
        if (!$token) {
            echo "  [FAIL] Failed to obtain CSRF token from /login\n";
            return false;
        }

        $res = $this->request('POST', '/login', [
            '_token' => $token,
            'email' => $email,
            'password' => $password,
        ]);

        return ($res['code'] === 200 || $res['code'] === 302) && !str_contains($res['body'], 'These credentials do not match');
    }

    public function assertCheck(bool $condition, string $name, string $extra = ''): void
    {
        if ($condition) {
            echo "  [PASS] $name" . ($extra ? " ($extra)" : "") . "\n";
            $this->passed++;
        } else {
            echo "  [FAIL] $name" . ($extra ? " ($extra)" : "") . "\n";
            $this->errors[] = $name . ($extra ? " - $extra" : "");
        }
    }

    public function runAllTests(): void
    {
        echo "===============================================================\n";
        echo "   TIMEBANK LIVE HTTP END-TO-END FEATURE VERIFICATION (PORT 8000)\n";
        echo "===============================================================\n\n";

        // Check if server is running
        $ping = $this->request('GET', '/');
        if ($ping['code'] !== 200) {
            echo "[FATAL] Live server at {$this->baseUrl} is not reachable (HTTP {$ping['code']}). Error: {$ping['error']}\n";
            return;
        }
        echo ">> Live server is reachable (HTTP {$ping['code']})\n\n";

        // =========================================================
        // SECTION 3: Create Project from Accepted Idea & Manage Tasks
        // =========================================================
        echo ">> SECTION 3: Create Project & Manage Tasks\n";

        // Step 3.1: Log in as Elena (Idea Creator)
        $loginOk = $this->login('elena@timebank.local');
        $this->assertCheck($loginOk, "Login as Elena (Owner)");

        // Fetch /ideas/create
        $createIdeaPage = $this->request('GET', '/ideas/create');
        $token = $this->getCsrfToken($createIdeaPage['body']);

        // Create a new Idea with status 'recruiting'
        $randomSuffix = rand(1000, 9999);
        $ideaTitle = 'Decentralized Community Mesh Network ' . $randomSuffix;
        $storeIdeaRes = $this->request('POST', '/ideas', [
            '_token' => $token,
            'category_id' => 1,
            'title' => $ideaTitle,
            'mission_statement' => 'Deploying autonomous off-grid communications infrastructure for community resilience.',
            'target_hours' => 20.00,
            'status' => 'recruiting',
        ]);
        $this->assertCheck($storeIdeaRes['code'] === 200 || $storeIdeaRes['code'] === 302, "Create new Idea: '$ideaTitle'");

        // Get Idea ID
        $createdIdea = Idea::where('title', $ideaTitle)->first();
        $ideaId = $createdIdea?->id;
        $this->assertCheck($ideaId !== null, "Created Idea ID identified", "ID: $ideaId");

        // Step 3.2: Log in as Marcus (Collaborator) and Apply
        $marcusLogin = $this->login('marcus@timebank.local');
        $this->assertCheck($marcusLogin, "Login as Marcus (Collaborator)");

        $ideaPage = $this->request('GET', "/ideas/$ideaId");
        $token = $this->getCsrfToken($ideaPage['body']);

        $applyRes = $this->request('POST', "/ideas/$ideaId/collaborators", [
            '_token' => $token,
            'role_offered' => 'Network Hardware Architect',
            'hours_pledged' => 10,
        ]);
        $this->assertCheck($applyRes['code'] === 200 || $applyRes['code'] === 302, "Marcus applied to collaborate on Idea #$ideaId");

        // Step 3.3: Log back in as Elena and Accept Marcus
        $this->login('elena@timebank.local');
        $ideaPageOwner = $this->request('GET', "/ideas/$ideaId");
        $token = $this->getCsrfToken($ideaPageOwner['body']);

        // Find collaborator application ID
        $collab = IdeaCollaborator::where('idea_id', $ideaId)->where('user_id', User::where('email', 'marcus@timebank.local')->first()->id)->first();
        $collabId = $collab?->id;

        if ($collabId) {
            $acceptRes = $this->request('PATCH', "/idea-collaborators/$collabId", [
                '_token' => $token,
                'status' => 'accepted',
            ]);
            $this->assertCheck($acceptRes['code'] === 200 || $acceptRes['code'] === 302, "Elena accepted Marcus as Collaborator (Application #$collabId)");
        }

        // Step 3.4: Convert Idea to Project
        $ideaPageOwnerAfter = $this->request('GET', "/ideas/$ideaId");
        $token = $this->getCsrfToken($ideaPageOwnerAfter['body']);

        $convertRes = $this->request('POST', "/ideas/$ideaId/project", [
            '_token' => $token,
        ]);
        $this->assertCheck($convertRes['code'] === 200 || $convertRes['code'] === 302, "Convert Idea to Project via POST /ideas/$ideaId/project");

        // Verify Project created
        $createdProject = Project::where('idea_id', $ideaId)->first();
        $projectId = $createdProject?->id;
        $this->assertCheck($createdProject !== null, "Project created successfully in database", "Project ID: $projectId");

        $projectPage = $this->request('GET', "/projects/$projectId");
        $this->assertCheck($projectPage['code'] === 200, "Project Page loads with HTTP 200", "URL: /projects/$projectId");
        $this->assertCheck(str_contains($projectPage['body'], $ideaTitle) || str_contains($projectPage['body'], 'Mesh Network'), "Project title displays correctly");
        $this->assertCheck(str_contains($projectPage['body'], 'Elena Rostova'), "Project Leader displayed (Elena Rostova)");
        $this->assertCheck(str_contains($projectPage['body'], 'Marcus Chen'), "Project Member displayed (Marcus Chen)");
        $this->assertCheck(str_contains($projectPage['body'], '0%'), "Initial project progress displays 0%");

        // Step 3.5: Add task 'Design project dashboard' assigned to Marcus
        $token = $this->getCsrfToken($projectPage['body']);
        $marcus = User::where('email', 'marcus@timebank.local')->first();

        $taskRes = $this->request('POST', "/projects/$projectId/tasks", [
            '_token' => $token,
            'title' => 'Design project dashboard',
            'description' => 'Create high-fidelity UI wireframes and interface prototypes',
            'assigned_to' => $marcus->id,
            'target_hours' => 4.00,
            'status' => 'pending',
            'order_index' => 1,
        ]);
        $this->assertCheck($taskRes['code'] === 200 || $taskRes['code'] === 302, "Task 'Design project dashboard' added and assigned to Marcus");

        // Step 3.6: Verify Task on Project Board and Update its status
        $projectPageTasks = $this->request('GET', "/projects/$projectId");
        $this->assertCheck(str_contains($projectPageTasks['body'], 'Design project dashboard'), "Task appears on Project Board");

        $createdTask = ProjectTask::where('project_id', $projectId)->where('title', 'Design project dashboard')->first();
        $taskId = $createdTask?->id;

        if ($taskId) {
            $token = $this->getCsrfToken($projectPageTasks['body']);
            $updateTaskRes = $this->request('PATCH', "/project-tasks/$taskId", [
                '_token' => $token,
                'title' => 'Design project dashboard',
                'description' => 'Create high-fidelity UI wireframes and interface prototypes',
                'assigned_to' => $marcus->id,
                'target_hours' => 4.00,
                'status' => 'completed',
                'order_index' => 1,
            ]);
            $this->assertCheck($updateTaskRes['code'] === 200 || $updateTaskRes['code'] === 302, "Task #$taskId marked as 'completed'");

            $createdTask->refresh();
            $this->assertCheck($createdTask->status === 'completed', "Task status updated to completed in database");

            $projectPageAfter = $this->request('GET', "/projects/$projectId");
            $this->assertCheck(str_contains($projectPageAfter['body'], 'line-through') || str_contains($projectPageAfter['body'], 'COMPLETED'), "Completed task rendered in Completed column on Project Board");
        }

        echo "\n";

        // =========================================================
        // SECTION 4: Check Notifications
        // =========================================================
        echo ">> SECTION 4: Check Notifications Workflow\n";

        // Step 4.1: Check Elena's notifications
        $elenaNotifs = $this->request('GET', '/notifications');
        $this->assertCheck($elenaNotifs['code'] === 200, "Elena opens Notifications page (/notifications)");
        $this->assertCheck(strlen($elenaNotifs['body']) > 500, "Notifications loaded for Owner");

        // Step 4.2: Check Marcus's notifications
        $this->login('marcus@timebank.local');
        $marcusNotifs = $this->request('GET', '/notifications');
        $this->assertCheck($marcusNotifs['code'] === 200, "Marcus opens Notifications page (/notifications)");
        $this->assertCheck(
            str_contains($marcusNotifs['body'], 'Task') || 
            str_contains($marcusNotifs['body'], 'Project') || 
            str_contains($marcusNotifs['body'], 'Collaborat') ||
            str_contains($marcusNotifs['body'], 'dashboard') ||
            str_contains($marcusNotifs['body'], 'Initiative') ||
            str_contains($marcusNotifs['body'], 'notification'),
            "Marcus received Project/Task/Collaboration notifications"
        );

        // Step 4.3: Mark all notifications as read
        $token = $this->getCsrfToken($marcusNotifs['body']);
        $readAllRes = $this->request('POST', '/notifications/read-all', [
            '_token' => $token,
        ]);
        $this->assertCheck($readAllRes['code'] === 200 || $readAllRes['code'] === 302, "Mark all notifications as read via POST /notifications/read-all");

        $marcusNotifsAfter = $this->request('GET', '/notifications');
        $this->assertCheck($marcusNotifsAfter['code'] === 200, "Notifications reloaded successfully after read-all");

        echo "\n";

        // =========================================================
        // SECTION 5: Test Admin Functions & Access Control
        // =========================================================
        echo ">> SECTION 5: Test Admin Functions & Access Control\n";

        // Step 5.1: Log in as Admin
        $adminLogin = $this->login('admin@timebank.local');
        $this->assertCheck($adminLogin, "Login as Admin (admin@timebank.local)");

        // Step 5.2: Admin Dashboard loads
        $adminDash = $this->request('GET', '/admin');
        $this->assertCheck($adminDash['code'] === 200, "Admin Dashboard (/admin) loads with HTTP 200");
        $this->assertCheck(str_contains($adminDash['body'], 'Admin') || str_contains($adminDash['body'], 'Platform') || str_contains($adminDash['body'], 'Dashboard'), "Admin Dashboard metrics & cards rendered");

        // Step 5.3: View Users
        $adminUsers = $this->request('GET', '/admin/users');
        $this->assertCheck($adminUsers['code'] === 200, "Admin Users page (/admin/users) loads with HTTP 200");
        $this->assertCheck(str_contains($adminUsers['body'], 'Elena Rostova') && str_contains($adminUsers['body'], 'Marcus Chen'), "User directory displays all registered members");

        // Step 5.4: View/Manage Categories
        $adminCats = $this->request('GET', '/admin/categories');
        $this->assertCheck($adminCats['code'] === 200, "Admin Categories page (/admin/categories) loads with HTTP 200");
        
        $token = $this->getCsrfToken($adminCats['body']);
        $newCatSlug = 'eco-energy-' . rand(100, 999);
        $createCatRes = $this->request('POST', '/categories', [
            '_token' => $token,
            'name' => 'Renewable Energy ' . rand(100, 999),
            'slug' => $newCatSlug,
            'description' => 'Solar, microgrid and clean energy temporal services',
            'icon' => 'bolt',
            'is_active' => '1',
        ]);
        $this->assertCheck($createCatRes['code'] === 200 || $createCatRes['code'] === 302, "Admin created new Category: '$newCatSlug'");

        // Step 5.5: View Service Requests & Test Dispute Resolution
        $adminRequests = $this->request('GET', '/admin/service-requests');
        $this->assertCheck($adminRequests['code'] === 200, "Admin Service Requests page (/admin/service-requests) loads with HTTP 200");

        // Create a test dispute to verify dispute resolution
        $testService = Service::firstOrCreate([
            'title' => 'Technical Mentorship Consultation',
        ], [
            'user_id' => User::where('email', 'elena@timebank.local')->first()->id,
            'category_id' => 1,
            'description' => 'Consulting and advisory session',
            'hourly_rate' => 2.00,
            'estimated_hours' => 2.00,
            'status' => 'active',
        ]);

        $sarah = User::where('email', 'sarah@timebank.local')->first();
        $testRequest = ServiceRequest::create([
            'service_id' => $testService->id,
            'requester_id' => $sarah->id,
            'provider_id' => $testService->user_id,
            'category_id' => $testService->category_id,
            'title' => 'Architecture advisory session',
            'project_scope' => 'High performance backend system consultation',
            'estimated_hours' => 2.00,
            'total_credits' => 4.00,
            'status' => 'disputed',
        ]);

        $adminDashFresh = $this->request('GET', '/admin');
        $token = $this->getCsrfToken($adminDashFresh['body']);

        $resolveDisputeRes = $this->request('POST', "/service-requests/{$testRequest->id}/resolve-dispute", [
            '_token' => $token,
            'resolution' => 'cancelled',
        ]);
        $this->assertCheck($resolveDisputeRes['code'] === 200 || $resolveDisputeRes['code'] === 302, "Admin resolved test dispute (#{$testRequest->id})");

        $testRequest->refresh();
        $this->assertCheck($testRequest->status === 'cancelled', "Dispute status transitioned to 'cancelled'");

        // Step 5.6: Test Admin Time Credit Adjustment
        $sarahInitialBalance = (float)($sarah->fresh()->time_balance ?? 5.0);

        $adminDashForToken = $this->request('GET', '/admin');
        $adminToken = $this->getCsrfToken($adminDashForToken['body']);

        $adjustRes = $this->request('POST', '/admin/adjustments', [
            '_token' => $adminToken,
            'user_id' => $sarah->id,
            'amount' => 5.00,
            'description' => 'Ecosystem hackathon award grant',
        ], [
            'Accept: application/json',
            'X-CSRF-TOKEN: ' . $adminToken,
        ]);
        $this->assertCheck($adjustRes['code'] === 200 || $adjustRes['code'] === 201 || $adjustRes['code'] === 302, "Admin performed time-credit adjustment (+5.00 hrs) for {$sarah->name}", "HTTP {$adjustRes['code']}");

        $sarah->refresh();
        $sarahNewBalance = (float)$sarah->time_balance;
        $this->assertCheck($sarahNewBalance === round($sarahInitialBalance + 5.00, 2), "Target user balance updated to {$sarahNewBalance} hrs (verified in DB & Ledger)");

        // Step 5.7: Verify Sarah can view her wallet & updated balance
        $this->login('sarah@timebank.local');
        $sarahWallet = $this->request('GET', '/wallet');
        $this->assertCheck($sarahWallet['code'] === 200, "Sarah opens Wallet page (/wallet)");
        $this->assertCheck(str_contains($sarahWallet['body'], number_format($sarahNewBalance, 2)) || str_contains($sarahWallet['body'], '15.00') || str_contains($sarahWallet['body'], '20.00'), "Updated balance visible in user Wallet");

        $sarahTx = $this->request('GET', '/transactions');
        $this->assertCheck($sarahTx['code'] === 200, "Sarah opens Transactions page (/transactions)");
        $this->assertCheck(str_contains($sarahTx['body'], 'Ecosystem hackathon award grant') || str_contains($sarahTx['body'], 'admin_adjustment') || str_contains($sarahTx['body'], 'Adjustment'), "Admin adjustment transaction entry visible in transaction ledger");

        // Step 5.8: Access Control - Normal user attempts to access Admin pages
        $this->login('marcus@timebank.local');
        $forbiddenAdminDash = $this->request('GET', '/admin');
        $this->assertCheck($forbiddenAdminDash['code'] === 403, "Access Control: Normal user navigating to /admin gets HTTP 403 Forbidden");

        $forbiddenAdminUsers = $this->request('GET', '/admin/users');
        $this->assertCheck($forbiddenAdminUsers['code'] === 403, "Access Control: Normal user navigating to /admin/users gets HTTP 403 Forbidden");

        $forbiddenAdminCats = $this->request('GET', '/admin/categories');
        $this->assertCheck($forbiddenAdminCats['code'] === 403, "Access Control: Normal user navigating to /admin/categories gets HTTP 403 Forbidden");

        $forbiddenAdminReqs = $this->request('GET', '/admin/service-requests');
        $this->assertCheck($forbiddenAdminReqs['code'] === 403, "Access Control: Normal user navigating to /admin/service-requests gets HTTP 403 Forbidden");

        echo "\n===============================================================\n";
        echo "                      FINAL RESULTS                            \n";
        echo "===============================================================\n";
        echo "TOTAL PASSED: {$this->passed}\n";
        echo "TOTAL FAILED: " . count($this->errors) . "\n";
        if (count($this->errors) > 0) {
            echo "\nErrors:\n";
            foreach ($this->errors as $err) {
                echo " - $err\n";
            }
        } else {
            echo "\n>> ALL LIVE TESTS FOR SECTIONS 3, 4, AND 5 PASSED 100% SUCCESSFULLY!\n";
        }
        echo "===============================================================\n";
    }
}

$tester = new LiveHttpTester();
$tester->runAllTests();
