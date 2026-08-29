<section>
    <header class="mb-6">
        <h2 class="font-headline-md text-lg font-bold text-on-surface">
            Update Cryptographic Key
        </h2>
        <p class="font-body-md text-xs text-on-surface-variant mt-1">
            Ensure your temporal account is utilizing a long, random key to stay secure.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="stitch-label">CURRENT KEY</label>
            <input id="update_password_current_password" name="current_password" type="password" class="stitch-input" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1 text-xs text-error font-mono-data" />
        </div>

        <div>
            <label for="update_password_password" class="stitch-label">NEW KEY</label>
            <input id="update_password_password" name="password" type="password" class="stitch-input" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1 text-xs text-error font-mono-data" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="stitch-label">CONFIRM NEW KEY</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="stitch-input" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1 text-xs text-error font-mono-data" />
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-stitch-primary text-xs py-2.5 px-6 shadow-[0_0_12px_rgba(93,230,255,0.3)]">
                UPDATE KEY
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                   class="font-mono-data text-xs text-secondary flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">check</span> Key Updated.
                </p>
            @endif
        </div>
    </form>
</section>
