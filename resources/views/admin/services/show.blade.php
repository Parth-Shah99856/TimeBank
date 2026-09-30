@section('title', 'Admin — Service: ' . $service->title)

<x-app-layout>
    <div class="max-w-3xl mx-auto">
        <a href="{{ route('admin.services.index') }}" class="inline-flex items-center gap-1 font-label-caps text-xs text-on-surface-variant hover:text-secondary mb-4">
            <span class="material-symbols-outlined text-[14px]">arrow_back</span> All Services
        </a>

        <div class="glass-card p-6 md:p-8 rounded-2xl mb-6">
            <div class="flex items-start justify-between gap-4 mb-6">
                <div class="flex-1 min-w-0">
                    <h1 class="font-headline-lg text-2xl font-bold text-on-surface">{{ $service->title }}</h1>
                    <p class="font-mono-data text-xs text-on-surface-variant mt-1">{{ $service->category?->name }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if($service->is_active)
                        <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-mono-data border border-emerald-500/20">Active</span>
                    @else
                        <span class="px-2.5 py-1 rounded-full bg-surface-container-high text-on-surface-variant text-[10px] font-mono-data">Inactive</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
                <div class="text-center p-3 rounded-xl bg-surface-container-high/30">
                    <p class="font-display-lg text-lg font-bold text-secondary">{{ number_format($service->hourly_rate, 2) }} TC</p>
                    <p class="font-label-caps text-[10px] text-on-surface-variant">Hourly Rate</p>
                </div>
                <div class="text-center p-3 rounded-xl bg-surface-container-high/30">
                    <p class="font-display-lg text-lg font-bold text-on-surface">{{ $service->serviceRequests->count() }}</p>
                    <p class="font-label-caps text-[10px] text-on-surface-variant">Total Requests</p>
                </div>
                <div class="text-center p-3 rounded-xl bg-surface-container-high/30">
                    <p class="font-display-lg text-lg font-bold text-on-surface">{{ $service->created_at->format('M Y') }}</p>
                    <p class="font-label-caps text-[10px] text-on-surface-variant">Listed</p>
                </div>
            </div>

            @if($service->description)
                <div class="mb-6">
                    <h3 class="font-headline-md text-sm font-bold text-on-surface mb-2">Description</h3>
                    <p class="font-body-md text-sm text-on-surface-variant leading-relaxed">{{ $service->description }}</p>
                </div>
            @endif

            <div class="pt-5 border-t border-white/10">
                <h3 class="font-headline-md text-sm font-bold text-on-surface mb-3">Owner</h3>
                @if($service->user)
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$service->user" size="sm" />
                        <div>
                            <a href="{{ route('admin.users.show', $service->user) }}" class="font-body-md text-sm font-semibold text-secondary hover:underline">{{ $service->user->name }}</a>
                            <p class="font-mono-data text-xs text-on-surface-variant">{{ $service->user->email }}</p>
                        </div>
                    </div>
                @else
                    <p class="font-mono-data text-xs text-on-surface-variant italic">Owner account deleted</p>
                @endif
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('admin.services.index') }}" class="btn-stitch-secondary text-sm">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Back
            </a>
            <a href="{{ route('admin.services.delete', $service) }}" class="btn-stitch-danger text-sm">
                <span class="material-symbols-outlined text-[16px]">delete_forever</span> Delete Service
            </a>
        </div>
    </div>
</x-app-layout>