@section('title', 'Transaction Ledger')

<x-app-layout>
    @php
        $user = Auth::user();
        $balance = (float)$user->time_balance;
        $incoming = $user->incomingTransactions()->with('fromUser')->latest()->get();
        $outgoing = $user->outgoingTransactions()->with('toUser')->latest()->get();
        $allTransactions = $incoming->concat($outgoing)->sortByDesc('created_at');
    @endphp

    <div class="max-w-4xl mx-auto space-y-8">
        {{-- Header: Net Time Balance Card --}}
        <div class="glass-card p-6 md:p-8 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-6 glow-hover">
            <div>
                <span class="font-label-caps text-xs text-on-surface-variant block mb-1">Net Time Balance</span>
                <div class="flex items-baseline gap-2">
                    <span class="font-display-lg text-4xl md:text-5xl font-bold text-secondary drop-shadow-[0_0_16px_rgba(93,230,255,0.4)]">
                        {{ number_format($balance, 1) }}
                    </span>
                    <span class="font-body-lg text-lg text-on-surface-variant font-mono">HRS</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('wallet') }}" class="btn-stitch-secondary text-xs py-2.5 px-4">
                    <span class="material-symbols-outlined text-[16px]">account_balance_wallet</span> Wallet View
                </a>
                <button onclick="window.print()" class="btn-stitch-ghost text-xs py-2.5 px-4 border border-white/10">
                    <span class="material-symbols-outlined text-[16px]">download</span> Export
                </button>
            </div>
        </div>

        {{-- Transactions List Section --}}
        <div class="glass-card p-6 md:p-8 rounded-2xl">
            <h3 class="font-headline-md text-lg font-bold text-on-surface mb-6">Complete Temporal History</h3>

            <div class="space-y-4">
                @forelse($allTransactions as $tx)
                    @php $isIncoming = $tx->to_user_id === Auth::id(); @endphp
                    <div class="p-4 rounded-xl bg-surface-container-high/40 hover:bg-surface-container-high border border-white/5 flex items-center justify-between gap-4 transition-colors">
                        <div class="flex items-center gap-3.5">
                            <x-avatar :user="$isIncoming ? $tx->fromUser : $tx->toUser" size="md" />
                            <div>
                                <h4 class="font-headline text-sm font-semibold text-on-surface">
                                    {{ $isIncoming ? ($tx->fromUser->name ?? 'System Node') : ($tx->toUser->name ?? 'System Node') }}
                                </h4>
                                <p class="font-mono-data text-xs text-on-surface-variant">
                                    {{ $tx->type ?? 'Temporal Service Exchange' }} &bull; {{ $tx->created_at->format('M d, H:i') }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right font-mono-data text-sm font-bold {{ $isIncoming ? 'text-secondary' : 'text-tertiary' }}">
                            <span class="flex items-center justify-end gap-0.5">
                                <span class="material-symbols-outlined text-[16px]">{{ $isIncoming ? 'north_east' : 'south_west' }}</span>
                                {{ $isIncoming ? '+' : '-' }}{{ number_format($tx->amount, 1) }}h
                            </span>
                            <span class="text-[10px] text-on-surface-variant font-normal block">
                                {{ $isIncoming ? 'Received' : 'Dispatched' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-8 rounded-xl bg-surface-container-high/20 border border-white/5 text-center">
                        <span class="material-symbols-outlined text-[36px] text-on-surface-variant/40 mb-2">history_toggle_off</span>
                        <h4 class="font-headline-md text-base text-on-surface font-semibold mb-1">No Ledger Entries</h4>
                        <p class="font-body-md text-xs text-on-surface-variant max-w-sm mx-auto mb-4">
                            All your completed exchanges, project rewards, and time credit transfers will be recorded here immutably.
                        </p>
                        <a href="{{ route('services.index') }}" class="btn-stitch-primary text-xs py-2 px-4 inline-flex items-center gap-1.5 shadow-[0_0_12px_rgba(93,230,255,0.25)]">
                            <span class="material-symbols-outlined text-[14px]">explore</span> Explore Exchanges
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
