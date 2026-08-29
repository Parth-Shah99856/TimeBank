@section('title', $project->title)

<x-app-layout>
    @php
        $targetHours = (float)($project->target_hours ?? 5000);
        $contributed = (float)($project->hours_contributed ?? 3420);
        $completion = $targetHours > 0 ? min(100, round(($contributed / $targetHours) * 100)) : 68;
        $remaining = max(0, $targetHours - $contributed);
        $members = $project->members()->with('user')->get();
        $tasks = $project->tasks()->with('assignee')->latest()->get();
        $isLead = Auth::id() === $project->lead_user_id;
    @endphp

    <div class="max-w-5xl mx-auto space-y-8" x-data="{ tab: 'progress', memberModal: false, taskModal: false }">
        {{-- Header Section --}}
        <div class="glass-card p-6 md:p-10 rounded-2xl relative overflow-hidden group">
            <div class="absolute -right-20 -top-20 w-72 h-72 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2.5">
                    <x-badge :variant="$project->status ?? 'active'">
                        {{ ucfirst(str_replace('_', ' ', $project->status ?? 'active')) }}
                    </x-badge>
                    <span class="font-mono-data text-xs text-on-surface-variant">ID: PRJ-{{ str_pad($project->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    @if($isLead)
                        <button @click="memberModal = true" class="btn-stitch-secondary text-xs py-1.5 px-3">
                            <span class="material-symbols-outlined text-[16px]">person_add</span> Invite Node
                        </button>
                        <button @click="taskModal = true" class="btn-stitch-primary text-xs py-1.5 px-3 shadow-[0_0_12px_rgba(93,230,255,0.3)]">
                            <span class="material-symbols-outlined text-[16px]">add_task</span> Add Task
                        </button>
                    @endif
                </div>
            </div>

            <h1 class="font-headline-lg text-2xl md:text-3xl lg:text-4xl font-bold text-on-surface mb-3">
                {{ $project->title }}
            </h1>

            <p class="font-body-md text-sm md:text-base text-on-surface-variant leading-relaxed mb-6 max-w-3xl">
                {{ $project->description ?? 'Deploying decentralized architecture across network sectors to establish low-latency chronal synchronization channels.' }}
            </p>

            {{-- Stat Metrics Bar --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6 border-t border-white/10">
                <div>
                    <span class="font-label-caps text-[10px] text-on-surface-variant block">Completion</span>
                    <span class="font-display-lg text-2xl font-bold text-secondary">{{ $completion }}%</span>
                </div>
                <div>
                    <span class="font-label-caps text-[10px] text-on-surface-variant block">Time Staked</span>
                    <span class="font-display-lg text-2xl font-bold text-on-surface">{{ number_format($contributed, 0) }} <span class="text-xs font-mono font-normal">hrs</span></span>
                </div>
                <div>
                    <span class="font-label-caps text-[10px] text-on-surface-variant block">Active Nodes</span>
                    <span class="font-display-lg text-2xl font-bold text-tertiary">{{ max(1, $members->count()) }}</span>
                </div>
                <div>
                    <span class="font-label-caps text-[10px] text-on-surface-variant block">Remaining</span>
                    <span class="font-display-lg text-2xl font-bold text-on-surface-variant">{{ number_format($remaining, 0) }} <span class="text-xs font-mono font-normal">hrs</span></span>
                </div>
            </div>
        </div>

        {{-- Navigation Tabs: Progress / Team / Tasks --}}
        <div class="flex gap-4 border-b border-white/10 pb-4">
            <button @click="tab = 'progress'"
                    class="font-label-caps text-xs pb-2 px-3 transition-colors flex items-center gap-2"
                    :class="tab === 'progress' ? 'text-secondary border-b-2 border-secondary font-bold drop-shadow-[0_0_8px_rgba(93,230,255,0.4)]' : 'text-on-surface-variant hover:text-white'">
                <span class="material-symbols-outlined text-[18px]">show_chart</span> Project Progress
            </button>
            <button @click="tab = 'team'"
                    class="font-label-caps text-xs pb-2 px-3 transition-colors flex items-center gap-2"
                    :class="tab === 'team' ? 'text-secondary border-b-2 border-secondary font-bold drop-shadow-[0_0_8px_rgba(93,230,255,0.4)]' : 'text-on-surface-variant hover:text-white'">
                <span class="material-symbols-outlined text-[18px]">group</span> Active Roster ({{ $members->count() }})
            </button>
            <button @click="tab = 'tasks'"
                    class="font-label-caps text-xs pb-2 px-3 transition-colors flex items-center gap-2"
                    :class="tab === 'tasks' ? 'text-secondary border-b-2 border-secondary font-bold drop-shadow-[0_0_8px_rgba(93,230,255,0.4)]' : 'text-on-surface-variant hover:text-white'">
                <span class="material-symbols-outlined text-[18px]">task</span> Task Board ({{ $tasks->count() }})
            </button>
        </div>

        {{-- TAB 1: PROJECT PROGRESS (Milestones & Activity) --}}
        <div x-show="tab === 'progress'" class="space-y-8">
            {{-- Milestones Timeline --}}
            <div class="glass-card p-6 md:p-8 rounded-2xl">
                <h3 class="font-headline-md text-lg font-bold text-on-surface mb-6 flex items-center justify-between">
                    <span>Milestones</span>
                    <span class="font-mono-data text-xs text-secondary font-normal">Phase 2 Active</span>
                </h3>

                <div class="space-y-6 relative border-l-2 border-surface-container-high ml-4 pl-6">
                    {{-- Phase 1 --}}
                    <div class="relative">
                        <div class="absolute -left-[31px] top-0 w-4 h-4 rounded-full bg-secondary shadow-[0_0_8px_rgba(93,230,255,1)] flex items-center justify-center">
                            <span class="material-symbols-outlined text-[10px] text-background fill">check</span>
                        </div>
                        <span class="font-label-caps text-[10px] text-secondary block">PHASE 1 &bull; COMPLETED</span>
                        <h4 class="font-headline text-base font-semibold text-on-surface mt-0.5">Core Node Architecture</h4>
                        <p class="font-body-md text-xs text-on-surface-variant mt-1">Established foundational quantum links and verified stability across initial 50 test nodes.</p>
                    </div>

                    {{-- Phase 2 --}}
                    <div class="relative">
                        <div class="absolute -left-[31px] top-0 w-4 h-4 rounded-full bg-secondary/20 border-2 border-secondary animate-pulse"></div>
                        <span class="font-label-caps text-[10px] text-secondary block">PHASE 2 &bull; IN PROGRESS</span>
                        <h4 class="font-headline text-base font-semibold text-on-surface mt-0.5">Synchronization Protocol</h4>
                        <p class="font-body-md text-xs text-on-surface-variant mt-1 mb-2">Developing consensus algorithms for chronal alignment. Debugging latency spikes.</p>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-surface-container-high text-xs font-mono-data text-secondary">
                            <span class="w-2 h-2 rounded-full bg-secondary animate-ping"></span>
                            +12 contributors active
                        </div>
                    </div>

                    {{-- Phase 3 --}}
                    <div class="relative">
                        <div class="absolute -left-[31px] top-0 w-4 h-4 rounded-full bg-surface-container-high border border-white/20"></div>
                        <span class="font-label-caps text-[10px] text-on-surface-variant/60 block">PHASE 3 &bull; LOCKED</span>
                        <h4 class="font-headline text-base font-semibold text-on-surface-variant mt-0.5">Network Deployment</h4>
                        <p class="font-body-md text-xs text-on-surface-variant/60 mt-1">Requires Phase 2 completion. Full scale rollout to all operational sectors.</p>
                    </div>
                </div>
            </div>

            {{-- Recent Activity Nexus Stream --}}
            <div class="glass-card p-6 md:p-8 rounded-2xl">
                <h3 class="font-headline-md text-lg font-bold text-on-surface mb-6">Recent Activity</h3>
                <div class="space-y-4">
                    <div class="p-3.5 rounded-xl bg-surface-container-high/40 border border-white/5 flex items-center gap-3">
                        <span class="material-symbols-outlined text-[18px] text-secondary">sync</span>
                        <div class="flex-1 text-xs">
                            <span class="font-semibold text-on-surface">Optimized synch loop in Node 42</span>
                            <span class="text-on-surface-variant block font-mono">by @zara_k &bull; 2h ago</span>
                        </div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-surface-container-high/40 border border-white/5 flex items-center gap-3">
                        <span class="material-symbols-outlined text-[18px] text-tertiary">warning</span>
                        <div class="flex-1 text-xs">
                            <span class="font-semibold text-on-surface">Issue resolved: Latency spike Sector 7</span>
                            <span class="text-on-surface-variant block font-mono">by @dr_chronos &bull; 5h ago</span>
                        </div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-surface-container-high/40 border border-white/5 flex items-center gap-3">
                        <span class="material-symbols-outlined text-[18px] text-secondary">commit</span>
                        <div class="flex-1 text-xs">
                            <span class="font-semibold text-on-surface">Refactored consensus algorithm</span>
                            <span class="text-on-surface-variant block font-mono">by @sys_admin_1 &bull; 1d ago</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 2: ACTIVE ROSTER / TEAM --}}
        <div x-show="tab === 'team'" class="space-y-6" style="display: none;">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Lead Architect --}}
                <div class="glass-card p-5 rounded-xl flex items-center justify-between border-secondary/30">
                    <div class="flex items-center gap-3.5">
                        <x-avatar :user="$project->leadUser" size="md" />
                        <div>
                            <h4 class="font-headline text-sm font-bold text-on-surface">{{ $project->leadUser->name }}</h4>
                            <span class="font-label-caps text-[10px] text-secondary">LEAD ARCHITECT</span>
                        </div>
                    </div>
                    <div class="text-right font-mono-data text-xs">
                        <span class="text-secondary font-bold">Lead</span>
                    </div>
                </div>

                {{-- Team Members --}}
                @foreach($members as $member)
                    <div class="glass-card p-5 rounded-xl flex items-center justify-between">
                        <div class="flex items-center gap-3.5">
                            <x-avatar :user="$member->user" size="md" />
                            <div>
                                <h4 class="font-headline text-sm font-semibold text-on-surface">{{ $member->user->name ?? 'Node Contributor' }}</h4>
                                <span class="font-label-caps text-[10px] text-on-surface-variant">{{ $member->member_role ?? 'Contributor' }}</span>
                            </div>
                        </div>
                        <div class="text-right font-mono-data text-xs">
                            <span class="text-on-surface font-semibold">{{ number_format($member->hours_contributed ?? 0, 0) }} hrs</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- TAB 3: TASK BOARD --}}
        <div x-show="tab === 'tasks'" class="space-y-6" style="display: none;">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- To Do / Pending --}}
                <div class="glass-card p-4 rounded-xl space-y-3">
                    <span class="font-label-caps text-xs text-on-surface-variant flex items-center gap-1.5 pb-2 border-b border-white/10">
                        <span class="w-2 h-2 rounded-full bg-surface-container-highest"></span> TO DO
                    </span>
                    @forelse($tasks->where('status', 'pending') as $task)
                        <div class="p-3 rounded-lg bg-surface-container-high/60 border border-white/5">
                            <h5 class="font-headline text-xs font-semibold text-on-surface">{{ $task->title }}</h5>
                            <p class="font-mono-data text-[10px] text-on-surface-variant mt-1">{{ $task->target_hours ?? 2 }}h budget</p>
                        </div>
                    @empty
                        <p class="font-mono-data text-xs text-on-surface-variant/50 py-4 text-center">No pending tasks</p>
                    @endforelse
                </div>

                {{-- In Progress --}}
                <div class="glass-card p-4 rounded-xl space-y-3">
                    <span class="font-label-caps text-xs text-secondary flex items-center gap-1.5 pb-2 border-b border-white/10">
                        <span class="w-2 h-2 rounded-full bg-secondary shadow-[0_0_6px_rgba(93,230,255,1)]"></span> IN PROGRESS
                    </span>
                    @forelse($tasks->where('status', 'in_progress') as $task)
                        <div class="p-3 rounded-lg bg-surface-container-high/60 border border-secondary/20">
                            <h5 class="font-headline text-xs font-semibold text-on-surface">{{ $task->title }}</h5>
                            <p class="font-mono-data text-[10px] text-secondary mt-1">Assigned: {{ $task->assignee->name ?? 'Unassigned' }}</p>
                        </div>
                    @empty
                        <p class="font-mono-data text-xs text-on-surface-variant/50 py-4 text-center">No active tasks</p>
                    @endforelse
                </div>

                {{-- Completed --}}
                <div class="glass-card p-4 rounded-xl space-y-3">
                    <span class="font-label-caps text-xs text-emerald-400 flex items-center gap-1.5 pb-2 border-b border-white/10">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span> COMPLETED
                    </span>
                    @forelse($tasks->where('status', 'completed') as $task)
                        <div class="p-3 rounded-lg bg-surface-container-high/40 border border-white/5 opacity-80">
                            <h5 class="font-headline text-xs font-semibold text-on-surface line-through">{{ $task->title }}</h5>
                        </div>
                    @empty
                        <p class="font-mono-data text-xs text-on-surface-variant/50 py-4 text-center">No completed tasks yet</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Add Member Modal --}}
        <div x-show="memberModal" class="stitch-overlay" style="display: none;" @click.self="memberModal = false">
            <div class="stitch-modal animate-fade-in-up">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
                    <h3 class="font-headline-md text-base font-bold text-on-surface">Add Project Member</h3>
                    <button @click="memberModal = false" class="text-on-surface-variant hover:text-white">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('projects.members.store', $project->id) }}">
                    @csrf
                    <div class="mb-4">
                        <label for="user_id" class="stitch-label">SELECT ARCHITECT *</label>
                        @php $users = \App\Models\User::where('id', '!=', Auth::id())->get(); @endphp
                        <select id="user_id" name="user_id" required class="stitch-select">
                            <option value="">Choose a user...</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-6">
                        <label for="member_role" class="stitch-label">ROLE</label>
                        <input id="member_role" type="text" name="member_role" class="stitch-input" placeholder="e.g., Research Node, UI Specialist">
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="memberModal = false" class="btn-stitch-secondary w-1/3 text-xs">CANCEL</button>
                        <button type="submit" class="btn-stitch-primary w-2/3 text-xs">ADD MEMBER</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Add Task Modal --}}
        <div x-show="taskModal" class="stitch-overlay" style="display: none;" @click.self="taskModal = false">
            <div class="stitch-modal animate-fade-in-up">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/10">
                    <h3 class="font-headline-md text-base font-bold text-on-surface">Add Project Task</h3>
                    <button @click="taskModal = false" class="text-on-surface-variant hover:text-white">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('projects.tasks.store', $project->id) }}">
                    @csrf
                    <div class="mb-4">
                        <label for="task_title" class="stitch-label">TASK TITLE *</label>
                        <input id="task_title" type="text" name="title" required class="stitch-input" placeholder="e.g., Implement smart contract escrow tests">
                    </div>
                    <div class="mb-4">
                        <label for="task_desc" class="stitch-label">DESCRIPTION</label>
                        <textarea id="task_desc" name="description" rows="3" class="stitch-textarea"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="target_hours" class="stitch-label">TARGET HOURS *</label>
                            <input id="target_hours" type="number" step="0.5" min="0" name="target_hours" class="stitch-input" value="4.0" required>
                        </div>
                        <div>
                            <label for="status" class="stitch-label">INITIAL STATUS *</label>
                            <select id="status" name="status" required class="stitch-select">
                                <option value="pending">To Do (Pending)</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="order_index" value="{{ $tasks->count() }}">
                    <div class="flex gap-3">
                        <button type="button" @click="taskModal = false" class="btn-stitch-secondary w-1/3 text-xs">CANCEL</button>
                        <button type="submit" class="btn-stitch-primary w-2/3 text-xs">CREATE TASK</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
