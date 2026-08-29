@section('title', 'Notifications Center')

<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="font-headline-lg text-2xl md:text-3xl text-on-surface font-bold">Notifications Center</h1>
                <p class="font-body-md text-xs md:text-sm text-on-surface-variant mt-1">Real-time alerts, service proposals, and temporal verifications</p>
            </div>

            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn-stitch-secondary text-xs py-2 px-4">
                    <span class="material-symbols-outlined text-[16px]">done_all</span> Mark All as Read
                </button>
            </form>
        </div>

        @php
            $notifications = Auth::user()->notifications()->latest()->get();
        @endphp

        <div class="space-y-3">
            @forelse($notifications as $n)
                @php $isUnread = is_null($n->read_at); @endphp
                <div class="glass-card p-4 md:p-5 rounded-xl flex items-center justify-between gap-4 border-l-4 {{ $isUnread ? 'border-secondary bg-surface-container-high/60 shadow-[0_0_12px_rgba(93,230,255,0.1)]' : 'border-white/10 opacity-75' }}">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-surface-container-lowest border border-white/10 flex items-center justify-center text-secondary flex-shrink-0">
                            <span class="material-symbols-outlined text-[20px]">
                                {{ $n->data['icon'] ?? 'notifications' }}
                            </span>
                        </div>
                        <div>
                            <h4 class="font-headline text-sm font-semibold text-on-surface">{{ $n->data['title'] ?? 'System Transmission' }}</h4>
                            <p class="font-body-md text-xs text-on-surface-variant mt-0.5">{{ $n->data['message'] ?? $n->data['body'] ?? '' }}</p>
                            <span class="font-mono-data text-[10px] text-on-surface-variant/60 block mt-1">{{ $n->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    @if($isUnread)
                        <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="p-1.5 rounded-lg text-on-surface-variant hover:text-secondary hover:bg-white/5 transition-colors" title="Mark Read">
                                <span class="material-symbols-outlined text-[18px]">check</span>
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <x-empty-state
                    title="No Unread Transmissions"
                    description="You are fully synchronized. All temporal updates and contract alerts will appear here."
                    icon="notifications_none" />
            @endforelse
        </div>
    </div>
</x-app-layout>
