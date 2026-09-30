@section('title', 'Admin — User: ' . $user->name)

<x-app-layout>
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 font-label-caps text-xs text-on-surface-variant hover:text-secondary mb-2">
            <span class="material-symbols-outlined text-[14px]">arrow_back</span> All Users
        </a>
        <h1 class="font-headline-lg text-2xl md:text-3xl text-on-surface font-bold">{{ $user->name }}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- User Profile Card --}}
        <div class="lg:col-span-1 space-y-5">
            <div class="glass-card p-6 rounded-2xl">
                <div class="flex flex-col items-center text-center mb-5">
                    <x-avatar :user="$user" size="lg" />
                    <h2 class="font-headline-md text-xl font-bold text-on-surface mt-3">{{ $user->name }}</h2>
                    <p class="font-mono-data text-xs text-on-surface-variant mt-1">{{ $user->email }}</p>
                    @if($user->headline)
                        <p class="font-body-md text-sm text-secondary mt-2">{{ $user->headline }}</p>
                    @endif
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between py-2 border-b border-white/5">
                        <span class="font-label-caps text-[10px] text-on-surface-variant">ROLE</span>
                        @if($user->role === 'admin')
                            <span class="px-2 py-0.5 rounded-full bg-tertiary/15 text-tertiary text-[10px] font-mono-data font-bold border border-tertiary/20">ADMIN</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant text-[10px] font-mono-data">USER</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-white/5">
                        <span class="font-label-caps text-[10px] text-on-surface-variant">EMAIL STATUS</span>
                        @if($user->email_verified_at)
                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-mono-data border border-emerald-500/20">Verified</span>
                        @elseif($user->isDemoAccount())
                            <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary text-[10px] font-mono-data border border-primary/20">Demo Exempt</span>
                        @elseif($user->isLegacyUser())
                            <span class="px-2 py-0.5 rounded-full bg-secondary/10 text-secondary text-[10px] font-mono-data border border-secondary/20">Legacy Verified</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full bg-yellow-500/10 text-yellow-400 text-[10px] font-mono-data border border-yellow-500/20">Unverified</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-white/5">
                        <span class="font-label-caps text-[10px] text-on-surface-variant">TIME BALANCE</span>
                        <span class="font-mono-data text-sm text-secondary font-bold">{{ number_format($user->time_balance, 2) }} TC</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-white/5">
                        <span class="font-label-caps text-[10px] text-on-surface-variant">JOINED</span>
                        <span class="font-mono-data text-xs text-on-surface-variant">{{ $user->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="font-label-caps text-[10px] text-on-surface-variant">USER ID</span>
                        <span class="font-mono-data text-xs text-on-surface-variant">#{{ $user->id }}</span>
                    </div>
                </div>

                @if($user->id !== auth()->id())
                    <div class="mt-5 pt-5 border-t border-white/10">
                        <a href="{{ route('admin.users.delete', $user) }}"
                           class="w-full btn-stitch-danger text-sm justify-center">
                            <span class="material-symbols-outlined text-[16px]">delete_forever</span>
                            Delete User
                        </a>
                    </div>
                @else
                    <div class="mt-5 pt-5 border-t border-white/10 text-center">
                        <p class="font-mono-data text-xs text-on-surface-variant">You cannot delete your own account from here.</p>
                    </div>
                @endif
            </div>

            @if($user->bio)
            <div class="glass-card p-5 rounded-xl">
                <h3 class="font-headline-md text-sm font-bold text-on-surface mb-2">Bio</h3>
                <p class="font-body-md text-xs text-on-surface-variant leading-relaxed">{{ $user->bio }}</p>
            </div>
            @endif
        </div>

        {{-- Activity --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Services --}}
            <div class="glass-card p-5 rounded-xl">
                <h3 class="font-headline-md text-base font-bold text-on-surface mb-4">
                    Offered Services <span class="text-on-surface-variant font-mono-data text-sm">({{ $stats['services']->count() }})</span>
                </h3>
                @forelse($stats['services'] as $service)
                    <div class="flex items-center justify-between py-2.5 border-b border-white/5 last:border-0">
                        <div class="min-w-0">
                            <p class="font-body-md text-sm font-semibold text-on-surface truncate">{{ $service->title }}</p>
                            <p class="font-mono-data text-[11px] text-on-surface-variant">{{ $service->category?->name }} &bull; {{ number_format($service->hourly_rate, 2) }} TC/hr</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ml-3">
                            @if($service->is_active)
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-mono-data">Active</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant text-[10px] font-mono-data">Inactive</span>
                            @endif
                            <a href="{{ route('admin.services.delete', $service) }}"
                               class="p-1 text-on-surface-variant hover:text-error hover:bg-error/10 rounded transition-colors"
                               title="Delete service">
                                <span class="material-symbols-outlined text-[14px]">delete</span>
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="font-mono-data text-xs text-on-surface-variant py-2">No services offered.</p>
                @endforelse
            </div>

            {{-- Ideas --}}
            <div class="glass-card p-5 rounded-xl">
                <h3 class="font-headline-md text-base font-bold text-on-surface mb-4">
                    Ideas <span class="text-on-surface-variant font-mono-data text-sm">({{ $stats['ideas']->count() }})</span>
                </h3>
                @forelse($stats['ideas'] as $idea)
                    <div class="py-2.5 border-b border-white/5 last:border-0">
                        <p class="font-body-md text-sm font-semibold text-on-surface">{{ $idea->title }}</p>
                        <p class="font-mono-data text-[11px] text-on-surface-variant">{{ $idea->category?->name }} &bull; {{ ucfirst($idea->status) }}</p>
                    </div>
                @empty
                    <p class="font-mono-data text-xs text-on-surface-variant py-2">No ideas posted.</p>
                @endforelse
            </div>

            {{-- Recent Requests --}}
            <div class="glass-card p-5 rounded-xl">
                <h3 class="font-headline-md text-base font-bold text-on-surface mb-4">Recent Service Requests</h3>
                @forelse($stats['requests'] as $req)
                    <div class="py-2.5 border-b border-white/5 last:border-0">
                        <div class="flex items-center justify-between">
                            <p class="font-body-md text-sm font-semibold text-on-surface truncate">{{ $req->title }}</p>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono-data shrink-0 ml-2
                                @if($req->status === 'completed') bg-emerald-500/10 text-emerald-400
                                @elseif($req->status === 'disputed') bg-error/10 text-error
                                @elseif($req->status === 'in_progress') bg-secondary/10 text-secondary
                                @else bg-surface-container-high text-on-surface-variant
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                            </span>
                        </div>
                        <p class="font-mono-data text-[11px] text-on-surface-variant mt-0.5">
                            @if($req->requester_id === $user->id)
                                As requester &bull; Provider: {{ $req->provider?->name ?? 'N/A' }}
                            @else
                                As provider &bull; Requester: {{ $req->requester?->name ?? 'N/A' }}
                            @endif
                        </p>
                    </div>
                @empty
                    <p class="font-mono-data text-xs text-on-surface-variant py-2">No service requests found.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>