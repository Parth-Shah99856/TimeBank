@section('title', 'Admin Maintenance')

<x-app-layout>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-1 font-label-caps text-xs text-on-surface-variant hover:text-secondary mb-2">
                <span class="material-symbols-outlined text-[14px]">arrow_back</span> Admin Dashboard
            </a>
            <h1 class="font-headline-lg text-2xl md:text-3xl text-on-surface font-bold">Maintenance</h1>
            <p class="font-body-md text-xs text-on-surface-variant mt-1">Platform health & diagnostics — read-only view</p>
        </div>
    </div>

    {{-- App Health --}}
    <div class="glass-card p-5 rounded-xl mb-6">
        <h3 class="font-headline-md text-base font-bold text-on-surface mb-4">
            <span class="material-symbols-outlined text-[18px] align-middle mr-1">monitor_heart</span>
            Application Health
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
            @foreach([
                ['label' => 'Laravel', 'value' => $health['laravel_version'], 'icon' => 'code'],
                ['label' => 'PHP', 'value' => $health['php_version'], 'icon' => 'terminal'],
                ['label' => 'Environment', 'value' => strtoupper($health['env']), 'icon' => 'cloud'],
                ['label' => 'Debug Mode', 'value' => $health['debug'] ? 'ON' : 'OFF', 'icon' => 'bug_report', 'warn' => $health['debug']],
                ['label' => 'Database', 'value' => strtoupper($health['db_connection']), 'icon' => 'storage'],
            ] as $item)
                <div class="p-3 rounded-xl bg-surface-container-high/30 text-center">
                    <span class="material-symbols-outlined text-[18px] {{ isset($item['warn']) && $item['warn'] ? 'text-yellow-400' : 'text-secondary' }} block mb-1">{{ $item['icon'] }}</span>
                    <p class="font-display-lg text-sm font-bold {{ isset($item['warn']) && $item['warn'] ? 'text-yellow-400' : 'text-on-surface' }}">{{ $item['value'] }}</p>
                    <p class="font-label-caps text-[10px] text-on-surface-variant">{{ $item['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Diagnostics Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="glass-card p-5 rounded-xl {{ $orphanedServices > 0 ? 'border-yellow-500/30' : '' }}">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-[18px] {{ $orphanedServices > 0 ? 'text-yellow-400' : 'text-emerald-400' }}">hub</span>
                <span class="font-label-caps text-[10px] text-on-surface-variant">ORPHANED SERVICES</span>
            </div>
            <p class="font-display-lg text-2xl font-bold {{ $orphanedServices > 0 ? 'text-yellow-400' : 'text-emerald-400' }}">{{ $orphanedServices }}</p>
            <p class="font-mono-data text-[10px] text-on-surface-variant mt-1">Services with no owner</p>
        </div>

        <div class="glass-card p-5 rounded-xl">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-[18px] text-secondary">sync_alt</span>
                <span class="font-label-caps text-[10px] text-on-surface-variant">REQUESTS (NO SERVICE)</span>
            </div>
            <p class="font-display-lg text-2xl font-bold text-on-surface">{{ $requestsWithNoService }}</p>
            <p class="font-mono-data text-[10px] text-on-surface-variant mt-1">Requests where service was deleted</p>
        </div>

        <div class="glass-card p-5 rounded-xl {{ $unverifiedUsers->count() > 0 ? 'border-yellow-500/30' : '' }}">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-[18px] {{ $unverifiedUsers->count() > 0 ? 'text-yellow-400' : 'text-emerald-400' }}">mail</span>
                <span class="font-label-caps text-[10px] text-on-surface-variant">UNVERIFIED USERS</span>
            </div>
            <p class="font-display-lg text-2xl font-bold {{ $unverifiedUsers->count() > 0 ? 'text-yellow-400' : 'text-emerald-400' }}">{{ $unverifiedUsers->count() }}</p>
            <p class="font-mono-data text-[10px] text-on-surface-variant mt-1">Email not verified</p>
        </div>
    </div>

    {{-- Unverified Users List --}}
    @if($unverifiedUsers->isNotEmpty())
    <div class="glass-card p-5 rounded-xl mb-6">
        <h3 class="font-headline-md text-base font-bold text-on-surface mb-4">
            <span class="material-symbols-outlined text-[18px] align-middle mr-1 text-yellow-400">warning</span>
            Unverified Accounts (Latest 20)
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/10 text-left">
                        <th class="px-3 py-2 font-label-caps text-[10px] text-on-surface-variant">Name</th>
                        <th class="px-3 py-2 font-label-caps text-[10px] text-on-surface-variant">Email</th>
                        <th class="px-3 py-2 font-label-caps text-[10px] text-on-surface-variant">Registered</th>
                        <th class="px-3 py-2 font-label-caps text-[10px] text-on-surface-variant">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($unverifiedUsers as $u)
                        <tr class="hover:bg-white/3">
                            <td class="px-3 py-2.5 font-body-md text-sm text-on-surface">{{ $u->name }}</td>
                            <td class="px-3 py-2.5 font-mono-data text-xs text-on-surface-variant">{{ $u->email }}</td>
                            <td class="px-3 py-2.5 font-mono-data text-xs text-on-surface-variant">{{ $u->created_at->diffForHumans() }}</td>
                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.users.show', $u) }}" class="font-label-caps text-[10px] text-secondary hover:underline">View</a>
                                    <a href="{{ route('admin.users.delete', $u) }}" class="font-label-caps text-[10px] text-error hover:underline">Delete</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Audit Log --}}
    <div class="glass-card p-5 rounded-xl">
        <h3 class="font-headline-md text-base font-bold text-on-surface mb-4">
            <span class="material-symbols-outlined text-[18px] align-middle mr-1">history</span>
            Recent Admin Audit Log
        </h3>
        @forelse($auditLogs as $log)
            <div class="py-3 border-b border-white/5 last:border-0 flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="font-mono-data text-[10px] font-bold
                            @if(str_contains($log->action, 'deleted')) text-error
                            @else text-secondary @endif">
                            {{ strtoupper(str_replace('.', ' ', $log->action)) }}
                        </span>
                    </div>
                    <p class="font-body-md text-sm text-on-surface">{{ $log->target_label }}</p>
                    <p class="font-mono-data text-[10px] text-on-surface-variant">by {{ $log->admin_name }}</p>
                </div>
                <span class="font-mono-data text-[10px] text-on-surface-variant shrink-0">{{ $log->created_at->diffForHumans() }}</span>
            </div>
        @empty
            <div class="py-8 text-center">
                <span class="material-symbols-outlined text-[28px] text-on-surface-variant/40 block mb-2">history</span>
                <p class="font-mono-data text-xs text-on-surface-variant">No admin actions recorded yet.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>