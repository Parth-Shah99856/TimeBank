@section('title', $idea->title)

<x-app-layout>
    @php
        $targetHours = (float)($idea->target_hours ?? 0);
        $hoursCommitted = (float)$idea->collaborators->where('status', 'accepted')->sum('hours_pledged');
        $isOwner = Auth::id() === $idea->user_id;
        $hasApplied = Auth::check() ? $idea->collaborators->where('user_id', Auth::id())->isNotEmpty() : false;
        $activeProject = $idea->projects->first();
    @endphp

    <div class="max-w-4xl mx-auto space-y-8" x-data="{ applyModal: false }">
        {{-- Back Link --}}
        <div>
            <a href="{{ route('ideas.index') }}" class="inline-flex items-center gap-1.5 font-label-caps text-xs text-on-surface-variant hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                Back to Vault
            </a>
        </div>

        {{-- Hero Header Card --}}
        <div class="glass-card p-6 md:p-10 rounded-2xl relative overflow-hidden group">
            <div class="absolute -right-20 -top-20 w-72 h-72 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex items-center justify-between mb-4">
                <x-badge :variant="'cyan'">
                    <span class="material-symbols-outlined text-[14px] mr-1">lightbulb</span>
                    {{ $idea->category->name ?? 'Initiative' }}
                </x-badge>
                <span class="font-mono-data text-xs text-on-surface-variant flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px] text-secondary">schedule</span>
                    {{ number_format($targetHours, 0) }} Hrs Needed
                </span>
            </div>

            <h1 class="font-headline-lg text-3xl md:text-4xl lg:text-5xl font-bold text-on-surface mb-4">
                {{ $idea->title }}
            </h1>

            <p class="font-body-lg text-sm md:text-base text-on-surface-variant leading-relaxed mb-8 max-w-2xl">
                {{ $idea->mission_statement }}
            </p>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center gap-4 pt-6 border-t border-white/10">
                @if($activeProject)
                    <a href="{{ route('projects.show', $activeProject->id) }}" class="btn-stitch-primary text-xs py-3.5 px-8 shadow-[0_0_16px_rgba(93,230,255,0.35)]">
                        <span class="material-symbols-outlined text-[18px]">hub</span> VIEW ACTIVE PROJECT BOARD
                    </a>
                @elseif(!$isOwner)
                    @auth
                        @if(!$hasApplied)
                            <button @click="applyModal = true" class="btn-stitch-primary text-xs py-3.5 px-8 shadow-[0_0_16px_rgba(93,230,255,0.35)]">
                                <span class="material-symbols-outlined text-[18px]">handshake</span> JOIN AS COLLABORATOR
                            </button>
                        @else
                            <div class="inline-flex items-center gap-2 px-4 py-3 rounded-lg bg-secondary/10 border border-secondary/30 font-label-caps text-xs text-secondary">
                                <span class="material-symbols-outlined text-[16px]">check_circle</span> Application Submitted
                            </div>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn-stitch-primary text-xs py-3.5 px-8">
                            SIGN IN TO COLLABORATE
                        </a>
                    @endauth
                @else
                    {{-- Convert to Project Button for Idea Owner --}}
                    <form method="POST" action="{{ route('ideas.project.store', $idea->id) }}">
                        @csrf
                        <button type="submit" class="btn-stitch-primary text-xs py-3.5 px-8 shadow-[0_0_16px_rgba(93,230,255,0.35)]">
                            <span class="material-symbols-outlined text-[18px]">rocket_launch</span> CONVERT TO ACTIVE PROJECT
                        </button>
                    </form>
                @endif
                <button onclick="navigator.clipboard.writeText(window.location.href); alert('Initiative link copied to clipboard.');" class="btn-stitch-secondary text-xs py-3.5 px-6">
                    <span class="material-symbols-outlined text-[18px]">share</span> SHARE
                </button>
            </div>
        </div>

        {{-- Resource Allocation & Core Team Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Resource Stats --}}
            <div class="glass-card p-6 rounded-2xl flex flex-col justify-between">
                <div>
                    <span class="font-label-caps text-[11px] text-on-surface-variant block mb-4">RESOURCE ALLOCATION</span>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="font-display-lg text-3xl font-bold text-on-surface">{{ number_format($hoursCommitted, 0) }}</span>
                            <span class="font-mono-data text-xs text-on-surface-variant block mt-1">Hours Pledged</span>
                        </div>
                        <div>
                            <span class="font-display-lg text-3xl font-bold text-secondary">{{ number_format($targetHours, 0) }}</span>
                            <span class="font-mono-data text-xs text-on-surface-variant block mt-1">Hours Target</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-white/10">
                    <span class="font-label-caps text-[10px] text-on-surface-variant block mb-2">REQUIRED SKILLS</span>
                    <div class="flex flex-wrap gap-2">
                        @if(!empty($idea->required_skills) && is_array($idea->required_skills))
                            @foreach($idea->required_skills as $skill)
                                <span class="px-2.5 py-1 rounded bg-surface-container-high font-mono-data text-xs text-on-surface-variant border border-white/5">
                                    {{ $skill }}
                                </span>
                            @endforeach
                        @else
                            <span class="font-mono-data text-xs text-on-surface-variant/60">Open for multidisciplinary contributors</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Core Team Card --}}
            <div class="glass-card p-6 rounded-2xl flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-label-caps text-[11px] text-on-surface-variant">CORE TEAM</span>
                        @php
                            $acceptedCollabs = $idea->collaborators->where('status', 'accepted');
                            $pendingCollabs = $idea->collaborators->where('status', 'pending');
                        @endphp
                        <span class="font-mono-data text-xs text-secondary">{{ $acceptedCollabs->count() + 1 }} Nodes</span>
                    </div>

                    <div class="space-y-3">
                        {{-- Project Lead --}}
                        <div class="flex items-center gap-3 p-2 rounded-xl bg-surface-container-high/40">
                            <x-avatar :user="$idea->user" size="md" />
                            <div>
                                <h4 class="font-headline text-sm font-semibold text-on-surface">{{ $idea->user->name }}</h4>
                                <span class="font-label-caps text-[10px] text-secondary">PROJECT LEAD &bull; ARCHITECT</span>
                            </div>
                        </div>

                        {{-- Accepted Collaborators --}}
                        @foreach($acceptedCollabs->take(3) as $collab)
                            <div class="flex items-center gap-3 p-2 rounded-xl bg-surface-container-high/40">
                                <x-avatar :user="$collab->user" size="md" />
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-headline text-sm font-semibold text-on-surface truncate">{{ $collab->user->name ?? 'Collaborator' }}</h4>
                                    <span class="font-label-caps text-[10px] text-on-surface-variant">{{ $collab->role_offered ?? 'Contributor' }} &bull; {{ $collab->hours_pledged ?? 0 }}h pledged</span>
                                </div>
                                <span class="w-2 h-2 rounded-full bg-emerald-400 flex-shrink-0" title="Accepted"></span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pending Applications (Owner Only) --}}
                    @if($isOwner && $pendingCollabs->count() > 0)
                        <div class="mt-4 pt-4 border-t border-white/10 space-y-2">
                            <span class="font-label-caps text-[10px] text-tertiary flex items-center gap-1.5 mb-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-tertiary animate-ping"></span>
                                {{ $pendingCollabs->count() }} PENDING APPLICATION{{ $pendingCollabs->count() > 1 ? 'S' : '' }}
                            </span>
                            @foreach($pendingCollabs as $collab)
                                <div class="p-3 rounded-xl bg-tertiary/5 border border-tertiary/20 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <x-avatar :user="$collab->user" size="sm" />
                                        <div class="flex-1 min-w-0">
                                            <p class="font-headline text-xs font-semibold text-on-surface truncate">{{ $collab->user->name }}</p>
                                            <p class="font-mono-data text-[10px] text-on-surface-variant">{{ $collab->role_offered ?? 'Contributor' }} &bull; {{ $collab->hours_pledged }}h</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('idea-collaborators.update', $collab->id) }}" class="flex-1">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="accepted">
                                            <button type="submit" class="btn-stitch-primary text-[10px] py-1.5 w-full justify-center">
                                                <span class="material-symbols-outlined text-[14px]">check_circle</span> Accept
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('idea-collaborators.update', $collab->id) }}" class="flex-1">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="btn-stitch-danger text-[10px] py-1.5 w-full justify-center">
                                                <span class="material-symbols-outlined text-[14px]">cancel</span> Decline
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="pt-4 border-t border-white/10 text-center font-mono-data text-xs text-on-surface-variant mt-4">
                    {{ $idea->collaborators->count() + 1 }} total architects &bull; Open for contributions
                </div>
            </div>
        </div>

        {{-- Collaborator Application Modal --}}
        <div x-show="applyModal" class="stitch-overlay" style="display: none;" @click.self="applyModal = false">
            <div class="stitch-modal animate-fade-in-up">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/10">
                    <h3 class="font-headline-md text-lg font-bold text-on-surface">Apply as Collaborator</h3>
                    <button @click="applyModal = false" class="text-on-surface-variant hover:text-white">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('ideas.collaborators.store', $idea->id) }}">
                    @csrf
                    <div class="mb-4">
                        <label for="role_offered" class="stitch-label">DESIRED ROLE / SPECIALIZATION</label>
                        <input id="role_offered" type="text" name="role_offered" class="stitch-input" placeholder="e.g., Lead CAD Engineer, System Architect">
                    </div>

                    <div class="mb-6">
                        <label for="hours_pledged" class="stitch-label">HOURS PLEDGED (TIME BUDGET) *</label>
                        <input id="hours_pledged" type="number" step="1" min="1" name="hours_pledged" value="10" required class="stitch-input" placeholder="10">
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="applyModal = false" class="btn-stitch-secondary w-1/3 text-xs justify-center">CANCEL</button>
                        <button type="submit" class="btn-stitch-primary w-2/3 text-xs justify-center shadow-[0_0_12px_rgba(93,230,255,0.3)]">SUBMIT APPLICATION</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
