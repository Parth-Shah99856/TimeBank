@section('title', 'Initialize Account')

<x-guest-layout>
    {{-- Step Header --}}
    <div class="mb-6">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-secondary/10 border border-secondary/30 font-label-caps text-[11px] text-secondary mb-3">
            <span class="material-symbols-outlined text-[13px]">badge</span>
            Step 1 of 3
        </div>
        <h2 class="font-headline-lg text-2xl font-bold text-on-surface mb-1">Initialize Account</h2>
        <p class="font-body-md text-xs text-on-surface-variant leading-relaxed">
            Join the decentralized network. Your time is the new standard of value.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" x-data="{ loading: false, showKey: false }" @submit="loading = true">
        @csrf

        {{-- Legal Name --}}
        <div class="mb-4">
            <label for="name" class="stitch-label">LEGAL NAME</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/60">
                    <span class="material-symbols-outlined text-[18px]">person</span>
                </div>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                       class="stitch-input pl-10 @error('name') border-error/50 focus:border-error @enderror"
                       placeholder="Jane Doe">
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-1 text-xs text-error font-mono-data" />
        </div>

        {{-- Secure Comm Link (Email) --}}
        <div class="mb-4">
            <label for="email" class="stitch-label">SECURE COMM LINK</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/60">
                    <span class="material-symbols-outlined text-[18px]">mail</span>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                       class="stitch-input pl-10 @error('email') border-error/50 focus:border-error @enderror"
                       placeholder="jane@network.link">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-error font-mono-data" />
        </div>

        {{-- Cryptographic Key (Password) --}}
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <label for="password" class="stitch-label !mb-0">CRYPTOGRAPHIC KEY</label>
                <span class="font-mono-data text-[10px] text-tertiary flex items-center gap-1">
                    <span class="material-symbols-outlined text-[12px]">verified_user</span> HIGHLY ENCRYPTED
                </span>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/60">
                    <span class="material-symbols-outlined text-[18px]">key</span>
                </div>
                <input id="password" :type="showKey ? 'text' : 'password'" name="password" required autocomplete="new-password"
                       class="stitch-input pl-10 pr-10 @error('password') border-error/50 focus:border-error @enderror"
                       placeholder="••••••••••••">
                <button type="button" @click="showKey = !showKey" class="absolute inset-y-0 right-0 pr-3 flex items-center text-on-surface-variant/60 hover:text-white">
                    <span class="material-symbols-outlined text-[18px]" x-text="showKey ? 'visibility_off' : 'visibility'"></span>
                </button>
            </div>
            {{-- Strength Bars Indicator --}}
            <div class="grid grid-cols-4 gap-1.5 mt-2">
                <div class="h-1 rounded bg-secondary shadow-[0_0_6px_rgba(93,230,255,0.8)]"></div>
                <div class="h-1 rounded bg-secondary shadow-[0_0_6px_rgba(93,230,255,0.8)]"></div>
                <div class="h-1 rounded bg-tertiary/70"></div>
                <div class="h-1 rounded bg-surface-container-high"></div>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-error font-mono-data" />
        </div>

        {{-- Confirm Password --}}
        <div class="mb-6">
            <label for="password_confirmation" class="stitch-label">CONFIRM CRYPTOGRAPHIC KEY</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/60">
                    <span class="material-symbols-outlined text-[18px]">key</span>
                </div>
                <input id="password_confirmation" :type="showKey ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                       class="stitch-input pl-10"
                       placeholder="••••••••••••">
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-xs text-error font-mono-data" />
        </div>

        {{-- Submit Button --}}
        <button type="submit" class="btn-stitch-primary w-full shadow-[0_0_16px_rgba(93,230,255,0.3)] mb-4" :disabled="loading">
            <span x-show="!loading" class="inline-flex items-center gap-2">
                PROCEED TO VALIDATION <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </span>
            <span x-show="loading" class="inline-flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] animate-spin">sync</span> INITIALIZING...
            </span>
        </button>

        {{-- Architect Protocol Disclaimer --}}
        <p class="font-mono-data text-[10px] text-on-surface-variant/70 text-center leading-relaxed mb-4">
            By proceeding, you adhere to the <span class="text-secondary font-semibold">Architect's Protocol</span> and agree to safeguard community value.
        </p>

        {{-- Sign In Link --}}
        <p class="text-center font-mono-data text-xs text-on-surface-variant pt-2 border-t border-white/5">
            Already verified?
            <a href="{{ route('login') }}" class="text-secondary hover:underline font-semibold">Initialize Connection</a>
        </p>
    </form>
</x-guest-layout>
