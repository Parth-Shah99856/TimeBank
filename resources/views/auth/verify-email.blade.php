@section('title', 'Verify Comm Link')

<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="w-14 h-14 rounded-2xl bg-secondary/15 border border-secondary/40 flex items-center justify-center text-secondary mx-auto mb-4 shadow-[0_0_16px_rgba(93,230,255,0.3)]">
            <span class="material-symbols-outlined text-[30px]">mark_email_read</span>
        </div>
        <h2 class="font-headline-lg text-2xl font-bold text-on-surface mb-2">Verify Comm Link</h2>
        <p class="font-body-md text-xs text-on-surface-variant leading-relaxed max-w-xs mx-auto">
            A synchronization transmission has been dispatched to your secure comm link. Please confirm receipt to activate your node.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-6 p-3 rounded-lg bg-secondary/10 border border-secondary/30 text-xs font-mono-data text-secondary text-center">
            A new verification transmission has been dispatched to your address.
        </div>
    @endif

    <div class="flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-stitch-primary w-full shadow-[0_0_16px_rgba(93,230,255,0.3)]">
                RESEND TRANSMISSION
            </button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-stitch-ghost w-full text-xs">
                Disconnect Node
            </button>
        </form>
    </div>
</x-guest-layout>
