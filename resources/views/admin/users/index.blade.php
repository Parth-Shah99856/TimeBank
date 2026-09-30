@section('title', 'Admin — Users')

<x-app-layout>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-1 font-label-caps text-xs text-on-surface-variant hover:text-secondary mb-2">
                <span class="material-symbols-outlined text-[14px]">arrow_back</span> Admin Dashboard
            </a>
            <h1 class="font-headline-lg text-2xl md:text-3xl text-on-surface font-bold">User Management</h1>
        </div>
    </div>

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="glass-card p-4 rounded-xl mb-6 flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-on-surface-variant/60">
                <span class="material-symbols-outlined text-[16px]">search</span>
            </div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email…"
                   class="stitch-input pl-9 text-sm w-full">
        </div>
        <select name="role" class="stitch-input text-sm w-full sm:w-40">
            <option value="">All Roles</option>
            <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>Users</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admins</option>
        </select>
        <button type="submit" class="btn-stitch-primary text-sm py-2.5 px-5">Filter</button>
        @if(request('search') || request('role'))
            <a href="{{ route('admin.users.index') }}" class="btn-stitch-ghost text-sm py-2.5 px-4">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="glass-card rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/10 text-left">
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold">User</th>
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold hidden sm:table-cell">Email</th>
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold">Role</th>
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold hidden md:table-cell">Balance</th>
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold hidden lg:table-cell">Joined</th>
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold">Status</th>
                        <th class="px-4 py-3 font-label-caps text-[10px] text-on-surface-variant font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($users as $u)
                        <tr class="hover:bg-white/3 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <x-avatar :user="$u" size="sm" />
                                    <div>
                                        <p class="font-body-md text-sm font-semibold text-on-surface">{{ $u->name }}</p>
                                        <p class="font-mono-data text-[10px] text-on-surface-variant sm:hidden">{{ $u->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono-data text-xs text-on-surface-variant hidden sm:table-cell">
                                {{ $u->email }}
                            </td>
                            <td class="px-4 py-3">
                                @if($u->role === 'admin')
                                    <span class="px-2 py-0.5 rounded-full bg-tertiary/15 text-tertiary text-[10px] font-mono-data font-bold border border-tertiary/20">ADMIN</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant text-[10px] font-mono-data">USER</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono-data text-xs text-secondary hidden md:table-cell">
                                {{ number_format($u->time_balance, 2) }} TC
                            </td>
                            <td class="px-4 py-3 font-mono-data text-[11px] text-on-surface-variant hidden lg:table-cell">
                                {{ $u->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                @if($u->email_verified_at)
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-mono-data border border-emerald-500/20">Verified</span>
                                @elseif($u->isDemoAccount())
                                    <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary text-[10px] font-mono-data border border-primary/20">Demo</span>
                                @elseif($u->isLegacyUser())
                                    <span class="px-2 py-0.5 rounded-full bg-secondary/10 text-secondary text-[10px] font-mono-data border border-secondary/20">Legacy</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-yellow-500/10 text-yellow-400 text-[10px] font-mono-data border border-yellow-500/20">Unverified</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('admin.users.show', $u) }}"
                                       class="p-1.5 rounded-lg text-on-surface-variant hover:text-secondary hover:bg-white/5 transition-colors"
                                       title="View user">
                                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    </a>
                                    @if($u->id !== auth()->id())
                                        <a href="{{ route('admin.users.delete', $u) }}"
                                           class="p-1.5 rounded-lg text-on-surface-variant hover:text-error hover:bg-error/10 transition-colors"
                                           title="Delete user">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </a>
                                    @else
                                        <span class="p-1.5 text-on-surface-variant/30 cursor-not-allowed" title="Cannot delete yourself">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <span class="material-symbols-outlined text-[32px] text-on-surface-variant/40 block mb-2">manage_accounts</span>
                                <p class="font-mono-data text-sm text-on-surface-variant">No users found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-4 py-4 border-t border-white/10">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-app-layout>