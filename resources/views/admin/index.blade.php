@section('title', 'Admin Platform Control')

<x-app-layout>
    @php
        $totalUsers = \App\Models\User::count();
        $totalIdeas = \App\Models\Idea::count();
        $totalServices = \App\Models\Service::count();
        $totalHours = (float)(\App\Models\Transaction::where('type', \App\Models\Transaction::TYPE_SERVICE_EXCHANGE)->sum('amount') + \App\Models\User::sum('time_balance'));
        $disputedRequests = \App\Models\ServiceRequest::with(['service', 'requester', 'provider'])->where('status', 'disputed')->latest()->get();
        $pendingDisputesCount = $disputedRequests->count();
    @endphp

    {{-- Top Status & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 font-label-caps text-[10px] text-emerald-400 mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                SYSTEM STATUS: ONLINE
            </div>
            <h1 class="font-headline-lg text-3xl md:text-4xl text-on-surface font-bold">Platform Control</h1>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.categories.index') }}" class="btn-stitch-secondary text-xs py-2.5 px-4">
                <span class="material-symbols-outlined text-[16px]">category</span> Categories
            </a>
            <button onclick="window.print()" class="btn-stitch-primary text-xs py-2.5 px-4 shadow-[0_0_12px_rgba(93,230,255,0.25)]">
                <span class="material-symbols-outlined text-[16px]">download</span> Export Report
            </button>
        </div>
    </div>

    {{-- System Metric Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Platform Hours --}}
        <div class="glass-card p-6 rounded-xl flex flex-col justify-between glow-hover relative overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <div class="w-9 h-9 rounded-lg bg-surface-container-high flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined text-[20px]">schedule</span>
                </div>
                <span class="font-mono-data text-xs text-secondary font-semibold">Active Ledger</span>
            </div>
            <div>
                <span class="font-label-caps text-[10px] text-on-surface-variant block mb-1">Total Platform Hours</span>
                <span class="font-display-lg text-2xl md:text-3xl font-bold text-on-surface">{{ number_format($totalHours, 1) }} TC</span>
            </div>
        </div>

        {{-- Active Users --}}
        <div class="glass-card p-6 rounded-xl flex flex-col justify-between glow-hover relative overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <div class="w-9 h-9 rounded-lg bg-surface-container-high flex items-center justify-center text-tertiary">
                    <span class="material-symbols-outlined text-[20px]">group</span>
                </div>
                <span class="font-mono-data text-xs text-tertiary font-semibold">Architect Nodes</span>
            </div>
            <div>
                <span class="font-label-caps text-[10px] text-on-surface-variant block mb-1">Registered Users</span>
                <span class="font-display-lg text-2xl md:text-3xl font-bold text-on-surface">{{ $totalUsers }}</span>
            </div>
        </div>

        {{-- Open Projects / Initiatives --}}
        <div class="glass-card p-6 rounded-xl flex flex-col justify-between glow-hover relative overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <div class="w-9 h-9 rounded-lg bg-surface-container-high flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[20px]">hub</span>
                </div>
                <span class="font-mono-data text-xs text-secondary font-semibold">{{ $totalServices }} Services</span>
            </div>
            <div>
                <span class="font-label-caps text-[10px] text-on-surface-variant block mb-1">Open Initiatives</span>
                <span class="font-display-lg text-2xl md:text-3xl font-bold text-on-surface">{{ $totalIdeas }}</span>
            </div>
        </div>

        {{-- System Alerts / Disputes --}}
        <div class="glass-card p-6 rounded-xl flex flex-col justify-between border-error/30 glow-hover relative overflow-hidden bg-error-container/10">
            <div class="flex items-center justify-between mb-4">
                <div class="w-9 h-9 rounded-lg bg-error/15 flex items-center justify-center text-error">
                    <span class="material-symbols-outlined text-[20px]">warning</span>
                </div>
                <span class="font-mono-data text-xs text-error font-semibold">{{ $pendingDisputesCount > 0 ? 'Action Needed' : 'Nominal' }}</span>
            </div>
            <div>
                <span class="font-label-caps text-[10px] text-error block mb-1">Active Disputes</span>
                <span class="font-display-lg text-2xl md:text-3xl font-bold text-error">{{ $pendingDisputesCount }}</span>
            </div>
        </div>
    </div>

    {{-- Grid: Global Skill Liquidity & Moderation Queue --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {{-- Global Skill Liquidity Chart --}}
        <div class="glass-card p-6 md:p-8 rounded-2xl lg:col-span-7 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-headline-md text-lg font-bold text-on-surface">Global Skill Liquidity</h3>
                    <p class="font-body-md text-xs text-on-surface-variant mt-0.5">Distribution across primary sector domains</p>
                </div>
                <a href="{{ route('admin.categories.index') }}" class="font-label-caps text-xs text-secondary hover:underline">
                    Taxonomy Settings &rarr;
                </a>
            </div>

            @php
                $categoryStats = \App\Models\Category::withCount('services')->where('is_active', true)->take(5)->get();
            @endphp

            {{-- Dynamic Sector Bars --}}
            <div class="space-y-4">
                @forelse($categoryStats as $c)
                    @php $pct = min(100, max(15, $c->services_count * 20)); @endphp
                    <div>
                        <div class="flex items-center justify-between font-mono-data text-xs mb-1.5">
                            <span class="text-on-surface font-medium">{{ $c->name }}</span>
                            <span class="text-secondary font-bold">{{ $c->services_count }} skills</span>
                        </div>
                        <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
                            <div class="h-full bg-secondary rounded-full shadow-[0_0_8px_rgba(93,230,255,0.5)]" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="font-mono-data text-xs text-on-surface-variant py-4 text-center">No categories configured yet.</p>
                @endforelse
            </div>

            <div class="pt-4 mt-6 border-t border-white/5 font-mono-data text-[11px] text-on-surface-variant/70 text-center">
                Temporal Liquidity Balanced &bull; Multi-Node Consensus
            </div>
        </div>

        {{-- Moderation Queue (Disputed Requests Resolution) --}}
        <div class="glass-card p-6 md:p-8 rounded-2xl lg:col-span-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Moderation Queue</h3>
                <span class="px-2.5 py-0.5 rounded-full {{ $pendingDisputesCount > 0 ? 'bg-error/15 text-error border border-error/30' : 'bg-emerald-500/10 text-emerald-400' }} font-mono-data text-xs font-bold">
                    {{ $pendingDisputesCount }} Pending
                </span>
            </div>

            <div class="space-y-3 overflow-y-auto max-h-80 pr-1">
                @forelse($disputedRequests as $dispute)
                    <div class="p-3.5 rounded-xl bg-surface-container-high/40 border border-error/20 space-y-2">
                        <div class="flex items-center justify-between text-[11px] font-mono-data">
                            <span class="text-error font-bold">DISPUTE #SR-{{ $dispute->id }}</span>
                            <span class="text-on-surface-variant">{{ $dispute->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="font-body-md text-xs text-on-surface font-semibold">{{ $dispute->service->title ?? $dispute->title }}</p>
                        <p class="font-mono-data text-[11px] text-on-surface-variant">
                            Requester: {{ $dispute->requester->name }} &bull; Provider: {{ $dispute->provider->name }}
                        </p>
                        <p class="font-mono-data text-xs text-secondary font-bold">{{ number_format($dispute->total_credits ?? $dispute->estimated_hours, 1) }} TC</p>

                        <div class="flex gap-2 pt-2 border-t border-white/5">
                            {{-- Complete exchange --}}
                            <form method="POST" action="{{ route('service-requests.resolve-dispute', $dispute->id) }}" class="w-1/2">
                                @csrf
                                <input type="hidden" name="resolution" value="completed">
                                <button type="submit" class="btn-stitch-primary text-[10px] py-1.5 w-full justify-center">Release to Provider</button>
                            </form>
                            {{-- Cancel exchange --}}
                            <form method="POST" action="{{ route('service-requests.resolve-dispute', $dispute->id) }}" class="w-1/2">
                                @csrf
                                <input type="hidden" name="resolution" value="cancelled">
                                <button type="submit" class="btn-stitch-danger text-[10px] py-1.5 w-full justify-center">Refund Requester</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-on-surface-variant flex flex-col items-center justify-center">
                        <span class="material-symbols-outlined text-[32px] text-emerald-400 opacity-60 mb-2">verified_user</span>
                        <p class="font-headline text-sm font-semibold text-on-surface">Queue Clear</p>
                        <p class="font-mono-data text-xs text-on-surface-variant/70 mt-1">No flagged exchanges or active disputes.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
