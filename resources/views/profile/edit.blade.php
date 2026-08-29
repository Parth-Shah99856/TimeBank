@section('title', 'User Profile')

<x-app-layout>
    @php
        $user = $user ?? Auth::user();
        $balance = (float)($user->time_balance ?? 0);
        $services = $user->services ?? collect();
        $projects = ($user->ledProjects ?? collect())->concat(($user->projectMemberships ?? collect())->pluck('project')->filter());
        $completedExchanges = $user->providedServiceRequests ? $user->providedServiceRequests()->where('status', 'completed')->count() : 0;
        $level = max(1, (int)floor($completedExchanges / 2) + 1);
        $progressPct = min(100, max(10, ($completedExchanges % 2) * 50 + 25));
        $totalHoursExchanged = $user->incomingTransactions ? (float)($user->incomingTransactions()->where('type', \App\Models\Transaction::TYPE_SERVICE_EXCHANGE)->sum('amount') + $user->outgoingTransactions()->where('type', \App\Models\Transaction::TYPE_SERVICE_EXCHANGE)->sum('amount')) : 0;
        $rankPosition = \App\Models\User::where('time_balance', '>', (float)$balance)->count() + 1;
    @endphp

    <div class="max-w-4xl mx-auto space-y-8">
        {{-- Profile Hero Card --}}
        <div class="glass-card p-6 md:p-10 rounded-2xl relative overflow-hidden text-center flex flex-col items-center">
            {{-- Glowing background --}}
            <div class="absolute -top-20 w-72 h-72 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>

            {{-- Avatar --}}
            <div class="relative w-24 h-24 md:w-28 md:h-28 rounded-full border-2 border-secondary/50 p-1 shadow-[0_0_20px_rgba(93,230,255,0.4)] mb-4">
                <div class="w-full h-full rounded-full bg-surface-container flex items-center justify-center font-headline text-3xl font-bold text-secondary">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            </div>

            <h1 class="font-headline-lg text-2xl md:text-3xl font-bold text-on-surface mb-1">{{ $user->name }}</h1>
            <p class="font-body-md text-xs md:text-sm text-on-surface-variant max-w-md mb-6">
                {{ $user->headline ?? 'Verified Temporal Architect' }}
            </p>

            {{-- Profile Action Buttons --}}
            <div class="flex items-center gap-3">
                <a href="#edit-forms" class="btn-stitch-primary text-xs py-2.5 px-6 shadow-[0_0_12px_rgba(93,230,255,0.3)]">
                    <span class="material-symbols-outlined text-[16px]">edit</span> EDIT PROFILE
                </a>
                <a href="{{ route('services.create') }}" class="btn-stitch-secondary text-xs py-2.5 px-4">
                    <span class="material-symbols-outlined text-[16px]">add_circle</span> ADD SKILL
                </a>
            </div>

            {{-- Architect Level EXP Card --}}
            <div class="w-full max-w-sm glass-panel p-5 rounded-xl mt-8 border border-white/10 bg-surface-container-high/40">
                <span class="font-label-caps text-[10px] text-on-surface-variant block mb-1">ARCHITECT LEVEL</span>
                <div class="font-display-lg text-4xl font-bold text-secondary drop-shadow-[0_0_12px_rgba(93,230,255,0.5)] mb-3">
                    Level {{ $level }}
                </div>
                <div class="w-full h-1.5 bg-surface-container-highest rounded-full overflow-hidden mb-1.5">
                    <div class="h-full bg-secondary rounded-full shadow-[0_0_8px_rgba(93,230,255,0.8)]" style="width: {{ $progressPct }}%"></div>
                </div>
                <span class="font-mono-data text-[10px] text-on-surface-variant">{{ $completedExchanges }} completed exchanges &bull; {{ number_format($balance, 1) }} TC Staked</span>
            </div>
        </div>

        {{-- Skills Offered Section --}}
        <div class="glass-card p-6 md:p-8 rounded-2xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-headline-md text-lg font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">code</span> Skills Offered
                </h3>
                <a href="{{ route('services.create') }}" class="font-label-caps text-xs text-secondary hover:underline flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">add</span> Add Skill
                </a>
            </div>

            <div class="space-y-4">
                @forelse($services as $srv)
                    <div class="p-4 rounded-xl bg-surface-container-high/40 border border-white/5 space-y-2">
                        <div class="flex items-center justify-between font-headline text-sm font-semibold text-on-surface">
                            <span>{{ $srv->title }}</span>
                            <span class="font-mono-data text-xs text-secondary">{{ number_format($srv->hourly_rate, 2) }} TC/hr</span>
                        </div>
                        <p class="font-body-md text-xs text-on-surface-variant line-clamp-2">{{ $srv->description }}</p>
                    </div>
                @empty
                    <div class="p-6 rounded-xl bg-surface-container-high/20 border border-white/5 text-center">
                        <p class="font-mono-data text-xs text-on-surface-variant mb-3">No skill offerings listed under this identity.</p>
                        <a href="{{ route('services.create') }}" class="btn-stitch-primary text-xs py-2 px-4 inline-flex items-center gap-1.5 shadow-[0_0_12px_rgba(93,230,255,0.25)]">
                            <span class="material-symbols-outlined text-[14px]">add</span> Offer Your First Skill
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Badges Earned Section --}}
        <div class="glass-card p-6 md:p-8 rounded-2xl">
            <h3 class="font-headline-md text-lg font-bold text-on-surface mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-tertiary">military_tech</span> Badges & Network Standing
            </h3>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                {{-- Pioneer --}}
                <div class="p-4 rounded-xl bg-surface-container-high/40 border border-tertiary/30 text-center flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-tertiary/15 flex items-center justify-center text-tertiary mb-2 shadow-[0_0_10px_rgba(249,189,34,0.4)]">
                        <span class="material-symbols-outlined text-[20px] fill">star</span>
                    </div>
                    <span class="font-label-caps text-xs text-on-surface font-bold">PIONEER</span>
                </div>

                {{-- Contributor --}}
                <div class="p-4 rounded-xl bg-surface-container-high/40 border border-secondary/30 text-center flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-secondary/15 flex items-center justify-center text-secondary mb-2 shadow-[0_0_10px_rgba(93,230,255,0.4)]">
                        <span class="material-symbols-outlined text-[20px] fill">speed</span>
                    </div>
                    <span class="font-label-caps text-xs text-on-surface font-bold">{{ $completedExchanges > 0 ? 'ACTIVE' : 'INITIATE' }}</span>
                </div>

                {{-- Mentor --}}
                <div class="p-4 rounded-xl bg-surface-container-high/40 border border-secondary/30 text-center flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full bg-secondary/15 flex items-center justify-center text-secondary mb-2 shadow-[0_0_10px_rgba(93,230,255,0.4)]">
                        <span class="material-symbols-outlined text-[20px] fill">groups</span>
                    </div>
                    <span class="font-label-caps text-xs text-on-surface font-bold">COLLABORATOR</span>
                </div>

                {{-- Architect Node --}}
                <div class="p-4 rounded-xl bg-surface-container-high/20 border border-white/5 text-center flex flex-col items-center {{ $level >= 3 ? 'opacity-100' : 'opacity-40' }}">
                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant mb-2">
                        <span class="material-symbols-outlined text-[20px]">verified</span>
                    </div>
                    <span class="font-label-caps text-xs text-on-surface-variant font-bold">MASTER</span>
                </div>
            </div>

            {{-- Stats Footer --}}
            <div class="pt-6 border-t border-white/10 flex items-center justify-between font-mono-data text-xs">
                <div>
                    <span class="text-on-surface-variant block text-[10px]">Total Hours Exchanged</span>
                    <span class="text-on-surface font-bold text-sm">{{ number_format($totalHoursExchanged, 1) }} hrs</span>
                </div>
                <div class="text-right">
                    <span class="text-on-surface-variant block text-[10px]">Network Rank</span>
                    <span class="text-secondary font-bold text-sm">#{{ $rankPosition }}</span>
                </div>
            </div>
        </div>

        {{-- Account Configuration Forms --}}
        <div id="edit-forms" class="space-y-6 pt-4">
            <h2 class="font-headline-lg text-xl md:text-2xl font-bold text-on-surface">Account Configuration</h2>

            <div class="glass-card p-6 md:p-8 rounded-2xl">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="glass-card p-6 md:p-8 rounded-2xl">
                @include('profile.partials.update-password-form')
            </div>

            <div class="glass-card p-6 md:p-8 rounded-2xl border-error/30">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
