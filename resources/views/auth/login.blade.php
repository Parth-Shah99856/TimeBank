@section('title', 'Initialize Connection')

<x-guest-layout>
    {{-- Session Status --}}
    <x-auth-session-status class="mb-4 text-xs font-mono-data text-secondary" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        {{-- Email Address --}}
        <div class="mb-5">
            <label for="email" class="stitch-label">
                EMAIL ADDRESS
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/60">
                    <span class="material-symbols-outlined text-[18px]">mail</span>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="stitch-input pl-10 @error('email') border-error/50 focus:border-error @enderror"
                       placeholder="architect@timebank.net">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-xs text-error font-mono-data" />
        </div>

        {{-- Access Key / Password --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <label for="password" class="stitch-label !mb-0">
                    ACCESS KEY
                </label>
                @if (Route::has('password.request'))
                    <a class="font-label-caps text-xs text-secondary hover:text-white transition-colors" href="{{ route('password.request') }}">
                        Forgot Key?
                    </a>
                @endif
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/60">
                    <span class="material-symbols-outlined text-[18px]">key</span>
                </div>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="stitch-input pl-10 @error('password') border-error/50 focus:border-error @enderror"
                       placeholder="••••••••••••">
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-xs text-error font-mono-data" />
        </div>

        {{-- Remember Me --}}
        <div class="flex items-center justify-between mb-6">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember"
                       class="w-4 h-4 rounded bg-surface-container-low border-white/20 text-secondary focus:ring-secondary/40 focus:ring-offset-background">
                <span class="font-label-caps text-xs text-on-surface-variant">Remember Node</span>
            </label>
        </div>

        {{-- Submit Button --}}
        <button type="submit" class="btn-stitch-primary w-full shadow-[0_0_16px_rgba(93,230,255,0.3)]" :disabled="loading">
            <span x-show="!loading" class="inline-flex items-center gap-2">
                INITIALIZE CONNECTION <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </span>
            <span x-show="loading" class="inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] animate-spin">sync</span> CONNECTING...
            </span>
        </button>

        {{-- Divider --}}
        <div class="relative my-6 flex items-center justify-center">
            <div class="border-t border-white/10 w-full"></div>
            <span class="bg-surface-container-high/80 px-3 font-mono-data text-[10px] text-on-surface-variant uppercase tracking-widest absolute">OR</span>
        </div>

        {{-- Register Link --}}
        <p class="text-center font-mono-data text-xs text-on-surface-variant">
            Unregistered node?
            <a href="{{ route('register') }}" class="text-secondary hover:underline font-semibold">Initialize Account</a>
        </p>
    </form>
</x-guest-layout>
