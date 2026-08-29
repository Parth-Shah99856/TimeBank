<section class="space-y-6" x-data="{ confirmingUserDeletion: false }">
    <header>
        <h2 class="font-headline-md text-lg font-bold text-error">
            Deactivate Temporal Node
        </h2>
        <p class="font-body-md text-xs text-on-surface-variant mt-1">
            Once your node is decommissioned, all accrued time credits and transactional histories will be permanently unlinked.
        </p>
    </header>

    <button type="button" @click="confirmingUserDeletion = true" class="btn-stitch-danger text-xs py-2.5 px-4">
        DECOMMISSION NODE
    </button>

    {{-- Confirmation Modal --}}
    <div x-show="confirmingUserDeletion" class="stitch-overlay" style="display: none;" @click.self="confirmingUserDeletion = false">
        <div class="stitch-modal animate-fade-in-up border-error/40">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <h2 class="font-headline-md text-lg font-bold text-on-surface mb-2">
                    Are you sure you want to decommission your node?
                </h2>

                <p class="font-body-md text-xs text-on-surface-variant mb-6">
                    Please enter your cryptographic key to authorize permanent removal of your account and temporal ledger.
                </p>

                <div class="mb-6">
                    <label for="password" class="stitch-label">CRYPTOGRAPHIC KEY</label>
                    <input id="password" name="password" type="password" class="stitch-input" placeholder="••••••••••••" />
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1 text-xs text-error font-mono-data" />
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="confirmingUserDeletion = false" class="btn-stitch-secondary text-xs">
                        CANCEL
                    </button>
                    <button type="submit" class="btn-stitch-danger text-xs">
                        CONFIRM DECOMMISSION
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
