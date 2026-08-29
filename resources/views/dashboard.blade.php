@section('title', 'Dashboard')

<x-app-layout>
    {{-- Hero Section: Balance & Reputation --}}
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-gutter md:gap-8 mb-8">
        {{-- Balance Card --}}
        <div class="glass-card p-6 md:p-8 lg:col-span-8 flex flex-col justify-center relative overflow-hidden group glow-hover rounded-2xl">
            {{-- Decorative Ambient Glow --}}
            <div class="absolute -right-20 -top-20 w-72 h-72 bg-secondary/15 rounded-full blur-3xl group-hover:bg-secondary/25 transition-all duration-700 pointer-events-none"></div>

            <span class="font-label-caps text-xs text-on-surface-variant mb-3">Time Credit Balance</span>

            <div class="flex items-baseline gap-3 mb-3">
                <span class="font-display-lg text-4xl sm:text-5xl md:text-6xl text-secondary drop-shadow-[0_0_16px_rgba(93,230,255,0.45)]">
                    {{ number_format(Auth::user()->time_balance, 2) }}
                </span>
                <span class="font-body-lg text-lg text-on-surface-variant font-mono">TC</span>
            </div>

            <div class="flex items-center gap-2 font-mono-data text-xs md:text-sm text-tertiary mb-8">
                <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">trending_up</span>
                <span>Active Vault Node &bull; 1.0 hr = 1.0 TC standard</span>
            </div>

            {{-- Quick Actions --}}
            <div class="flex flex-wrap gap-3 relative z-10">
                <a href="{{ route('services.index') }}" class="btn-stitch-primary text-xs py-3 px-6 shadow-[0_0_16px_rgba(93,230,255,0.3)]">
                    <span class="material-symbols-outlined text-[16px]">sync_alt</span>
                    Transfer / Book Time
                </a>
                <a href="{{ route('services.create') }}" class="btn-stitch-secondary text-xs py-3 px-6">
                    <span class="material-symbols-outlined text-[16px]">add_circle</span>
                    Offer a Skill
                </a>
                <a href="{{ route('ideas.create') }}" class="btn-stitch-ghost text-xs py-3 px-4">
                    <span class="material-symbols-outlined text-[16px]">lightbulb</span>
                    Post Initiative
                </a>
            </div>
        </div>

        {{-- Reputation / Badges Card --}}
        <div class="glass-card p-6 md:p-8 lg:col-span-4 flex flex-col items-center justify-center text-center rounded-2xl relative overflow-hidden group glow-hover">
            @php
                $avgRating = (float)Auth::user()->reviewsReceived()->avg('rating');
                $repScore = $avgRating > 0 ? round($avgRating * 20, 1) : null;
                $completedCount = Auth::user()->providedServiceRequests()->where('status', 'completed')->count();
                $archLevel = match(true) {
                    $completedCount >= 20 => ['label' => 'Architect Level 5', 'pct' => 96],
                    $completedCount >= 10 => ['label' => 'Architect Level 4', 'pct' => 80],
                    $completedCount >= 5  => ['label' => 'Architect Level 3', 'pct' => 65],
                    $completedCount >= 2  => ['label' => 'Architect Level 2', 'pct' => 45],
                    default               => ['label' => 'Architect Level 1', 'pct' => 20],
                };
                $offset = round(276 * (1 - $archLevel['pct'] / 100));
            @endphp
            <div class="relative w-28 h-28 mb-4">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="44" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="5"></circle>
                    <circle cx="50" cy="50" r="44" fill="none" stroke="#f9bd22" stroke-width="5"
                            stroke-dasharray="276" stroke-dashoffset="{{ $offset }}"
                            stroke-linecap="round"
                            class="drop-shadow-[0_0_10px_rgba(249,189,34,0.6)]"></circle>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[36px] text-tertiary drop-shadow-[0_0_12px_rgba(249,189,34,0.6)]" style="font-variation-settings: 'FILL' 1;">military_tech</span>
                </div>
            </div>
            <h3 class="font-headline-md text-lg font-bold text-primary mb-1">{{ $archLevel['label'] }}</h3>
            <p class="font-body-md text-xs text-on-surface-variant">{{ $completedCount }} completed exchange{{ $completedCount !== 1 ? 's' : '' }}</p>
            @if($repScore)
                <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-tertiary/10 border border-tertiary/30 font-mono-data text-[11px] text-tertiary">
                    <span class="material-symbols-outlined text-[14px]">star</span>
                    {{ $repScore }} Rep Score
                </div>
            @else
                <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 font-mono-data text-[11px] text-on-surface-variant">
                    Complete exchanges to earn Rep
                </div>
            @endif
        </div>
    </section>

    {{-- Bento Grid: Contribution Matrix & Active Engagements --}}
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-gutter md:gap-8 mb-8">
        {{-- Contribution Matrix Chart Card --}}
        <div class="glass-card p-6 md:p-8 lg:col-span-7 flex flex-col justify-between rounded-2xl relative overflow-hidden h-84">
            @php
                // Real weekly transaction hours grouped by day (DB-agnostic)
                $weekStart = now()->startOfWeek();
                $weeklyTxRaw = Auth::user()->incomingTransactions()
                    ->where('created_at', '>=', $weekStart)
                    ->where('type', \App\Models\Transaction::TYPE_SERVICE_EXCHANGE)
                    ->get(['created_at', 'amount']);
                $days = ['MON','TUE','WED','THU','FRI','SAT','SUN'];
                // dayOfWeek: 0=Sunday,1=Monday,...,6=Saturday → map to Mon-Sun index
                $dayTotals = collect(range(0, 6))->map(function($i) use ($weeklyTxRaw, $weekStart) {
                    $date = $weekStart->copy()->addDays($i)->toDateString();
                    return $weeklyTxRaw->filter(fn($tx) => $tx->created_at->toDateString() === $date)
                        ->sum('amount');
                });
                $maxDay = max((float)$dayTotals->max(), 1);
                $weeklyTotal = $dayTotals->sum();
                $todayIdx = (now()->dayOfWeek + 6) % 7; // Mon=0
            @endphp
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-label-caps text-xs text-on-surface-variant">Contribution Matrix</h3>
                    <p class="font-body-md text-xs text-on-surface-variant/70 mt-0.5">Weekly temporal exchange volume</p>
                </div>
                <span class="font-mono-data text-xs text-secondary font-semibold">+{{ number_format($weeklyTotal, 1) }} hrs this week</span>
            </div>

            {{-- Bars Visualization --}}
            <div class="flex-1 w-full flex items-end gap-3 px-2 pb-2">
                @foreach($dayTotals as $i => $hrs)
                    @php
                        $pct = max(3, round(($hrs / $maxDay) * 100));
                        $isToday = ($i === $todayIdx);
                    @endphp
                    <div class="w-full {{ $isToday ? 'bg-secondary shadow-[0_0_16px_rgba(93,230,255,0.4)]' : ($hrs > 0 ? 'bg-secondary/40' : 'bg-surface-container') }} rounded-t-md relative group transition-all duration-300"
                         style="height: {{ $pct }}%"
                         title="{{ $days[$i] }}: {{ number_format($hrs, 1) }} hrs">
                    </div>
                @endforeach
            </div>

            <div class="flex justify-between font-mono-data text-[10px] text-on-surface-variant/60 pt-3 border-t border-white/5">
                @foreach($days as $d)<span>{{ $d }}</span>@endforeach
            </div>
        </div>

        {{-- Active Engagements / Service Requests --}}
        <div class="glass-card p-6 md:p-8 lg:col-span-5 flex flex-col justify-between rounded-2xl h-84">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-label-caps text-xs text-on-surface-variant">Active Engagements</h3>
                <a href="{{ route('service-requests.index') }}" class="text-secondary hover:text-white transition-colors" title="View all requests">
                    <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                </a>
            </div>

            @php
                $activeRequests = Auth::user()->requestedServiceRequests()->with(['service', 'provider'])->latest()->take(3)->get();
                $receivedRequests = Auth::user()->providedServiceRequests()->with(['service', 'requester'])->latest()->take(2)->get();
                $allEngagements = $activeRequests->concat($receivedRequests)->take(3);
            @endphp

            <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                @forelse($allEngagements as $req)
                    <a href="{{ route('service-requests.index') }}"
                       class="flex items-start gap-3.5 p-3 rounded-xl bg-surface-container-high/40 hover:bg-surface-container-high border border-white/5 hover:border-secondary/30 transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-full bg-surface-container-lowest border border-white/10 flex items-center justify-center text-secondary group-hover:border-secondary/40 flex-shrink-0">
                            <span class="material-symbols-outlined text-[18px]">sync_alt</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-headline text-xs font-semibold text-primary group-hover:text-secondary transition-colors truncate">
                                {{ $req->service->title ?? 'Temporal Exchange' }}
                            </h4>
                            <p class="font-mono-data text-[11px] text-on-surface-variant/70 truncate mt-0.5">
                                Status: <span class="text-secondary">{{ ucfirst(str_replace('_', ' ', $req->status)) }}</span>
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="font-mono-data text-xs text-secondary font-bold">{{ number_format($req->estimated_hours ?? $req->total_credits ?? 0, 1) }} TC</span>
                        </div>
                    </a>
                @empty
                    <div class="py-12 text-center text-on-surface-variant flex flex-col items-center justify-center">
                        <span class="material-symbols-outlined text-[28px] opacity-40 mb-1.5">hourglass_empty</span>
                        <p class="font-mono-data text-xs">No active service engagements.</p>
                    </div>
                @endforelse
            </div>

            <a href="{{ route('services.index') }}" class="btn-stitch-ghost text-xs py-2 w-full mt-2 justify-center border border-white/5">
                <span class="material-symbols-outlined text-[16px]">search</span> Explore More Opportunities
            </a>
        </div>
    </section>

    {{-- Featured Services & IdeaVault Initiatives Grid --}}
    <section class="mt-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="font-headline-lg text-xl md:text-2xl text-on-surface font-bold">Recommended Skills & Initiatives</h2>
                <p class="font-body-md text-xs text-on-surface-variant mt-0.5">Community architects offering high-impact capabilities</p>
            </div>
            <a href="{{ route('services.index') }}" class="btn-stitch-ghost text-xs">
                Browse All <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>

        @php
            $featuredServices = \App\Models\Service::with(['user', 'category'])->latest()->take(3)->get();
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse($featuredServices as $service)
                <x-service-card :service="$service" />
            @empty
                <div class="col-span-3">
                    <x-empty-state
                        title="No Public Skills Listed Yet"
                        description="Be the first architect to list a skill offering in the temporal exchange."
                        actionUrl="{{ route('services.create') }}"
                        actionLabel="Offer a Skill" />
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
