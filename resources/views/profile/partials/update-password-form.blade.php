<section>
    <header class="mb-5">
        <h2 class="font-headline-md text-lg font-bold text-on-surface">
            Update Cryptographic Key
        </h2>
        <p class="font-body-md text-xs text-on-surface-variant mt-1">
            Rotate your temporal access key. Stored keys are irreversibly hashed and protected against unauthorized retrieval.
        </p>
    </header>

    {{-- Security Architecture Status Notice --}}
    <div class="mb-6 p-3.5 rounded-xl bg-surface-container-high/60 border border-white/10 flex items-start gap-3">
        <span class="material-symbols-outlined text-secondary text-[20px] mt-0.5">lock_clock</span>
        <div class="flex-1">
            <div class="flex items-center gap-2">
                <span class="font-label-caps text-[11px] text-secondary font-semibold">STORAGE STATUS: SECURE HASH</span>
                <span class="px-1.5 py-0.5 text-[9px] font-mono-data bg-secondary/15 text-secondary border border-secondary/30 rounded">BCRYPT 12-ROUNDS</span>
            </div>
            <p class="font-mono-data text-xs text-on-surface-variant/80 mt-1 leading-relaxed">
                Your current key is stored as a one-way cryptographic hash and cannot be displayed in plaintext. Enter your existing key below to authorize cryptographic rotation.
            </p>
        </div>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="space-y-6" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
        @csrf
        @method('put')

        {{-- Current Key Input --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="update_password_current_password" class="stitch-label !mb-0">AUTHORIZE WITH CURRENT KEY</label>
                <span class="font-mono-data text-[11px] text-on-surface-variant/70">Required for rotation</span>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/60">
                    <span class="material-symbols-outlined text-[18px]">key</span>
                </div>
                <input id="update_password_current_password"
                       name="current_password"
                       :type="showCurrent ? 'text' : 'password'"
                       class="stitch-input pl-10 pr-10 @error('current_password', 'updatePassword') border-error/50 focus:border-error @enderror"
                       autocomplete="current-password"
                       placeholder="Enter current key to authorize rotation..." />
                <button type="button"
                        @click="showCurrent = !showCurrent"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-on-surface-variant hover:text-white transition-colors"
                        tabindex="-1">
                    <span class="material-symbols-outlined text-[18px]" x-text="showCurrent ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5 text-xs text-error font-mono-data" />
        </div>

        {{-- New Key Input --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="update_password_password" class="stitch-label !mb-0">NEW CRYPTOGRAPHIC KEY</label>
                <span class="font-mono-data text-[11px] text-on-surface-variant/70">Min. 8 characters</span>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/60">
                    <span class="material-symbols-outlined text-[18px]">vpn_key</span>
                </div>
                <input id="update_password_password"
                       name="password"
                       :type="showNew ? 'text' : 'password'"
                       class="stitch-input pl-10 pr-10 @error('password', 'updatePassword') border-error/50 focus:border-error @enderror"
                       autocomplete="new-password"
                       placeholder="Enter new high-entropy cryptographic key..." />
                <button type="button"
                        @click="showNew = !showNew"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-on-surface-variant hover:text-white transition-colors"
                        tabindex="-1">
                    <span class="material-symbols-outlined text-[18px]" x-text="showNew ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5 text-xs text-error font-mono-data" />
        </div>

        {{-- Confirm New Key Input --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="update_password_password_confirmation" class="stitch-label !mb-0">CONFIRM NEW KEY</label>
                <span class="font-mono-data text-[11px] text-on-surface-variant/70">Must match new key</span>
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/60">
                    <span class="material-symbols-outlined text-[18px]">verified_user</span>
                </div>
                <input id="update_password_password_confirmation"
                       name="password_confirmation"
                       :type="showConfirm ? 'text' : 'password'"
                       class="stitch-input pl-10 pr-10 @error('password_confirmation', 'updatePassword') border-error/50 focus:border-error @enderror"
                       autocomplete="new-password"
                       placeholder="Confirm new cryptographic key..." />
                <button type="button"
                        @click="showConfirm = !showConfirm"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-on-surface-variant hover:text-white transition-colors"
                        tabindex="-1">
                    <span class="material-symbols-outlined text-[18px]" x-text="showConfirm ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1.5 text-xs text-error font-mono-data" />
        </div>

        {{-- Action & Status --}}
        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-stitch-primary text-xs py-2.5 px-6 shadow-[0_0_12px_rgba(93,230,255,0.3)]">
                <span class="material-symbols-outlined text-[16px] mr-1.5">sync_lock</span> UPDATE KEY
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                   class="font-mono-data text-xs text-secondary flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-secondary/10 border border-secondary/30">
                    <span class="material-symbols-outlined text-[16px]">check_circle</span> Cryptographic Key Rotated Successfully.
                </p>
            @endif
        </div>
    </form>
</section>
