@section('title', 'Recover Access Key')

<x-guest-layout>
    <div class="mb-6">
        <h2 class="font-headline-lg text-2xl font-bold text-on-surface mb-1">Recover Access Key</h2>
        <p class="font-body-md text-xs text-on-surface-variant leading-relaxed">
            Enter your verified comm link to receive temporal decryption instructions.
        </p>
    </div>

    <x-auth-session-status class="mb-4 text-xs font-mono-data text-secondary" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <div class="mb-6">
            <label for="email" class="stitch-label">SECURE COMM LINK</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/60">
                    <span class="material-symbols-outlined text-[18px]">mail</span>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="stitch-input pl-10 @error('email') border-error/50 focus:border-error @enderror"
                       placeholder="architect@timebank.net">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-error font-mono-data" />
        </div>

        <button type="submit" class="btn-stitch-primary w-full shadow-[0_0_16px_rgba(93,230,255,0.3)] mb-4" :disabled="loading">
            <span x-show="!loading" class="inline-flex items-center gap-2">
                TRANSMIT RECOVERY KEY <span class="material-symbols-outlined text-[18px]">send</span>
            </span>
            <span x-show="loading" class="inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] animate-spin">sync</span> TRANSMITTING...
            </span>
        </button>

        <p class="text-center font-mono-data text-xs text-on-surface-variant pt-2 border-t border-white/5">
            <a href="{{ route('login') }}" class="text-secondary hover:underline font-semibold">Back to Connection</a>
        </p>
    </form>
</x-guest-layout>
