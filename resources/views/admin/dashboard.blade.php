@section('title', 'Admin Dashboard')

<x-app-layout>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 font-label-caps text-[10px] text-emerald-400 mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                SYSTEM STATUS: ONLINE
            </div>
            <h1 class="font-headline-lg text-3xl md:text-4xl text-on-surface font-bold">Platform Control</h1>
            <p class="font-body-md text-xs text-on-surface-variant mt-1">Platform control, user management & maintenance</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <a href="{{ route('admin.users.index') }}" class="btn-stitch-secondary text-xs py-2.5 px-4">
                <span class="material-symbols-outlined text-[16px]">manage_accounts</span> Users
            </a>
            <a href="{{ route('admin.services.index') }}" class="btn-stitch-secondary text-xs py-2.5 px-4">
                <span class="material-symbols-outlined text-[16px]">hub</span> Services
            </a>
            <a href="{{ route('admin.maintenance') }}" class="btn-stitch-secondary text-xs py-2.5 px-4">
                <span class="material-symbols-outlined text-[16px]">build</span> Maintenance
            </a>
            <a href="{{ route('admin.categories.index') }}" class="btn-stitch-primary text-xs py-2.5 px-4">
                <span class="material-symbols-outlined text-[16px]">category</span> Categories
            </a>
        </div>
    </div>

    {{-- Key Stats Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
        <div class="glass-card p-5 rounded-xl glow-hover">
            <div class="flex items-center justify-between mb-3">
                <div class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-tertiary">
                    <span class="material-symbols-outlined text-[18px]">group</span>
                </div>
                <span class="font-mono-data text-[10px] text-tertiary font-semibold">USERS</span>
            </div>
            <span class="font-display-lg text-2xl font-bold text-on-surface">{{ $stats['total_users'] }}</span>
            <p class="font-label-caps text-[10px] text-on-surface-variant mt-0.5">Total registered</p>
        </div>

        <div class="glass-card p-5 rounded-xl glow-hover">
            <div class="flex items-center justify-between mb-3">
                <div class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined text-[18px]">mark_email_read</span>
                </div>
                <span class="font-mono-data text-[10px] text-secondary font-semibold">VERIFIED</span>
            </div>
            <span class="font-display-lg text-2xl font-bold text-on-surface">{{ $stats['verified_users'] }}</span>
            <p class="font-label-caps text-[10px] text-on-surface-variant mt-0.5">Email verified</p>
        </div>

        <div class="glass-card p-5 rounded-xl glow-hover {{ $stats['unverified_users'] > 0 ? 'border-yellow-500/30' : '' }}">
            <div class="flex items-center justify-between mb-3">
                <div class="w-8 h-8 rounded-lg {{ $stats['unverified_users'] > 0 ? 'bg-yellow-500/15' : 'bg-surface-container-high' }} flex items-center justify-center {{ $stats['unverified_users'] > 0 ? 'text-yellow-400' : 'text-on-surface-variant' }}">
                    <span class="material-symbols-outlined text-[18px]">mail</span>
                </div>
                <span class="font-mono-data text-[10px] {{ $stats['unverified_users'] > 0 ? 'text-yellow-400' : 'text-on-surface-variant' }} font-semibold">UNVERIFIED</span>
            </div>
            <span class="font-display-lg text-2xl font-bold text-on-surface">{{ $stats['unverified_users'] }}</span>
            <p class="font-label-caps text-[10px] text-on-surface-variant mt-0.5">Pending verification</p>
        </div>

        <div class="glass-card p-5 rounded-xl glow-hover">
            <div class="flex items-center justify-between mb-3">
                <div class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[18px]">hub</span>
                </div>
                <span class="font-mono-data text-[10px] text-primary font-semibold">SERVICES</span>
            </div>
            <span class="font-display-lg text-2xl font-bold text-on-surface">{{ $stats['total_services'] }}</span>
            <p class="font-label-caps text-[10px] text-on-surface-variant mt-0.5">{{ $stats['active_services'] }} active</p>
        </div>

        <div class="glass-card p-5 rounded-xl glow-hover">
            <div class="flex items-center justify-between mb-3">
                <div class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined text-[18px]">sync_alt</span>
                </div>
                <span class="font-mono-data text-[10px] text-secondary font-semibold">REQUESTS</span>
            </div>
            <span class="font-display-lg text-2xl font-bold text-on-surface">{{ $stats['total_requests'] }}</span>
            <p class="font-label-caps text-[10px] text-on-surface-variant mt-0.5">{{ $stats['completed_requests'] }} completed</p>
        </div>

        <div class="glass-card p-5 rounded-xl glow-hover {{ $stats['disputed_requests'] > 0 ? 'border-error/30 bg-error/5' : '' }}">
            <div class="flex items-center justify-between mb-3">
                <div class="w-8 h-8 rounded-lg {{ $stats['disputed_requests'] > 0 ? 'bg-error/15' : 'bg-surface-container-high' }} flex items-center justify-center {{ $stats['disputed_requests'] > 0 ? 'text-error' : 'text-on-surface-variant' }}">
                    <span class="material-symbols-outlined text-[18px]">warning</span>
                </div>
                <span class="font-mono-data text-[10px] {{ $stats['disputed_requests'] > 0 ? 'text-error' : 'text-on-surface-variant' }} font-semibold">DISPUTES</span>
            </div>
            <span class="font-display-lg text-2xl font-bold {{ $stats['disputed_requests'] > 0 ? 'text-error' : 'text-on-surface' }}">{{ $stats['disputed_requests'] }}</span>
            <p class="font-label-caps text-[10px] text-on-surface-variant mt-0.5">Active disputes</p>
        </div>

        <div class="glass-card p-5 rounded-xl glow-hover">
            <div class="flex items-center justify-between mb-3">
                <div class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-tertiary">
                    <span class="material-symbols-outlined text-[18px]">lightbulb</span>
                </div>
                <span class="font-mono-data text-[10px] text-tertiary font-semibold">IDEAS</span>
            </div>
            <span class="font-display-lg text-2xl font-bold text-on-surface">{{ $stats['total_ideas'] }}</span>
            <p class="font-label-caps text-[10px] text-on-surface-variant mt-0.5">Total initiatives</p>
        </div>

        <div class="glass-card p-5 rounded-xl glow-hover">
            <div class="flex items-center justify-between mb-3">
                <div class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                </div>
                <span class="font-mono-data text-[10px] text-primary font-semibold">TRANSACTIONS</span>
            </div>
            <span class="font-display-lg text-2xl font-bold text-on-surface">{{ $stats['total_transactions'] }}</span>
            <p class="font-label-caps text-[10px] text-on-surface-variant mt-0.5">Ledger entries</p>
        </div>
    </div>

    {{-- Main Grid: Recent Users + Audit Log --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">

        {{-- Recent Users --}}
        <div class="glass-card p-6 rounded-2xl lg:col-span-7">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Recent Registrations</h3>
                <a href="{{ route('admin.users.index') }}" class="font-label-caps text-xs text-secondary hover:underline">View All ?</a>
            </div>
            <div class="space-y-3">
                @forelse($recentUsers as $u)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-surface-container-high/30 hover:bg-surface-container-high/50 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <x-avatar :user="$u" size="sm" />
                            <div class="min-w-0">
                                <p class="font-body-md text-sm font-semibold text-on-surface truncate">{{ $u->name }}</p>
                                <p class="font-mono-data text-[11px] text-on-surface-variant truncate">{{ $u->email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if($u->role === 'admin')
                                <span class="px-2 py-0.5 rounded-full bg-tertiary/15 text-tertiary text-[10px] font-mono-data font-bold">ADMIN</span>
                            @endif
                            @if(!$u->hasVerifiedEmail())
                                <span class="px-2 py-0.5 rounded-full bg-yellow-500/15 text-yellow-400 text-[10px] font-mono-data font-bold">UNVERIFIED</span>
                            @elseif($u->isLegacyUser() && !$u->email_verified_at)
                                <span class="px-2 py-0.5 rounded-full bg-secondary/15 text-secondary text-[10px] font-mono-data font-bold">LEGACY</span>
                            @endif
                            <a href="{{ route('admin.users.show', $u) }}" class="text-secondary hover:text-secondary/70 p-1">
                                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="font-mono-data text-xs text-on-surface-variant text-center py-4">No users yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Recent Audit Logs --}}
        <div class="glass-card p-6 rounded-2xl lg:col-span-5">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Audit Log</h3>
                <span class="px-2.5 py-0.5 rounded-full bg-secondary/10 text-secondary text-[10px] font-mono-data font-bold border border-secondary/20">
                    {{ $recentAuditLogs->count() }} Recent
                </span>
            </div>
            <div class="space-y-2 overflow-y-auto max-h-80">
                @forelse($recentAuditLogs as $log)
                    <div class="p-3 rounded-xl bg-surface-container-high/30 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-mono-data text-[10px] font-bold
                                @if(str_contains($log->action, 'deleted')) text-error
                                @elseif(str_contains($log->action, 'suspended')) text-yellow-400
                                @else text-secondary
                                @endif">
                                {{ strtoupper(str_replace('.', ' ', $log->action)) }}
                            </span>
                            <span class="font-mono-data text-[10px] text-on-surface-variant">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="font-body-md text-xs text-on-surface">{{ $log->target_label }}</p>
                        <p class="font-mono-data text-[10px] text-on-surface-variant">by {{ $log->admin_name }}</p>
                    </div>
                @empty
                    <div class="py-8 text-center text-on-surface-variant">
                        <span class="material-symbols-outlined text-[28px] opacity-40 mb-2 block">verified_user</span>
                        <p class="font-mono-data text-xs">No admin actions recorded yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Global Skill Liquidity --}}
    <div class="glass-card p-6 md:p-8 rounded-2xl mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Global Skill Liquidity</h3>
                <p class="font-body-md text-xs text-on-surface-variant mt-0.5">Distribution across primary sector domains</p>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="font-label-caps text-xs text-secondary hover:underline">
                Taxonomy Settings &rarr;
            </a>
        </div>

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
                <p class="font-mono-data text-xs text-on-surface-variant">No active categories found.</p>
            @endforelse
        </div>
    </div>

    {{-- Disputed Requests --}}
    @if($disputedRequests->isNotEmpty())
    <div class="glass-card p-6 rounded-2xl border-error/20 bg-error/5">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-headline-md text-lg font-bold text-error">? Moderation Queue</h3>
            <span class="px-2.5 py-0.5 rounded-full bg-error/15 text-error text-xs font-mono-data font-bold border border-error/30">
                {{ $disputedRequests->count() }} Pending
            </span>
        </div>
        <div class="space-y-3">
            @foreach($disputedRequests as $dispute)
                <div class="p-3.5 rounded-xl bg-surface-container-high/40 border border-error/20 space-y-2">
                    <div class="flex items-center justify-between text-[11px] font-mono-data">
                        <span class="text-error font-bold">DISPUTE #SR-{{ $dispute->id }}</span>
                        <span class="text-on-surface-variant">{{ $dispute->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="font-body-md text-xs text-on-surface font-semibold">{{ $dispute->service?->title ?? $dispute->title }}</p>
                    <p class="font-mono-data text-[11px] text-on-surface-variant">
                        Requester: {{ $dispute->requester?->name }} &bull; Provider: {{ $dispute->provider?->name }}
                    </p>
                    <div class="flex gap-2 pt-2 border-t border-white/5">
                        <form method="POST" action="{{ route('service-requests.resolve-dispute', $dispute->id) }}" class="w-1/2">
                            @csrf
                            <input type="hidden" name="resolution" value="completed">
                            <button type="submit" class="btn-stitch-primary text-[10px] py-1.5 w-full justify-center">Release to Provider</button>
                        </form>
                        <form method="POST" action="{{ route('service-requests.resolve-dispute', $dispute->id) }}" class="w-1/2">
                            @csrf
                            <input type="hidden" name="resolution" value="cancelled">
                            <button type="submit" class="btn-stitch-danger text-[10px] py-1.5 w-full justify-center">Refund Requester</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</x-app-layout>