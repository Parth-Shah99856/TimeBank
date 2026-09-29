<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaCollaborator;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectTask;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestMessage;
use App\Models\ServiceRequestOtp;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * DemoDataSeeder
 *
 * Creates realistic synthetic demo data to populate a development environment.
 *
 * IDEMPOTENCY STRATEGY:
 *   - Users / Categories / Services: updateOrCreate on unique keys (email, title+user)
 *   - ServiceRequests: firstOrCreate on (requester_id, service_id, title)
 *   - Transactions: guarded by deterministic code prefix; skipped if code exists
 *   - Reviews: firstOrCreate on (service_request_id, reviewer_id)
 *   - Ideas: firstOrCreate on (user_id, title)
 *   - IdeaCollaborators: firstOrCreate on (idea_id, user_id)
 *   - Projects: firstOrCreate on (idea_id)
 *   - ProjectMembers: firstOrCreate on (project_id, user_id)
 *   - ProjectTasks: firstOrCreate on (project_id, title)
 *   - Notifications: guarded by deterministic UUID seeded from description string
 *   - OTPs: firstOrCreate on (service_request_id, user_id) with null used_at
 *   - Messages: guarded by (service_request_id, sender_id, content)
 *
 * LEVEL THRESHOLDS (matches dashboard.blade.php and users/show.blade.php exactly):
 *   >= 20 completed provided SRs → Architect Level 5
 *   >= 10                        → Architect Level 4
 *   >= 5                         → Architect Level 3
 *   >= 2                         → Architect Level 2
 *   default                      → Architect Level 1
 *
 * DEMO LEADERBOARD TARGETS (total TYPE_SERVICE_EXCHANGE hours EARNED as provider):
 *   Alex Rivera    → Level 3 (5 completed) → ~11.00 hrs earned
 *   Maya Patel     → Level 3 (5 completed) → ~9.75 hrs earned
 *   Elena Rostova  → Level 2 (3 completed) → ~6.00 hrs earned
 *   Marcus Chen    → Level 2 (2 completed) → ~4.00 hrs earned
 *   Sarah Jenkins  → Level 2 (2 completed) → ~3.75 hrs earned
 *   Daniel Cooper  → Level 2 (2 completed) → ~3.50 hrs earned
 *   Sophia Williams→ Level 1 (1 completed) → ~3.00 hrs earned
 *   Admin          → Level 1 (0)           → 0 hrs earned
 *
 * IMPORTANT: This seeder does NOT run migrate:fresh, TRUNCATE, or DELETE.
 *            It is safe to run on a database that already contains seed data.
 *
 * DISCLAIMER: These records are newly generated synthetic demo data.
 *             They do NOT represent the original records lost in the database wipe.
 *
 * Demo passwords (local dev only — NOT committed as secrets):
 *   All four additional users have password: password
 *   (Same as the four seeded users — standard for local dev)
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting DemoDataSeeder — safe idempotent run');

        $this->call([
            CategorySeeder::class,
            UserSeeder::class,
        ]);

        DB::transaction(function () {
            // ----------------------------------------------------------------
            // 1. ADDITIONAL USERS (4)
            //    time_balance is set to reflect realistic balance AFTER the
            //    exchange transactions created below. The balance formula is:
            //      starting_balance (5.00 signup bonus)
            //      + hours_earned_as_provider (from TYPE_SERVICE_EXCHANGE to_user)
            //      - hours_spent_as_requester (from TYPE_SERVICE_EXCHANGE from_user)
            //
            //    Alex:   5.00 + 11.00 - 8.00 = 8.00  (net +3.00 from exchanges)
            //    Maya:   5.00 +  9.75 - 6.00 = 8.75
            //    Daniel: 5.00 +  3.50 - 4.00 = 4.50
            //    Sophia: 5.00 +  3.00 - 3.75 = 4.25
            // ----------------------------------------------------------------
            $this->command->info('  → Creating additional users…');

            $additionalUsers = [
                [
                    'name'              => 'Alex Rivera',
                    'email'             => 'alex@timebank.local',
                    'password'          => Hash::make('password'),
                    'role'              => 'user',
                    'headline'          => 'Full-Stack JavaScript Developer',
                    'bio'               => 'Passionate about React, Node.js, and building scalable web apps. Available for mentoring and pair-programming sessions.',
                    'time_balance'      => '8.00',
                    'email_verified_at' => now(),
                ],
                [
                    'name'              => 'Maya Patel',
                    'email'             => 'maya@timebank.local',
                    'password'          => Hash::make('password'),
                    'role'              => 'user',
                    'headline'          => 'Data Scientist & ML Engineer',
                    'bio'               => 'Specialising in Python, TensorFlow, and data visualisation. Excited to collaborate on data-driven projects and share analytical skills.',
                    'time_balance'      => '8.75',
                    'email_verified_at' => now(),
                ],
                [
                    'name'              => 'Daniel Cooper',
                    'email'             => 'daniel@timebank.local',
                    'password'          => Hash::make('password'),
                    'role'              => 'user',
                    'headline'          => 'DevOps Engineer & Cloud Architect',
                    'bio'               => 'AWS & GCP certified. Passionate about CI/CD pipelines, infrastructure-as-code, and helping teams ship faster.',
                    'time_balance'      => '4.50',
                    'email_verified_at' => now(),
                ],
                [
                    'name'              => 'Sophia Williams',
                    'email'             => 'sophia@timebank.local',
                    'password'          => Hash::make('password'),
                    'role'              => 'user',
                    'headline'          => 'Technical Writer & Content Strategist',
                    'bio'               => 'Translating complex technical concepts into clear, accessible documentation. Experienced with API docs, tutorials, and developer guides.',
                    'time_balance'      => '4.25',
                    'email_verified_at' => now(),
                ],
            ];

            foreach ($additionalUsers as $userData) {
                $user = User::updateOrCreate(
                    ['email' => $userData['email']],
                    $userData
                );

                // Each new user gets a signup bonus if not already recorded.
                $hasBonus = Transaction::where('to_user_id', $user->id)
                    ->where('type', Transaction::TYPE_SIGNUP_BONUS)
                    ->exists();

                if (! $hasBonus) {
                    Transaction::create([
                        'transaction_code' => 'TX-BONUS-' . strtoupper(Str::random(8)),
                        'from_user_id'     => null,
                        'to_user_id'       => $user->id,
                        'service_request_id' => null,
                        'amount'           => '5.00',
                        'type'             => Transaction::TYPE_SIGNUP_BONUS,
                        'description'      => 'Initial signup bonus',
                        'created_at'       => now()->subDays(rand(5, 30)),
                    ]);
                }
            }

            // Resolve user records for use in the rest of the seeder.
            $admin   = User::where('email', 'admin@timebank.local')->firstOrFail();
            $elena   = User::where('email', 'elena@timebank.local')->firstOrFail();
            $marcus  = User::where('email', 'marcus@timebank.local')->firstOrFail();
            $sarah   = User::where('email', 'sarah@timebank.local')->firstOrFail();
            $alex    = User::where('email', 'alex@timebank.local')->firstOrFail();
            $maya    = User::where('email', 'maya@timebank.local')->firstOrFail();
            $daniel  = User::where('email', 'daniel@timebank.local')->firstOrFail();
            $sophia  = User::where('email', 'sophia@timebank.local')->firstOrFail();

            // Resolve categories.
            $catWeb     = Category::where('slug', 'web-development')->firstOrFail();
            $catDesign  = Category::where('slug', 'ui-ux-design')->firstOrFail();
            $catData    = Category::where('slug', 'data-science-ai')->firstOrFail();
            $catDevOps  = Category::where('slug', 'systems-devops')->firstOrFail();
            $catWriting = Category::where('slug', 'writing-content')->firstOrFail();
            $catEcology = Category::where('slug', 'urban-ecology')->firstOrFail();
            $catMentor  = Category::where('slug', 'community-mentorship')->firstOrFail();

            // ----------------------------------------------------------------
            // 2. SERVICES (7)
            // ----------------------------------------------------------------
            $this->command->info('  → Creating services…');

            $servicesData = [
                [
                    'user_id'     => $alex->id,
                    'category_id' => $catWeb->id,
                    'title'       => 'React Web Development',
                    'description' => 'I can help build React components, set up routing with React Router, manage state with Redux or Zustand, and review pull requests. Ideal for developers learning React or teams needing extra hands on frontend features.',
                    'hourly_rate' => '2.00',
                    'tags'        => ['react', 'javascript', 'frontend', 'web'],
                    'is_active'   => true,
                ],
                [
                    'user_id'     => $maya->id,
                    'category_id' => $catData->id,
                    'title'       => 'Python Data Analysis',
                    'description' => 'Pandas, NumPy, and Matplotlib sessions to help you analyse datasets, clean data pipelines, and build exploratory visualisations. I can review your scripts and explain statistical concepts.',
                    'hourly_rate' => '2.50',
                    'tags'        => ['python', 'pandas', 'data', 'analysis'],
                    'is_active'   => true,
                ],
                [
                    'user_id'     => $marcus->id,
                    'category_id' => $catDesign->id,
                    'title'       => 'UI/UX Design Assistance',
                    'description' => 'Figma wireframing, component design, accessibility reviews, and design system structuring. I can help refine your interface, create consistent design tokens, and review UX flows.',
                    'hourly_rate' => '2.00',
                    'tags'        => ['figma', 'design', 'ux', 'ui', 'accessibility'],
                    'is_active'   => true,
                ],
                [
                    'user_id'     => $elena->id,
                    'category_id' => $catDevOps->id,
                    'title'       => 'Git & GitHub Mentoring',
                    'description' => 'Git workflows, branching strategies, pull request reviews, rebasing, and CI/CD pipeline walkthroughs. Perfect for developers who want to improve their version control habits.',
                    'hourly_rate' => '1.50',
                    'tags'        => ['git', 'github', 'version-control', 'devops'],
                    'is_active'   => true,
                ],
                [
                    'user_id'     => $sarah->id,
                    'category_id' => $catData->id,
                    'title'       => 'Excel & Data Visualisation',
                    'description' => 'Advanced Excel techniques including pivot tables, Power Query, conditional formatting, and dashboard creation. I can also assist with Google Sheets and lightweight visualisation tools.',
                    'hourly_rate' => '1.50',
                    'tags'        => ['excel', 'spreadsheets', 'visualization', 'data'],
                    'is_active'   => true,
                ],
                [
                    'user_id'     => $sophia->id,
                    'category_id' => $catWriting->id,
                    'title'       => 'Technical Writing',
                    'description' => 'API documentation, README files, developer guides, and tutorial writing. I help teams create clear and maintainable documentation that developers actually enjoy reading.',
                    'hourly_rate' => '1.50',
                    'tags'        => ['writing', 'documentation', 'technical', 'api'],
                    'is_active'   => true,
                ],
                [
                    'user_id'     => $daniel->id,
                    'category_id' => $catDevOps->id,
                    'title'       => 'Java Programming Help',
                    'description' => 'Java fundamentals, OOP design patterns, Spring Boot basics, and code review sessions. Whether you are learning Java or working on a Spring project, I can guide you through the tricky parts.',
                    'hourly_rate' => '2.00',
                    'tags'        => ['java', 'spring', 'oop', 'backend'],
                    'is_active'   => true,
                ],
            ];

            $services = [];
            foreach ($servicesData as $svcData) {
                $svc = Service::updateOrCreate(
                    ['user_id' => $svcData['user_id'], 'title' => $svcData['title']],
                    $svcData
                );
                $services[] = $svc;
            }

            [$svcReact, $svcPython, $svcDesign, $svcGit, $svcExcel, $svcWriting, $svcJava] = $services;

            // ----------------------------------------------------------------
            // 3. SERVICE REQUESTS
            //    Completed SRs drive leaderboard ranking & level progression.
            //    Each completed SR MUST have a corresponding TYPE_SERVICE_EXCHANGE
            //    transaction so it appears in the leaderboard's withSum query.
            //
            //    Completed SR distribution per provider (for Architect Level):
            //      Alex Rivera   → 5 completed  → Level 3
            //      Maya Patel    → 5 completed  → Level 3
            //      Elena Rostova → 3 completed  → Level 2
            //      Marcus Chen   → 2 completed  → Level 2
            //      Sarah Jenkins → 2 completed  → Level 2
            //      Daniel Cooper → 2 completed  → Level 2
            //      Sophia Williams → 1 completed → Level 1
            // ----------------------------------------------------------------
            $this->command->info('  → Creating service requests…');

            // Helper: create a completed SR + its exchange transaction idempotently.
            // Uses a deterministic transaction code (TX-DEMO-<shortHash>) for dedup.
            $makeCompletedSR = function (
                Service $service,
                User    $requester,
                User    $provider,
                string  $title,
                string  $scope,
                string  $hours,
                string  $credits,
                int     $daysAgo
            ) {
                $sr = ServiceRequest::firstOrCreate(
                    [
                        'requester_id' => $requester->id,
                        'service_id'   => $service->id,
                        'title'        => $title,
                    ],
                    [
                        'provider_id'     => $provider->id,
                        'category_id'     => $service->category_id,
                        'project_scope'   => $scope,
                        'estimated_hours' => $hours,
                        'total_credits'   => $credits,
                        'desired_deadline'=> now()->subDays($daysAgo + 3)->toDateString(),
                        'status'          => 'completed',
                        'completed_at'    => now()->subDays($daysAgo),
                    ]
                );

                // Deterministic dedup key: TX-DEMO-<sr_id> so one tx per SR.
                $txCode = 'TX-DEMO-SR' . str_pad($sr->id, 6, '0', STR_PAD_LEFT);
                $txExists = Transaction::where('transaction_code', $txCode)->exists();

                if (! $txExists) {
                    Transaction::create([
                        'transaction_code'   => $txCode,
                        'from_user_id'       => $requester->id,
                        'to_user_id'         => $provider->id,
                        'service_request_id' => $sr->id,
                        'amount'             => $credits,
                        'type'               => Transaction::TYPE_SERVICE_EXCHANGE,
                        'description'        => 'Service exchange completion',
                        'created_at'         => now()->subDays($daysAgo),
                    ]);
                }

                return $sr;
            };

            // ------------------------------------------------------------------
            // ALEX RIVERA — provider for 5 completed SRs → Level 3
            //   Requesters: marcus(2), elena(1), sarah(1), sophia(1)
            //   Total credits earned: 4.00 + 4.00 + 1.50 + 1.00 + 0.50 = 11.00
            // ------------------------------------------------------------------
            $srAlex1 = $makeCompletedSR(
                $svcReact, $marcus, $alex,
                'React component refactoring for design system',
                'Refactor three existing page components into reusable design-system tokens, add prop validation, and document storybook stories for each.',
                '2.00', '4.00', 30
            );
            $srAlex2 = $makeCompletedSR(
                $svcReact, $elena, $alex,
                'React state management migration to Zustand',
                'Migrate a Redux-heavy dashboard to Zustand, replacing complex action/reducer patterns with simple stores while preserving existing TypeScript types.',
                '2.00', '4.00', 22
            );
            $srAlex3 = $makeCompletedSR(
                $svcReact, $sarah, $alex,
                'React hooks workshop — useCallback and useMemo',
                'Explain the difference between useCallback and useMemo with live examples, profile re-renders in a test app, and apply memoisation optimisations together.',
                '0.75', '1.50', 17
            );
            $srAlex4 = $makeCompletedSR(
                $svcReact, $sophia, $alex,
                'JavaScript async/await patterns review',
                'Code review of three existing async functions, rewrite with proper error handling, add retry logic, and explain the event loop in practical terms.',
                '0.50', '1.00', 10
            );
            $srAlex5 = $makeCompletedSR(
                $svcReact, $daniel, $alex,
                'Frontend CI pipeline setup with GitHub Actions',
                'Configure a GitHub Actions workflow that runs ESLint, Prettier, and Vitest on every pull request, with a build artifact upload step for staging review.',
                '0.25', '0.50', 5
            );

            // ------------------------------------------------------------------
            // MAYA PATEL — provider for 5 completed SRs → Level 3
            //   Requesters: sarah(2), elena(1), alex(1), marcus(1)
            //   Total credits earned: 3.75 + 2.50 + 1.50 + 1.00 + 1.00 = 9.75
            // ------------------------------------------------------------------
            $srMaya1 = $makeCompletedSR(
                $svcPython, $sarah, $maya,
                'Data pipeline review and optimisation',
                'Review existing Pandas pipeline for performance bottlenecks, suggest vectorised operations, and help write unit tests for data transformation functions.',
                '1.50', '3.75', 28
            );
            $srMaya2 = $makeCompletedSR(
                $svcPython, $sarah, $maya,
                'Matplotlib dashboard visualisation session',
                'Design a five-chart Matplotlib dashboard for monthly sales data — bar, line, scatter, pie, and heatmap — with colour consistency and annotation guidance.',
                '1.00', '2.50', 20
            );
            $srMaya3 = $makeCompletedSR(
                $svcPython, $elena, $maya,
                'NumPy vectorisation for simulation code',
                'Profile an existing numerical simulation written with Python loops, refactor hotspots to NumPy vectorised operations, and measure the resulting speedup.',
                '0.60', '1.50', 14
            );
            $srMaya4 = $makeCompletedSR(
                $svcPython, $alex, $maya,
                'Exploratory data analysis for user behaviour dataset',
                'Load a CSV of user-click events, clean nulls and duplicates, produce a descriptive-stats summary, and generate three correlation charts for the engineering team.',
                '0.40', '1.00', 9
            );
            $srMaya5 = $makeCompletedSR(
                $svcPython, $marcus, $maya,
                'Python script review for data ingestion pipeline',
                'Review a 200-line ETL script, identify anti-patterns, add type hints, split into functions with docstrings, and write two pytest unit tests for the transform layer.',
                '0.40', '1.00', 4
            );

            // ------------------------------------------------------------------
            // ELENA ROSTOVA — provider for 3 completed SRs → Level 2
            //   Requesters: daniel(1), marcus(1), alex(1)
            //   Total credits earned: 2.25 + 2.00 + 1.75 = 6.00
            // ------------------------------------------------------------------
            $srElena1 = $makeCompletedSR(
                $svcGit, $daniel, $elena,
                'Git branching strategy for monorepo project',
                'Help establish a trunk-based development workflow, set up branch protection rules, and configure GitHub Actions for automated PR checks.',
                '1.50', '2.25', 25
            );
            $srElena2 = $makeCompletedSR(
                $svcGit, $marcus, $elena,
                'Interactive rebase and squashing workshop',
                'Walk through interactive rebase to clean up a messy 20-commit branch, practice squash merges, and configure a commit-message template for the team.',
                '1.00', '2.00', 16 // corrected so total = 2.00+2.25+1.75 = 6.00
            );
            $srElena3 = $makeCompletedSR(
                $svcGit, $alex, $elena,
                'Git hooks and pre-commit linting setup',
                'Configure Husky + lint-staged in a Node.js monorepo, write a custom pre-push hook that runs the test suite, and document the hook workflow for new team members.',
                '1.00', '1.75', 8
            );

            // ------------------------------------------------------------------
            // MARCUS CHEN — provider for 2 completed SRs → Level 2
            //   Requesters: alex(1), daniel(1)
            //   Total credits earned: 2.00 + 2.00 = 4.00
            // ------------------------------------------------------------------
            $srMarcus1 = $makeCompletedSR(
                $svcDesign, $alex, $marcus,
                'Dashboard UX review and accessibility audit',
                'Review the analytics dashboard layout for usability issues, run a colour-contrast audit, and propose accessible component alternatives.',
                '1.00', '2.00', 23
            );
            $srMarcus2 = $makeCompletedSR(
                $svcDesign, $daniel, $marcus,
                'Figma component library initial setup',
                'Create a starter Figma component library with atomic design principles — atoms (buttons, inputs, badges), molecules (cards, alerts), and one organism (header).',
                '1.00', '2.00', 11
            );

            // ------------------------------------------------------------------
            // SARAH JENKINS — provider for 2 completed SRs → Level 2
            //   Requesters: sophia(1), marcus(1)
            //   Total credits earned: 2.25 + 1.50 = 3.75
            // ------------------------------------------------------------------
            $srSarah1 = $makeCompletedSR(
                $svcExcel, $sophia, $sarah,
                'Excel pivot table and data summarisation',
                'Build a dynamic pivot table from a 5000-row sales CSV, add slicers for region and quarter, and format a printable one-page summary for management.',
                '1.50', '2.25', 21
            );
            $srSarah2 = $makeCompletedSR(
                $svcExcel, $marcus, $sarah,
                'Google Sheets dashboard for project tracking',
                'Create a Google Sheets project tracker with colour-coded status cells, progress bars via formulas, and an automatically updating summary row for each sprint.',
                '1.00', '1.50', 12
            );

            // ------------------------------------------------------------------
            // DANIEL COOPER — provider for 2 completed SRs → Level 2
            //   Requesters: sophia(1), elena(1)
            //   Total credits earned: 2.00 + 1.50 = 3.50
            // ------------------------------------------------------------------
            $srDaniel1 = $makeCompletedSR(
                $svcJava, $sophia, $daniel,
                'Spring Boot REST API design review',
                'Review the architecture of a new Spring Boot REST API — entity design, repository patterns, exception handling strategy, and DTO mapping approach.',
                '1.00', '2.00', 19
            );
            $srDaniel2 = $makeCompletedSR(
                $svcJava, $elena, $daniel,
                'Java OOP fundamentals and design patterns',
                'Walk through four GOF design patterns (Factory, Observer, Strategy, Decorator) with working Java examples and refactor a monolithic class to use them correctly.',
                '0.75', '1.50', 7
            );

            // ------------------------------------------------------------------
            // SOPHIA WILLIAMS — provider for 1 completed SR → Level 1
            //   Requester: maya(1)
            //   Total credits earned: 3.00
            // ------------------------------------------------------------------
            $srSophia1 = $makeCompletedSR(
                $svcWriting, $maya, $sophia,
                'API documentation for ML model endpoints',
                'Write clear OpenAPI 3.0 documentation for a REST API exposing three machine learning inference endpoints, including example requests and response schemas.',
                '2.00', '3.00', 15
            );

            // In-progress and pending SRs (no exchange transactions — not yet completed).
            $srInProgress1 = ServiceRequest::firstOrCreate(
                [
                    'requester_id' => $alex->id,
                    'service_id'   => $svcDesign->id,
                    'title'        => 'Mobile-first responsive redesign review',
                ],
                [
                    'provider_id'     => $marcus->id,
                    'category_id'     => $catDesign->id,
                    'project_scope'   => 'Review and improve the mobile layout of the main app, audit touch targets, and propose a bottom-sheet navigation pattern for small screens.',
                    'estimated_hours' => '2.00',
                    'total_credits'   => '4.00',
                    'desired_deadline'=> now()->addDays(5)->toDateString(),
                    'status'          => 'in_progress',
                    'completed_at'    => null,
                ]
            );

            $srInProgress2 = ServiceRequest::firstOrCreate(
                [
                    'requester_id' => $daniel->id,
                    'service_id'   => $svcGit->id,
                    'title'        => 'CI/CD pipeline for monorepo with GitHub Actions',
                ],
                [
                    'provider_id'     => $elena->id,
                    'category_id'     => $catDevOps->id,
                    'project_scope'   => 'Help establish a trunk-based development workflow, set up branch protection rules, and configure GitHub Actions for automated PR checks.',
                    'estimated_hours' => '1.50',
                    'total_credits'   => '2.25',
                    'desired_deadline'=> now()->addDays(7)->toDateString(),
                    'status'          => 'in_progress',
                    'completed_at'    => null,
                ]
            );

            $srInProgress3 = ServiceRequest::firstOrCreate(
                [
                    'requester_id' => $maya->id,
                    'service_id'   => $svcWriting->id,
                    'title'        => 'Developer onboarding guide for ML platform',
                ],
                [
                    'provider_id'     => $sophia->id,
                    'category_id'     => $catWriting->id,
                    'project_scope'   => 'Write a comprehensive onboarding guide covering environment setup, key API concepts, and three common workflow examples for new ML platform users.',
                    'estimated_hours' => '2.00',
                    'total_credits'   => '3.00',
                    'desired_deadline'=> now()->addDays(10)->toDateString(),
                    'status'          => 'in_progress',
                    'completed_at'    => null,
                ]
            );

            $srPending = ServiceRequest::firstOrCreate(
                [
                    'requester_id' => $sophia->id,
                    'service_id'   => $svcJava->id,
                    'title'        => 'Spring Boot REST API design review',
                ],
                [
                    'provider_id'     => $daniel->id,
                    'category_id'     => $catDevOps->id,
                    'project_scope'   => 'Review the architecture of a new Spring Boot REST API — entity design, repository patterns, exception handling strategy, and DTO mapping approach.',
                    'estimated_hours' => '1.00',
                    'total_credits'   => '2.00',
                    'desired_deadline'=> now()->addDays(14)->toDateString(),
                    'status'          => 'pending',
                    'completed_at'    => null,
                ]
            );

            // ----------------------------------------------------------------
            // 4. UPDATE time_balance for UserSeeder users to reflect exchanges
            //    (Additional users' time_balance was already set above.)
            //    UserSeeder sets all four to 5.00. Adjust to reflect exchange history:
            //
            //    Elena:  5.00 + 6.00 earned - 3.75 spent (as requester) = 7.25
            //    Marcus: 5.00 + 4.00 earned - 5.50 spent (as requester) = 3.50
            //    Sarah:  5.00 + 3.75 earned - 8.75 spent (as requester) = 0.00 (floor at 0)
            //
            //    NOTE: forceFill bypasses the immutable Transaction guard.
            //    We do this ONLY when the exchange transactions for a user's
            //    completed SRs already exist in the ledger and the balance
            //    has not yet been adjusted (detect via a guard flag in description).
            // ----------------------------------------------------------------
            $this->command->info('  → Reconciling time_balance for demo exchange activity…');

            // Recalculate each user's correct balance from the ledger.
            // balance = signup_bonus_sum + exchange_earned_sum - exchange_spent_sum
            $recalcBalance = function (User $user): string {
                $earned = (string) Transaction::where('to_user_id', $user->id)
                    ->whereIn('type', [Transaction::TYPE_SIGNUP_BONUS, Transaction::TYPE_SERVICE_EXCHANGE])
                    ->sum('amount');
                $spent = (string) Transaction::where('from_user_id', $user->id)
                    ->where('type', Transaction::TYPE_SERVICE_EXCHANGE)
                    ->sum('amount');
                $balance = bcsub($earned, $spent, 2);
                return bccomp($balance, '0.00', 2) < 0 ? '0.00' : $balance;
            };

            // Only update if the current stored balance does not match the ledger.
            foreach ([$elena, $marcus, $sarah, $alex, $maya, $daniel, $sophia] as $u) {
                $correct = $recalcBalance($u);
                $fresh   = $u->fresh();
                if (bccomp((string) $fresh->time_balance, $correct, 2) !== 0) {
                    $fresh->forceFill(['time_balance' => $correct])->save();
                }
            }

            // ----------------------------------------------------------------
            // 5. REVIEWS (one per completed SR where appropriate)
            // ----------------------------------------------------------------
            $this->command->info('  → Creating reviews…');

            $reviewData = [
                // Alex's completed SRs (Marcus reviews Alex)
                [$srAlex1->id, $marcus->id, $alex->id, 5, 'Alex was incredibly helpful — walked me through the React refactor clearly and the code is now much more maintainable. Highly recommend!'],
                [$srAlex2->id, $elena->id,  $alex->id, 5, 'Excellent session. Alex migrated the entire Redux setup to Zustand in one go with zero regressions. Clean, idiomatic code throughout.'],
                [$srAlex3->id, $sarah->id,  $alex->id, 4, 'Really clear explanation of useMemo vs useCallback. I finally understand when to reach for each one. Would book again.'],
                // Maya's completed SRs
                [$srMaya1->id, $sarah->id, $maya->id, 5, 'Maya has a great teaching style. She explained every step of the data pipeline optimisation and I learned a lot about vectorisation. Fantastic session.'],
                [$srMaya2->id, $sarah->id, $maya->id, 5, 'The Matplotlib dashboard turned out beautifully. Maya has a great eye for colour and layout. I got exactly what I needed.'],
                [$srMaya3->id, $elena->id, $maya->id, 4, 'Solid refactor session. The NumPy rewrite gave a 6x speedup on the critical path. A few edge cases needed follow-up but overall excellent work.'],
                // Elena's completed SRs
                [$srElena1->id, $daniel->id, $elena->id, 5, 'Elena explained trunk-based development perfectly. We have already implemented the branch protection rules she recommended. Great session!'],
                [$srElena2->id, $marcus->id, $elena->id, 5, 'Brilliant interactive rebase session. I no longer dread squashing commits and the team commit template has already improved our log quality.'],
                // Marcus reviews
                [$srMarcus1->id, $alex->id, $marcus->id, 4, 'Really solid UX review. Marcus spotted several contrast issues I had missed and his suggested component alternatives were practical and clear.'],
                // Sarah reviews
                [$srSarah1->id, $sophia->id, $sarah->id, 5, 'Sarah built the pivot table exactly as I needed it. The slicer interactions are smooth and the management summary looks professional.'],
                // Daniel reviews
                [$srDaniel1->id, $sophia->id, $daniel->id, 4, 'Daniel gave thorough API design feedback. His suggestions on the DTO layer were particularly valuable. Quick, clear, and professional.'],
                // Sophia review
                [$srSophia1->id, $maya->id, $sophia->id, 5, 'Sophia produced beautiful OpenAPI documentation in one session. The example requests and error-schema coverage are exactly what our team needed.'],
            ];

            foreach ($reviewData as [$srId, $reviewerId, $revieweeId, $rating, $comment]) {
                Review::firstOrCreate(
                    ['service_request_id' => $srId, 'reviewer_id' => $reviewerId],
                    ['reviewee_id' => $revieweeId, 'rating' => $rating, 'comment' => $comment]
                );
            }

            // ----------------------------------------------------------------
            // 6. IDEAS (2)
            // ----------------------------------------------------------------
            $this->command->info('  → Creating ideas…');

            $idea1 = Idea::firstOrCreate(
                ['user_id' => $elena->id, 'title' => 'Community Skill Exchange Portal'],
                [
                    'category_id'      => $catWeb->id,
                    'mission_statement'=> 'Build an open-source web platform where community members can offer and request skills without money — pure time-credit bartering. The portal will include a skill marketplace, request board, and reputation system.',
                    'target_hours'     => '80.00',
                    'required_skills'  => ['react', 'laravel', 'ux-design', 'technical-writing'],
                    'status'           => 'recruiting',
                ]
            );

            $idea2 = Idea::firstOrCreate(
                ['user_id' => $maya->id, 'title' => 'Open Data Science Learning Hub'],
                [
                    'category_id'      => $catData->id,
                    'mission_statement'=> 'Create a curated, community-maintained repository of data science tutorials, datasets, and Jupyter notebooks. Members contribute tutorials and earn time credits reviewed by the community.',
                    'target_hours'     => '40.00',
                    'required_skills'  => ['python', 'data-science', 'technical-writing', 'devops'],
                    'status'           => 'open',
                ]
            );

            // ----------------------------------------------------------------
            // 7. IDEA COLLABORATORS (3)
            // ----------------------------------------------------------------
            $this->command->info('  → Creating idea collaborators…');

            IdeaCollaborator::firstOrCreate(
                ['idea_id' => $idea1->id, 'user_id' => $alex->id],
                [
                    'role_offered'  => 'Lead Frontend Developer',
                    'hours_pledged' => '20.00',
                    'status'        => 'accepted',
                ]
            );

            IdeaCollaborator::firstOrCreate(
                ['idea_id' => $idea1->id, 'user_id' => $marcus->id],
                [
                    'role_offered'  => 'UI/UX Designer',
                    'hours_pledged' => '15.00',
                    'status'        => 'accepted',
                ]
            );

            IdeaCollaborator::firstOrCreate(
                ['idea_id' => $idea2->id, 'user_id' => $sarah->id],
                [
                    'role_offered'  => 'Data Curriculum Contributor',
                    'hours_pledged' => '10.00',
                    'status'        => 'pending',
                ]
            );

            // ----------------------------------------------------------------
            // 8. PROJECT (1 — converted from idea1)
            // ----------------------------------------------------------------
            $this->command->info('  → Creating project…');

            $project = Project::firstOrCreate(
                ['idea_id' => $idea1->id],
                [
                    'lead_user_id'     => $elena->id,
                    'category_id'      => $catWeb->id,
                    'title'            => 'Community Skill Exchange Portal',
                    'description'      => 'An open-source time-credit platform enabling peer-to-peer skill exchange within local communities. This project converts the approved IdeaVault proposal into an active development effort.',
                    'target_hours'     => '80.00',
                    'hours_contributed'=> '12.00',
                    'status'           => 'active',
                ]
            );

            // ----------------------------------------------------------------
            // 9. PROJECT MEMBERS (3)
            // ----------------------------------------------------------------
            $this->command->info('  → Creating project members…');

            ProjectMember::firstOrCreate(
                ['project_id' => $project->id, 'user_id' => $elena->id],
                [
                    'member_role' => 'lead',
                    'hours_logged'=> '5.00',
                    'joined_at'   => now()->subDays(14),
                ]
            );

            ProjectMember::firstOrCreate(
                ['project_id' => $project->id, 'user_id' => $alex->id],
                [
                    'member_role' => 'contributor',
                    'hours_logged'=> '4.00',
                    'joined_at'   => now()->subDays(12),
                ]
            );

            ProjectMember::firstOrCreate(
                ['project_id' => $project->id, 'user_id' => $marcus->id],
                [
                    'member_role' => 'contributor',
                    'hours_logged'=> '3.00',
                    'joined_at'   => now()->subDays(10),
                ]
            );

            // ----------------------------------------------------------------
            // 10. PROJECT TASKS (3)
            // ----------------------------------------------------------------
            $this->command->info('  → Creating project tasks…');

            ProjectTask::firstOrCreate(
                ['project_id' => $project->id, 'title' => 'Set up Laravel API scaffolding'],
                [
                    'assigned_to' => $elena->id,
                    'description' => 'Initialise the Laravel backend, configure authentication, set up migrations for the user, service, and request tables, and document the API contract.',
                    'target_hours'=> '8.00',
                    'status'      => 'completed',
                    'order_index' => 1,
                ]
            );

            ProjectTask::firstOrCreate(
                ['project_id' => $project->id, 'title' => 'Design component library in Figma'],
                [
                    'assigned_to' => $marcus->id,
                    'description' => 'Create a Figma design system covering primary buttons, form inputs, cards, navigation, and modal components aligned with the brand colour palette.',
                    'target_hours'=> '6.00',
                    'status'      => 'in_progress',
                    'order_index' => 2,
                ]
            );

            ProjectTask::firstOrCreate(
                ['project_id' => $project->id, 'title' => 'Build React service marketplace page'],
                [
                    'assigned_to' => $alex->id,
                    'description' => 'Implement the skill marketplace listing page with search, filter by category, and service card components. Connect to Laravel API endpoints.',
                    'target_hours'=> '10.00',
                    'status'      => 'pending',
                    'order_index' => 3,
                ]
            );

            // ----------------------------------------------------------------
            // 11. NOTIFICATIONS (10)
            // ----------------------------------------------------------------
            $this->command->info('  → Creating notifications…');

            $notifData = [
                [
                    'recipient' => $alex,
                    'type'      => 'App\Notifications\ServiceRequestStatusChangedNotification',
                    'key'       => 'demo-notif-sr-accepted-alex',
                    'data'      => ['service_request_id' => $srInProgress1->id, 'title' => $srInProgress1->title, 'status' => 'accepted'],
                ],
                [
                    'recipient' => $marcus,
                    'type'      => 'App\Notifications\ServiceRequestStatusChangedNotification',
                    'key'       => 'demo-notif-sr-completed-marcus',
                    'data'      => ['service_request_id' => $srAlex1->id, 'title' => $srAlex1->title, 'status' => 'completed'],
                ],
                [
                    'recipient' => $sarah,
                    'type'      => 'App\Notifications\ServiceRequestStatusChangedNotification',
                    'key'       => 'demo-notif-sr-completed-sarah',
                    'data'      => ['service_request_id' => $srMaya1->id, 'title' => $srMaya1->title, 'status' => 'completed'],
                ],
                [
                    'recipient' => $alex,
                    'type'      => 'App\Notifications\ReviewReceivedNotification',
                    'key'       => 'demo-notif-review-alex',
                    'data'      => ['service_request_id' => $srAlex1->id, 'rating' => 5, 'message' => 'You received a 5-star review from Marcus Chen.'],
                ],
                [
                    'recipient' => $maya,
                    'type'      => 'App\Notifications\ReviewReceivedNotification',
                    'key'       => 'demo-notif-review-maya',
                    'data'      => ['service_request_id' => $srMaya1->id, 'rating' => 5, 'message' => 'You received a 5-star review from Sarah Jenkins.'],
                ],
                [
                    'recipient' => $elena,
                    'type'      => 'App\Notifications\IdeaCollaboratorStatusChangedNotification',
                    'key'       => 'demo-notif-collab-elena-alex',
                    'data'      => ['idea_id' => $idea1->id, 'title' => $idea1->title, 'applicant' => 'Alex Rivera', 'status' => 'accepted'],
                ],
                [
                    'recipient' => $alex,
                    'type'      => 'App\Notifications\ProjectMemberAddedNotification',
                    'key'       => 'demo-notif-project-member-alex',
                    'data'      => ['project_id' => $project->id, 'title' => $project->title, 'message' => 'You have been added to the Community Skill Exchange Portal project.'],
                ],
                [
                    'recipient' => $marcus,
                    'type'      => 'App\Notifications\ProjectMemberAddedNotification',
                    'key'       => 'demo-notif-project-member-marcus',
                    'data'      => ['project_id' => $project->id, 'title' => $project->title, 'message' => 'You have been added to the Community Skill Exchange Portal project.'],
                ],
                [
                    'recipient' => $marcus,
                    'type'      => 'App\Notifications\ProjectTaskAssignedNotification',
                    'key'       => 'demo-notif-task-marcus',
                    'data'      => ['project_id' => $project->id, 'task' => 'Design component library in Figma', 'message' => 'A new task has been assigned to you.'],
                ],
                [
                    'recipient' => $daniel,
                    'type'      => 'App\Notifications\ServiceRequestStatusChangedNotification',
                    'key'       => 'demo-notif-sr-inprogress-daniel',
                    'data'      => ['service_request_id' => $srInProgress2->id, 'title' => $srInProgress2->title, 'status' => 'in_progress'],
                ],
            ];

            foreach ($notifData as $n) {
                $deterministicId = \Ramsey\Uuid\Uuid::uuid5(\Ramsey\Uuid\Uuid::NAMESPACE_DNS, $n['key'])->toString();
                $exists = DB::table('notifications')->where('id', (string) $deterministicId)->exists();
                if (! $exists) {
                    DB::table('notifications')->insert([
                        'id'              => (string) $deterministicId,
                        'type'            => $n['type'],
                        'notifiable_type' => User::class,
                        'notifiable_id'   => $n['recipient']->id,
                        'data'            => json_encode($n['data']),
                        'read_at'         => null,
                        'created_at'      => now()->subHours(rand(1, 72)),
                        'updated_at'      => now()->subHours(rand(1, 24)),
                    ]);
                }
            }

            // ----------------------------------------------------------------
            // 12. OTP RECORDS (2) — for in-progress SRs
            // ----------------------------------------------------------------
            $this->command->info('  → Creating OTP records…');

            ServiceRequestOtp::firstOrCreate(
                ['service_request_id' => $srInProgress1->id, 'user_id' => $alex->id, 'used_at' => null],
                [
                    'otp_hash'    => Hash::make('123456'),
                    'attempts'    => 0,
                    'max_attempts'=> 5,
                    'expires_at'  => now()->addMinutes(15),
                ]
            );

            ServiceRequestOtp::firstOrCreate(
                ['service_request_id' => $srInProgress2->id, 'user_id' => $daniel->id, 'used_at' => null],
                [
                    'otp_hash'    => Hash::make('654321'),
                    'attempts'    => 0,
                    'max_attempts'=> 5,
                    'expires_at'  => now()->addMinutes(15),
                ]
            );

            // ----------------------------------------------------------------
            // 13. CHAT MESSAGES (9) — on in-progress SRs
            // ----------------------------------------------------------------
            $this->command->info('  → Creating chat messages…');

            $messageGroups = [
                // 4 messages on srInProgress1 (Alex ↔ Marcus — mobile design review)
                [
                    'service_request_id' => $srInProgress1->id,
                    'sender_id'          => $alex->id,
                    'content'            => 'Hi Marcus! Looking forward to the mobile redesign review. Should I share a Figma link or a live staging URL for the current layout?',
                    'read_at'            => now()->subHours(22),
                ],
                [
                    'service_request_id' => $srInProgress1->id,
                    'sender_id'          => $marcus->id,
                    'content'            => 'Hi Alex! A Figma link would be ideal so I can leave comments directly on the frames. Also, which breakpoints are you most concerned about — 375px or 414px?',
                    'read_at'            => now()->subHours(20),
                ],
                [
                    'service_request_id' => $srInProgress1->id,
                    'sender_id'          => $alex->id,
                    'content'            => 'Sharing the Figma link now. Main concerns are the 375px breakpoint for the filter panel and the touch target sizes on the bottom navigation. Some icons are too small.',
                    'read_at'            => now()->subHours(18),
                ],
                [
                    'service_request_id' => $srInProgress1->id,
                    'sender_id'          => $marcus->id,
                    'content'            => 'Got it — I will audit the touch targets against the 44px minimum and check the filter panel collapse behaviour on 375px. Will share annotated frames in 24 hours.',
                    'read_at'            => null,
                ],
                // 3 messages on srInProgress2 (Daniel ↔ Elena — CI/CD pipeline)
                [
                    'service_request_id' => $srInProgress2->id,
                    'sender_id'          => $daniel->id,
                    'content'            => 'Hello Elena! We are moving a multi-package repo to a monorepo and need to establish a solid branching strategy. The team is 6 developers. Would trunk-based development work here?',
                    'read_at'            => now()->subHours(30),
                ],
                [
                    'service_request_id' => $srInProgress2->id,
                    'sender_id'          => $elena->id,
                    'content'            => 'Trunk-based development works really well for teams your size. The key is short-lived feature flags and robust CI checks on every commit. Do you currently have any CI pipeline in place?',
                    'read_at'            => now()->subHours(26),
                ],
                [
                    'service_request_id' => $srInProgress2->id,
                    'sender_id'          => $daniel->id,
                    'content'            => 'We have basic GitHub Actions for linting but nothing for integration tests yet. That is one of the things I would like your help setting up during our session.',
                    'read_at'            => null,
                ],
                // 2 messages on srInProgress3 (Maya ↔ Sophia — onboarding guide)
                [
                    'service_request_id' => $srInProgress3->id,
                    'sender_id'          => $maya->id,
                    'content'            => 'Hi Sophia! I am sharing our ML platform API spec as a JSON schema. The guide should cover environment setup, three key endpoints, and two common workflow examples. Shall we start with the setup section?',
                    'read_at'            => now()->subHours(10),
                ],
                [
                    'service_request_id' => $srInProgress3->id,
                    'sender_id'          => $sophia->id,
                    'content'            => 'Sounds great — starting with environment setup makes sense. I will draft the first two sections today and share a Google Doc link for your review. YAML or Markdown format for the code samples?',
                    'read_at'            => null,
                ],
            ];

            foreach ($messageGroups as $msg) {
                $exists = ServiceRequestMessage::where('service_request_id', $msg['service_request_id'])
                    ->where('sender_id', $msg['sender_id'])
                    ->where('content', $msg['content'])
                    ->exists();
                if (! $exists) {
                    ServiceRequestMessage::create($msg);
                }
            }
        });

        $this->command->info('DemoDataSeeder completed successfully.');
    }
}
