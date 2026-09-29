@section('title', 'Community Leaderboard')

<x-app-layout>
    {{-- Header Section --}}
    <div class="mb-8 max-w-2xl">
        <h1 class="font-headline-lg text-3xl md:text-4xl text-on-surface font-bold mb-2">Community Leaderboard</h1>
        <p class="font-body-md text-sm text-on-surface-variant">Top contributors ranked by dedicated hours, exchange liquidity, and verified community reputation.</p>
    </div>

    @php
        $viewAll = request()->query('view') === 'all';

        // Shared ranking logic: rank by total hours EARNED via service exchanges (to_user),
        // then by current time_balance as secondary sort.
        // Level thresholds mirror dashboard.blade.php and users/show.blade.php exactly:
        //   >= 20 completed provided SRs → Level 5 (96%)
        //   >= 10                        → Level 4 (80%)
        //   >= 5                         → Level 3 (65%)
        //   >= 2                         → Level 2 (45%)
        //   default                      → Level 1 (20%)

        $query = \App\Models\User::query()
            ->withCount(['providedServiceRequests as completed_exchanges_count' => fn($q) => $q->where('status', 'completed')])
            ->withSum(['incomingTransactions as total_hours_earned' => fn($q) => $q->where('type', \App\Models\Transaction::TYPE_SERVICE_EXCHANGE)], 'amount')
            ->withAvg('reviewsReceived as avg_rating', 'rating')
            ->orderByDesc('total_hours_earned')
            ->orderByDesc('time_balance');

        $allCount    = (clone $query)->count();
        $rankedUsers = $viewAll ? $query->get() : $query->take(10)->get();
    @endphp

    {{-- Filter / Tabs --}}
    <div class="flex gap-4 mb-8 border-b border-white/10 pb-4">
        <a href="{{ route('leaderboard') }}"
           class="font-label-caps text-xs pb-2 px-2 transition-colors {{ !$viewAll ? 'text-secondary border-b-2 border-secondary font-bold drop-shadow-[0_0_8px_rgba(93,230,255,0.4)]' : 'text-on-surface-variant/60 hover:text-white' }}">
            Global Rank
        </a>
        <a href="{{ route('leaderboard', ['view' => 'all']) }}"
           class="font-label-caps text-xs pb-2 px-2 transition-colors {{ $viewAll ? 'text-secondary border-b-2 border-secondary font-bold drop-shadow-[0_0_8px_rgba(93,230,255,0.4)]' : 'text-on-surface-variant/60 hover:text-white' }}">
            All Architects ({{ $allCount }})
        </a>
    </div>

    {{-- Leaderboard List --}}
    <div class="space-y-4 max-w-4xl">
        @forelse($rankedUsers as $index => $u)
            @php
                $rank       = $index + 1;
                $hours      = (float)($u->total_hours_earned ?? 0);
                $repScore   = $u->avg_rating ? number_format($u->avg_rating * 20, 1) : null;
                $completed  = (int)($u->completed_exchanges_count ?? 0);
                $archLevel  = match(true) {
                    $completed >= 20 => ['label' => 'Level 5', 'color' => 'text-tertiary'],
                    $completed >= 10 => ['label' => 'Level 4', 'color' => 'text-tertiary'],
                    $completed >= 5  => ['label' => 'Level 3', 'color' => 'text-secondary'],
                    $completed >= 2  => ['label' => 'Level 2', 'color' => 'text-secondary'],
                    default          => ['label' => 'Level 1', 'color' => 'text-on-surface-variant'],
                };
            @endphp

            @if($rank === 1 && !$viewAll)
                {{-- Rank 1 (Gold / Tertiary) --}}
                <div class="glass-card p-4 md:p-5 rounded-2xl flex items-center gap-4 glow-hover transition-all duration-300 relative overflow-hidden group border-tertiary/40">
                    <div class="absolute inset-0 bg-gradient-to-r from-tertiary/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                    <div class="flex-shrink-0 w-10 text-center font-display-lg text-2xl text-tertiary font-bold">
                        1
                    </div>

                    <div class="relative w-14 h-14 flex-shrink-0">
                        <div class="w-full h-full rounded-full bg-surface-container-high border-2 border-tertiary shadow-[0_0_12px_rgba(249,189,34,0.5)] flex items-center justify-center font-bold text-tertiary text-lg font-headline">
                            {{ strtoupper(substr($u->name, 0, 2)) }}
                        </div>
                    </div>

                    <div class="flex-grow min-w-0">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('users.show', $u->id) }}" class="font-headline-md text-base md:text-lg font-bold text-on-surface hover:text-tertiary transition-colors truncate">{{ $u->name }}</a>
                            <span class="px-2 py-0.5 rounded-full bg-tertiary/15 text-tertiary font-label-caps text-[9px] font-bold">LEAD ARCHITECT</span>
                        </div>
                        <p class="font-body-md text-xs text-on-surface-variant truncate">{{ $u->headline ?? 'Verified Temporal Contributor' }}</p>
                    </div>

                    <div class="text-right flex-shrink-0 font-mono-data">
                        <div class="text-secondary flex items-center justify-end gap-1 mb-0.5 text-sm md:text-base font-bold">
                            <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">schedule</span>
                            {{ number_format($hours, 1) }} hrs
                        </div>
                        <div class="font-label-caps text-[11px] {{ $archLevel['color'] }} flex items-center justify-end gap-1">
                            <span class="material-symbols-outlined text-[13px]">military_tech</span>
                            {{ $archLevel['label'] }}
                            @if($repScore)
                                &bull; {{ $repScore }} Rep
                            @endif
                        </div>
                    </div>
                </div>
            @elseif($rank === 2 && !$viewAll)
                {{-- Rank 2 (Cyan / Secondary) --}}
                <div class="glass-card p-4 md:p-5 rounded-2xl flex items-center gap-4 glow-hover transition-all duration-300 relative overflow-hidden group border-secondary/40">
                    <div class="flex-shrink-0 w-10 text-center font-display-lg text-2xl text-secondary font-bold">
                        2
                    </div>

                    <div class="relative w-14 h-14 flex-shrink-0">
                        <div class="w-full h-full rounded-full bg-surface-container-high border-2 border-secondary shadow-[0_0_12px_rgba(93,230,255,0.4)] flex items-center justify-center font-bold text-secondary text-lg font-headline">
                            {{ strtoupper(substr($u->name, 0, 2)) }}
                        </div>
                    </div>

                    <div class="flex-grow min-w-0">
                        <a href="{{ route('users.show', $u->id) }}" class="font-headline-md text-base md:text-lg font-bold text-on-surface hover:text-secondary transition-colors block truncate">{{ $u->name }}</a>
                        <p class="font-body-md text-xs text-on-surface-variant truncate">{{ $u->headline ?? 'Temporal Architect' }}</p>
                    </div>

                    <div class="text-right flex-shrink-0 font-mono-data">
                        <div class="text-secondary flex items-center justify-end gap-1 mb-0.5 text-sm md:text-base font-bold">
                            <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">schedule</span>
                            {{ number_format($hours, 1) }} hrs
                        </div>
                        <div class="font-label-caps text-[11px] {{ $archLevel['color'] }} flex items-center justify-end gap-1">
                            <span class="material-symbols-outlined text-[13px]">military_tech</span>
                            {{ $archLevel['label'] }}
                            @if($repScore)
                                &bull; {{ $repScore }} Rep
                            @endif
                        </div>
                    </div>
                </div>
            @elseif($rank === 3 && !$viewAll)
                {{-- Rank 3 (Silver / Bronze) --}}
                <div class="glass-card p-4 md:p-5 rounded-2xl flex items-center gap-4 glow-hover transition-all duration-300 relative overflow-hidden group">
                    <div class="flex-shrink-0 w-10 text-center font-display-lg text-2xl text-outline font-bold">
                        3
                    </div>

                    <div class="relative w-14 h-14 flex-shrink-0">
                        <div class="w-full h-full rounded-full bg-surface-container-high border-2 border-white/20 flex items-center justify-center font-bold text-on-surface text-lg font-headline">
                            {{ strtoupper(substr($u->name, 0, 2)) }}
                        </div>
                    </div>

                    <div class="flex-grow min-w-0">
                        <a href="{{ route('users.show', $u->id) }}" class="font-headline-md text-base md:text-lg font-bold text-on-surface hover:text-secondary transition-colors block truncate">{{ $u->name }}</a>
                        <p class="font-body-md text-xs text-on-surface-variant truncate">{{ $u->headline ?? 'Active Contributor' }}</p>
                    </div>

                    <div class="text-right flex-shrink-0 font-mono-data">
                        <div class="text-secondary flex items-center justify-end gap-1 mb-0.5 text-sm md:text-base font-bold">
                            <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">schedule</span>
                            {{ number_format($hours, 1) }} hrs
                        </div>
                        <div class="font-label-caps text-[11px] {{ $archLevel['color'] }} flex items-center justify-end gap-1">
                            <span class="material-symbols-outlined text-[13px]">military_tech</span>
                            {{ $archLevel['label'] }}
                            @if($repScore)
                                &bull; {{ $repScore }} Rep
                            @endif
                        </div>
                    </div>
                </div>
            @else
                {{-- Rank 4+ (or all-architects view) --}}
                <div class="glass-card p-4 rounded-xl flex items-center gap-4 opacity-85 hover:opacity-100 transition-opacity">
                    <div class="flex-shrink-0 w-10 text-center font-display-lg text-lg text-on-surface-variant font-bold">{{ $rank }}</div>
                    <div class="w-11 h-11 rounded-full bg-surface-container border border-white/10 flex items-center justify-center font-bold text-sm text-on-surface flex-shrink-0">
                        {{ strtoupper(substr($u->name, 0, 2)) }}
                    </div>
                    <div class="flex-grow min-w-0">
                        <a href="{{ route('users.show', $u->id) }}" class="font-headline text-sm font-semibold text-on-surface hover:text-secondary transition-colors block truncate">{{ $u->name }}</a>
                        <p class="font-body-md text-xs text-on-surface-variant truncate">{{ $u->headline ?? 'Network Member' }}</p>
                    </div>
                    <div class="text-right font-mono-data flex-shrink-0">
                        <div class="text-xs text-secondary font-bold">{{ number_format($hours, 1) }} hrs</div>
                        <div class="font-label-caps text-[10px] {{ $archLevel['color'] }} mt-0.5">{{ $archLevel['label'] }}</div>
                    </div>
                </div>
            @endif
        @empty
            <x-empty-state
                title="Leaderboard Offline"
                description="No active contributors have completed temporal exchanges in the network yet."
                actionUrl="{{ route('services.index') }}"
                actionLabel="Explore Skills" />
        @endforelse
    </div>
</x-app-layout>
