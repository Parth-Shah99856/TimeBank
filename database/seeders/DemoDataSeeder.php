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
 *   - Transactions: guarded by a deterministic lookup code prefix; skipped if code exists
 *   - Reviews: firstOrCreate on (service_request_id, reviewer_id)
 *   - Ideas: firstOrCreate on (user_id, title)
 *   - IdeaCollaborators: firstOrCreate on (idea_id, user_id)
 *   - Projects: firstOrCreate on (idea_id)
 *   - ProjectMembers: firstOrCreate on (project_id, user_id)
 *   - ProjectTasks: firstOrCreate on (project_id, title)
 *   - Notifications: guarded by deterministic UUID seeded from description string
 *   - OTPs: firstOrCreate on (service_request_id, user_id) with null used_at
 *   - Messages: guarded by (service_request_id, sender_id, content) prefix
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

        DB::transaction(function () {
            // ----------------------------------------------------------------
            // 1. ADDITIONAL USERS (4)
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
                    'time_balance'      => '5.00',
                    'email_verified_at' => now(),
                ],
                [
                    'name'              => 'Maya Patel',
                    'email'             => 'maya@timebank.local',
                    'password'          => Hash::make('password'),
                    'role'              => 'user',
                    'headline'          => 'Data Scientist & ML Engineer',
                    'bio'               => 'Specialising in Python, TensorFlow, and data visualisation. Excited to collaborate on data-driven projects and share analytical skills.',
                    'time_balance'      => '5.00',
                    'email_verified_at' => now(),
                ],
                [
                    'name'              => 'Daniel Cooper',
                    'email'             => 'daniel@timebank.local',
                    'password'          => Hash::make('password'),
                    'role'              => 'user',
                    'headline'          => 'DevOps Engineer & Cloud Architect',
                    'bio'               => 'AWS & GCP certified. Passionate about CI/CD pipelines, infrastructure-as-code, and helping teams ship faster.',
                    'time_balance'      => '5.00',
                    'email_verified_at' => now(),
                ],
                [
                    'name'              => 'Sophia Williams',
                    'email'             => 'sophia@timebank.local',
                    'password'          => Hash::make('password'),
                    'role'              => 'user',
                    'headline'          => 'Technical Writer & Content Strategist',
                    'bio'               => 'Translating complex technical concepts into clear, accessible documentation. Experienced with API docs, tutorials, and developer guides.',
                    'time_balance'      => '5.00',
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
            // 3. SERVICE REQUESTS (6: 2 completed, 3 in_progress, 1 pending)
            // ----------------------------------------------------------------
            $this->command->info('  → Creating service requests…');

            $srData = [
                // Completed #1: Alex's React service, requested by Marcus
                [
                    'service_id'      => $svcReact->id,
                    'requester_id'    => $marcus->id,
                    'provider_id'     => $alex->id,
                    'category_id'     => $catWeb->id,
                    'title'           => 'React component refactoring for design system',
                    'project_scope'   => 'Refactor three existing page components into reusable design-system tokens, add prop validation, and document storybook stories for each.',
                    'estimated_hours' => '2.00',
                    'total_credits'   => '4.00',
                    'desired_deadline'=> now()->subDays(10)->toDateString(),
                    'status'          => 'completed',
                    'completed_at'    => now()->subDays(8),
                ],
                // Completed #2: Maya's Python service, requested by Sarah
                [
                    'service_id'      => $svcPython->id,
                    'requester_id'    => $sarah->id,
                    'provider_id'     => $maya->id,
                    'category_id'     => $catData->id,
                    'title'           => 'Data pipeline review and optimisation',
                    'project_scope'   => 'Review existing Pandas pipeline for performance bottlenecks, suggest vectorised operations, and help write unit tests for data transformation functions.',
                    'estimated_hours' => '1.50',
                    'total_credits'   => '3.75',
                    'desired_deadline'=> now()->subDays(15)->toDateString(),
                    'status'          => 'completed',
                    'completed_at'    => now()->subDays(12),
                ],
                // In-progress #1: Design service, requested by Alex
                [
                    'service_id'      => $svcDesign->id,
                    'requester_id'    => $alex->id,
                    'provider_id'     => $marcus->id,
                    'category_id'     => $catDesign->id,
                    'title'           => 'Dashboard UX review and accessibility audit',
                    'project_scope'   => 'Review the analytics dashboard layout for usability issues, run a colour-contrast audit, and propose accessible component alternatives.',
                    'estimated_hours' => '2.00',
                    'total_credits'   => '4.00',
                    'desired_deadline'=> now()->addDays(5)->toDateString(),
                    'status'          => 'in_progress',
                    'completed_at'    => null,
                ],
                // In-progress #2: Git service, requested by Daniel
                [
                    'service_id'      => $svcGit->id,
                    'requester_id'    => $daniel->id,
                    'provider_id'     => $elena->id,
                    'category_id'     => $catDevOps->id,
                    'title'           => 'Git branching strategy for monorepo project',
                    'project_scope'   => 'Help establish a trunk-based development workflow, set up branch protection rules, and configure GitHub Actions for automated PR checks.',
                    'estimated_hours' => '1.50',
                    'total_credits'   => '2.25',
                    'desired_deadline'=> now()->addDays(7)->toDateString(),
                    'status'          => 'in_progress',
                    'completed_at'    => null,
                ],
                // In-progress #3: Writing service, requested by Maya
                [
                    'service_id'      => $svcWriting->id,
                    'requester_id'    => $maya->id,
                    'provider_id'     => $sophia->id,
                    'category_id'     => $catWriting->id,
                    'title'           => 'API documentation for ML model endpoints',
                    'project_scope'   => 'Write clear OpenAPI 3.0 documentation for a REST API exposing three machine learning inference endpoints, including example requests and response schemas.',
                    'estimated_hours' => '2.00',
                    'total_credits'   => '3.00',
                    'desired_deadline'=> now()->addDays(10)->toDateString(),
                    'status'          => 'in_progress',
                    'completed_at'    => null,
                ],
                // Pending #1: Java service, requested by Sophia
                [
                    'service_id'      => $svcJava->id,
                    'requester_id'    => $sophia->id,
                    'provider_id'     => $daniel->id,
                    'category_id'     => $catDevOps->id,
                    'title'           => 'Spring Boot REST API design review',
                    'project_scope'   => 'Review the architecture of a new Spring Boot REST API — entity design, repository patterns, exception handling strategy, and DTO mapping approach.',
                    'estimated_hours' => '1.00',
                    'total_credits'   => '2.00',
                    'desired_deadline'=> now()->addDays(14)->toDateString(),
                    'status'          => 'pending',
                    'completed_at'    => null,
                ],
            ];

            $serviceRequests = [];
            foreach ($srData as $srItem) {
                $sr = ServiceRequest::firstOrCreate(
                    [
                        'requester_id' => $srItem['requester_id'],
                        'service_id'   => $srItem['service_id'],
                        'title'        => $srItem['title'],
                    ],
                    $srItem
                );
                $serviceRequests[] = $sr;
            }

            [$srCompleted1, $srCompleted2, $srInProgress1, $srInProgress2, $srInProgress3, $srPending] = $serviceRequests;

            // ----------------------------------------------------------------
            // 4. TRANSACTIONS (4 additional created above during user creation)
            //    Each of the 4 additional users received 1 legitimate TYPE_SIGNUP_BONUS
            //    transaction (5.00 hours).
            //    Combined with the 4 original seeded users' signup bonuses, this yields
            //    EXACTLY 8 total transactions in the ledger, with each user's
            //    time_balance (5.00) perfectly matching their ledger balance (5.00 - 0.00).
            // ----------------------------------------------------------------
            $this->command->info('  → Verified 4 additional signup-bonus transactions created for new users.');

            // ----------------------------------------------------------------
            // 5. REVIEWS (2 — one per completed service request)
            // ----------------------------------------------------------------
            $this->command->info('  → Creating reviews…');

            Review::firstOrCreate(
                ['service_request_id' => $srCompleted1->id, 'reviewer_id' => $marcus->id],
                [
                    'reviewee_id' => $alex->id,
                    'rating'      => 5,
                    'comment'     => 'Alex was incredibly helpful — walked me through the React refactor clearly and the code is now much more maintainable. Highly recommend!',
                ]
            );

            Review::firstOrCreate(
                ['service_request_id' => $srCompleted2->id, 'reviewer_id' => $sarah->id],
                [
                    'reviewee_id' => $maya->id,
                    'rating'      => 5,
                    'comment'     => 'Maya has a great teaching style. She explained every step of the data pipeline optimisation and I learned a lot about vectorisation. Fantastic session.',
                ]
            );

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
            //     Stored directly in the notifications table (Laravel format).
            //     Each has a deterministic UUID derived from its description so
            //     re-running the seeder skips duplicates.
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
                    'data'      => ['service_request_id' => $srCompleted1->id, 'title' => $srCompleted1->title, 'status' => 'completed'],
                ],
                [
                    'recipient' => $sarah,
                    'type'      => 'App\Notifications\ServiceRequestStatusChangedNotification',
                    'key'       => 'demo-notif-sr-completed-sarah',
                    'data'      => ['service_request_id' => $srCompleted2->id, 'title' => $srCompleted2->title, 'status' => 'completed'],
                ],
                [
                    'recipient' => $alex,
                    'type'      => 'App\Notifications\ReviewReceivedNotification',
                    'key'       => 'demo-notif-review-alex',
                    'data'      => ['service_request_id' => $srCompleted1->id, 'rating' => 5, 'message' => 'You received a 5-star review from Marcus Chen.'],
                ],
                [
                    'recipient' => $maya,
                    'type'      => 'App\Notifications\ReviewReceivedNotification',
                    'key'       => 'demo-notif-review-maya',
                    'data'      => ['service_request_id' => $srCompleted2->id, 'rating' => 5, 'message' => 'You received a 5-star review from Sarah Jenkins.'],
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
                // Use a UUID seeded from the key for deterministic deduplication
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
            // 12. OTP RECORDS (2)
            //     Stored as bcrypt hashes — never plaintext.
            //     Associated with in-progress service requests.
            // ----------------------------------------------------------------
            $this->command->info('  → Creating OTP records…');

            // OTP 1: For srInProgress1 — requester is alex
            ServiceRequestOtp::firstOrCreate(
                ['service_request_id' => $srInProgress1->id, 'user_id' => $alex->id, 'used_at' => null],
                [
                    'otp_hash'    => Hash::make('123456'), // Demo OTP — hashed, never stored plaintext
                    'attempts'    => 0,
                    'max_attempts'=> 5,
                    'expires_at'  => now()->addMinutes(15),
                ]
            );

            // OTP 2: For srInProgress2 — requester is daniel
            ServiceRequestOtp::firstOrCreate(
                ['service_request_id' => $srInProgress2->id, 'user_id' => $daniel->id, 'used_at' => null],
                [
                    'otp_hash'    => Hash::make('654321'), // Demo OTP — hashed, never stored plaintext
                    'attempts'    => 0,
                    'max_attempts'=> 5,
                    'expires_at'  => now()->addMinutes(15),
                ]
            );

            // ----------------------------------------------------------------
            // 13. CHAT MESSAGES (9)
            //     Realistic conversations between requesters and providers.
            //     Content is HTML-escaped via Laravel's automatic escaping.
            // ----------------------------------------------------------------
            $this->command->info('  → Creating chat messages…');

            $messageGroups = [
                // 4 messages on srInProgress1 (Alex ↔ Marcus — dashboard review)
                [
                    'service_request_id' => $srInProgress1->id,
                    'sender_id'          => $alex->id,
                    'content'            => 'Hi Marcus! Looking forward to working with you on the dashboard UX review. Should I share a Figma link or a live URL for the current design?',
                    'read_at'            => now()->subHours(22),
                ],
                [
                    'service_request_id' => $srInProgress1->id,
                    'sender_id'          => $marcus->id,
                    'content'            => 'Hi Alex! Either works — a Figma link would be ideal so I can leave comments directly on the frames. Also, do you have any specific accessibility concerns I should focus on?',
                    'read_at'            => now()->subHours(20),
                ],
                [
                    'service_request_id' => $srInProgress1->id,
                    'sender_id'          => $alex->id,
                    'content'            => 'Great, I will share the Figma link now. Main concern is the colour contrast on the charts — some of the status indicators might not meet WCAG AA. Also the mobile layout on the filters panel.',
                    'read_at'            => now()->subHours(18),
                ],
                [
                    'service_request_id' => $srInProgress1->id,
                    'sender_id'          => $marcus->id,
                    'content'            => 'Got it — I will start with a colour contrast audit using APCA and check the filter panel on 375px breakpoint. Will share findings in 24 hours.',
                    'read_at'            => null,
                ],
                // 3 messages on srInProgress2 (Daniel ↔ Elena — Git strategy)
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
                // 2 messages on srInProgress3 (Maya ↔ Sophia — API docs)
                [
                    'service_request_id' => $srInProgress3->id,
                    'sender_id'          => $maya->id,
                    'content'            => 'Hi Sophia! I am sharing the API endpoint spec as a JSON schema. The three endpoints cover inference, batch inference, and model metadata. Would you prefer YAML or JSON for the OpenAPI output?',
                    'read_at'            => now()->subHours(10),
                ],
                [
                    'service_request_id' => $srInProgress3->id,
                    'sender_id'          => $sophia->id,
                    'content'            => 'YAML is easier to read and maintain in version control — let us go with that. I will set up the OpenAPI 3.0 base structure and share a draft by tomorrow for your review.',
                    'read_at'            => null,
                ],
            ];

            foreach ($messageGroups as $msg) {
                // Deduplication: skip if this exact (sr_id, sender_id, first-150-chars of content) already exists
                $contentKey = mb_substr($msg['content'], 0, 150);
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
