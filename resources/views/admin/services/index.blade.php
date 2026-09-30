@section('title', 'Admin — Services')

<x-app-layout>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-1 font-label-caps text-xs text-on-surface-variant hover:text-secondary mb-2">
                <span class="material-symbols-outlined text-[14px]">arrow_back</span> Admin Dashboard
            </a>
            <h1 class="font-headline-lg text-2xl md:text-3xl text-on-surface font-bold">Service Management</h1>
        </div>
    </div>

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('admin.services.index') }}" class="glass-card p-4 rounded-xl mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-on-surface-variant/60">
                <span class="material-symbols-outlined text-[16px]">search</span>
            </div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title, description or owner…"
                   class="stitch-input pl-9 text-sm w-full">
        </div>
        <select name="category" class="stitch-input text-sm w-full sm:w-48">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <select name="status" class="stitch-input text-sm w-full sm:w-36">
            <option value="">All Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="submit" class="btn-stitch-primary text-sm py-2.5 px-5">Filter</button>
        @if(request('search') || request('category') || request('status'))
            <a href="{{ route('admin.services.index') }}" class="btn-stitch-ghost text-sm py-2.5 px-4">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="glass-card rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/10 text-left">
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold">Service</th>
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold hidden sm:table-cell">Owner</th>
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold hidden md:table-cell">Category</th>
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold hidden lg:table-cell">Rate</th>
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold">Status</th>
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold hidden lg:table-cell">Created</th>
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($services as $service)
                        <tr class="hover:bg-white/3 transition-colors">
                            <td class="px-4 py-3">
                                <p class="font-body-md text-sm font-semibold text-on-surface">{{ $service->title }}</p>
                                <p class="font-mono-data text-[10px] text-on-surface-variant mt-0.5 sm:hidden">{{ $service->user?->name }}</p>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                @if($service->user)
                                    <a href="{{ route('admin.users.show', $service->user) }}" class="font-body-md text-sm text-secondary hover:underline">{{ $service->user->name }}</a>
                                @else
                                    <span class="font-mono-data text-xs text-on-surface-variant/50 italic">Deleted user</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono-data text-xs text-on-surface-variant hidden md:table-cell">{{ $service->category?->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono-data text-xs text-secondary hidden lg:table-cell">{{ number_format($service->hourly_rate, 2) }} TC/hr</td>
                            <td class="px-4 py-3">
                                @if($service->is_active)
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-mono-data border border-emerald-500/20">Active</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant text-[10px] font-mono-data">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono-data text-[11px] text-on-surface-variant hidden lg:table-cell">{{ $service->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('admin.services.show', $service) }}"
                                       class="p-1.5 rounded-lg text-on-surface-variant hover:text-secondary hover:bg-white/5 transition-colors"
                                       title="View service">
                                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    </a>
                                    <a href="{{ route('admin.services.delete', $service) }}"
                                       class="p-1.5 rounded-lg text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors"
                                       title="Delete service">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <span class="material-symbols-outlined text-[32px] text-on-surface-variant/40 block mb-2">hub</span>
                                <p class="font-mono-data text-sm text-on-surface-variant">No services found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($services->hasPages())
            <div class="px-4 py-4 border-t border-white/10">{{ $services->links() }}</div>
        @endif
    </div>
</x-app-layout>