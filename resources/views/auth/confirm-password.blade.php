@section('title', 'Confirm Security Protocol')

<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="w-12 h-12 rounded-xl bg-tertiary/10 border border-tertiary/40 flex items-center justify-center text-tertiary mx-auto mb-3">
            <span class="material-symbols-outlined text-[24px]">lock</span>
        </div>
        <h2 class="font-headline-lg text-2xl font-bold text-on-surface mb-1">Confirm Security Protocol</h2>
        <p class="font-body-md text-xs text-on-surface-variant leading-relaxed">
            This is a restricted temporal sector. Please confirm your cryptographic key.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <div class="mb-6">
            <label for="password" class="stitch-label">CRYPTOGRAPHIC KEY</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="stitch-input @error('password') border-error/50 focus:border-error @enderror"
                   placeholder="••••••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-error font-mono-data" />
        </div>

        <button type="submit" class="btn-stitch-primary w-full shadow-[0_0_16px_rgba(93,230,255,0.3)]" :disabled="loading">
            CONFIRM PROTOCOL
        </button>
    </form>
</x-guest-layout>
