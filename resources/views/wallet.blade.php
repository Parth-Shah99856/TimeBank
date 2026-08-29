@section('title', 'Time Credit Wallet')

<x-app-layout>
    @php
        $user = Auth::user();
        $balance = (float)$user->time_balance;
        $transactions = $user->incomingTransactions()->with(['fromUser', 'serviceRequest'])->latest()->take(5)->get()
            ->concat($user->outgoingTransactions()->with(['toUser', 'serviceRequest'])->latest()->take(5)->get())
            ->sortByDesc('created_at')->take(5);

        // Real earned / spent from transaction ledger
        $totalEarned = (float)$user->incomingTransactions()
            ->where('type', \App\Models\Transaction::TYPE_SERVICE_EXCHANGE)->sum('amount');
        $totalSpent  = (float)$user->outgoingTransactions()
            ->where('type', \App\Models\Transaction::TYPE_SERVICE_EXCHANGE)->sum('amount');
        $flowMax = max($totalEarned, $totalSpent, 1);
        $earnedPct = min(100, round(($totalEarned / $flowMax) * 100));
        $spentPct  = min(100, round(($totalSpent  / $flowMax) * 100));

        // Real trust score from received reviews (0-100 scale)
        $avgRating = (float)$user->reviewsReceived()->avg('rating');
        $trustScore = $avgRating > 0 ? (int)round($avgRating * 20) : ($balance > 0 ? 70 : 0);
        $trustDash  = 276; // circle circumference (r=44)
        $trustOffset = round($trustDash * (1 - $trustScore / 100));
        $completedExchanges = $user->reviewsReceived()->count();
    @endphp

    <div class="max-w-4xl mx-auto space-y-8">
        {{-- Wallet Hero Balance Card --}}
        <div class="glass-card p-8 md:p-12 rounded-2xl relative overflow-hidden group glow-hover text-center">
            <div class="absolute -right-24 -top-24 w-80 h-80 bg-secondary/15 rounded-full blur-3xl pointer-events-none"></div>

            <span class="font-label-caps text-xs text-on-surface-variant block mb-3">AVAILABLE TIME CREDITS</span>

            <div class="flex items-baseline justify-center gap-3 mb-6">
                <span class="font-display-lg text-5xl sm:text-6xl md:text-7xl font-bold text-secondary drop-shadow-[0_0_24px_rgba(93,230,255,0.5)]">
                    {{ number_format($balance, 1) }}
                </span>
                <span class="font-body-lg text-xl text-on-surface-variant font-mono">HRS</span>
            </div>

            <div class="flex items-center justify-center gap-4 relative z-10 max-w-sm mx-auto">
                <a href="{{ route('services.index') }}" class="btn-stitch-primary text-xs py-3 px-6 w-1/2 shadow-[0_0_16px_rgba(93,230,255,0.3)]">
                    <span class="material-symbols-outlined text-[18px]">send</span> SEND
                </a>
                <a href="{{ route('services.create') }}" class="btn-stitch-secondary text-xs py-3 px-6 w-1/2">
                    <span class="material-symbols-outlined text-[18px]">handshake</span> OFFER
                </a>
            </div>
        </div>

        {{-- Grid: Time Flow & Trust Score --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            {{-- Time Flow Card --}}
            <div class="glass-card p-6 md:p-8 rounded-2xl md:col-span-7 flex flex-col justify-between">
                <h3 class="font-headline-md text-lg font-bold text-on-surface mb-6">Time Flow</h3>

                <div class="space-y-6">
                    <div>
                        <div class="flex items-center justify-between font-mono-data text-xs mb-2">
                            <span class="text-on-surface-variant">Earned (Incoming)</span>
                            <span class="text-secondary font-bold">+ {{ number_format($totalEarned, 1) }} HRS</span>
                        </div>
                        <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
                            <div class="h-full bg-secondary rounded-full shadow-[0_0_10px_rgba(93,230,255,0.6)]" style="width: {{ $earnedPct }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between font-mono-data text-xs mb-2">
                            <span class="text-on-surface-variant">Spent (Engagements)</span>
                            <span class="text-tertiary font-bold">- {{ number_format($totalSpent, 1) }} HRS</span>
                        </div>
                        <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
                            <div class="h-full bg-tertiary rounded-full shadow-[0_0_10px_rgba(249,189,34,0.6)]" style="width: {{ $spentPct }}%"></div>
                        </div>
                    </div>
                </div>

                <p class="font-mono-data text-[11px] text-on-surface-variant/60 mt-6 pt-4 border-t border-white/5">
                    Zero inflation &bull; 1.0 hr = 1.0 TC community rate
                </p>
            </div>

            {{-- Trust Score Card --}}
            <div class="glass-card p-6 md:p-8 rounded-2xl md:col-span-5 flex flex-col items-center justify-center text-center">
                <div class="relative w-28 h-28 mb-4">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="44" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="5"></circle>
                        <circle cx="50" cy="50" r="44" fill="none" stroke="#f9bd22" stroke-width="5"
                                stroke-dasharray="{{ $trustDash }}" stroke-dashoffset="{{ $trustOffset }}"
                                stroke-linecap="round"
                                class="drop-shadow-[0_0_12px_rgba(249,189,34,0.6)]"></circle>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center font-display-lg text-2xl font-bold text-tertiary">
                        {{ $trustScore }}
                    </div>
                </div>
                <h4 class="font-headline-md text-base font-bold text-on-surface mb-1">Trust Score</h4>
                @if($completedExchanges > 0)
                    <p class="font-body-md text-xs text-on-surface-variant">Based on {{ $completedExchanges }} review{{ $completedExchanges > 1 ? 's' : '' }}</p>
                @else
                    <p class="font-body-md text-xs text-on-surface-variant">Complete exchanges to build your score</p>
                @endif
            </div>
        </div>

        {{-- Ledger Section --}}
        <div class="glass-card p-6 md:p-8 rounded-2xl">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-headline-md text-lg font-bold text-on-surface">Ledger</h3>
                <a href="{{ route('transactions.index') }}" class="font-label-caps text-xs text-secondary hover:underline flex items-center gap-1">
                    View Full History <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
            </div>

            <div class="space-y-4">
                @forelse($transactions as $tx)
                    @php $isIncoming = $tx->to_user_id === Auth::id(); @endphp
                    <div class="p-4 rounded-xl bg-surface-container-high/40 border-l-2 {{ $isIncoming ? 'border-secondary' : 'border-tertiary' }} flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center {{ $isIncoming ? 'text-secondary' : 'text-tertiary' }}">
                                <span class="material-symbols-outlined text-[20px]">{{ $isIncoming ? 'call_received' : 'call_made' }}</span>
                            </div>
                            <div>
                                <h4 class="font-headline text-sm font-semibold text-on-surface">{{ $tx->type ?? 'Temporal Transfer' }}</h4>
                                <p class="font-mono-data text-xs text-on-surface-variant">
                                    {{ $isIncoming ? 'From: ' . ($tx->fromUser->name ?? 'Network Node') : 'To: ' . ($tx->toUser->name ?? 'Network Node') }} &bull; {{ $tx->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right font-mono-data text-sm font-bold {{ $isIncoming ? 'text-secondary' : 'text-tertiary' }}">
                            {{ $isIncoming ? '+' : '-' }}{{ number_format($tx->amount, 1) }} HRS
                        </div>
                    </div>
                @empty
                    <div class="p-6 rounded-xl bg-surface-container-high/20 border border-white/5 text-center">
                        <span class="material-symbols-outlined text-[32px] text-on-surface-variant/40 mb-2">receipt_long</span>
                        <p class="font-mono-data text-xs text-on-surface-variant">No recorded transactions yet.</p>
                        <p class="font-body-md text-xs text-on-surface-variant/60 mt-1">Complete service requests or exchange skills to build your ledger.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
