@section('title', 'Confirm Delete User')

<x-app-layout>
    <div class="max-w-2xl mx-auto">
        <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center gap-1 font-label-caps text-xs text-on-surface-variant hover:text-secondary mb-4">
            <span class="material-symbols-outlined text-[14px]">arrow_back</span> Back to User
        </a>

        <div class="glass-card p-6 md:p-8 rounded-2xl border-error/30 bg-error/5">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-full bg-error/15 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-error text-[22px]">delete_forever</span>
                </div>
                <div>
                    <h1 class="font-headline-md text-xl font-bold text-error">Delete User Account</h1>
                    <p class="font-body-md text-sm text-on-surface-variant mt-0.5">This action is permanent and cannot be undone.</p>
                </div>
            </div>

            {{-- User Summary --}}
            <div class="glass-card p-4 rounded-xl mb-6 bg-surface-container-high/30 space-y-2">
                <div class="flex items-center gap-3">
                    <x-avatar :user="$user" size="md" />
                    <div>
                        <p class="font-headline-md text-base font-bold text-on-surface">{{ $user->name }}</p>
                        <p class="font-mono-data text-xs text-on-surface-variant">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-3 border-t border-white/10">
                    <div class="text-center">
                        <p class="font-display-lg text-xl font-bold text-on-surface">{{ $meta['services_count'] }}</p>
                        <p class="font-label-caps text-[10px] text-on-surface-variant">Services</p>
                    </div>
                    <div class="text-center">
                        <p class="font-display-lg text-xl font-bold text-on-surface">{{ $meta['requests_count'] }}</p>
                        <p class="font-label-caps text-[10px] text-on-surface-variant">Requests</p>
                    </div>
                    <div class="text-center">
                        <p class="font-display-lg text-xl font-bold text-on-surface">{{ $meta['ideas_count'] }}</p>
                        <p class="font-label-caps text-[10px] text-on-surface-variant">Ideas</p>
                    </div>
                    <div class="text-center">
                        <p class="font-display-lg text-xl font-bold text-secondary">{{ $meta['time_balance'] }} TC</p>
                        <p class="font-label-caps text-[10px] text-on-surface-variant">Balance</p>
                    </div>
                </div>
            </div>

            {{-- Warning --}}
            <div class="bg-error/10 border border-error/30 rounded-xl p-4 mb-6">
                <p class="font-body-md text-sm text-error font-semibold mb-2">The following will happen permanently:</p>
                <ul class="font-mono-data text-xs text-error/80 space-y-1 list-disc list-inside">
                    <li>User account and all personal data deleted</li>
                    <li>All offered services and their pending requests deleted</li>
                    <li>All ideas and collaborator applications deleted</li>
                    <li>Ledger transactions are immutable — transaction records will have user reference nullified</li>
                    <li>Led projects (and their members/tasks) will be deleted</li>
                    <li>Notifications and sessions removed</li>
                </ul>
            </div>

            {{-- Confirmation Form --}}
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                @csrf
                @method('DELETE')

                <div class="mb-5">
                    <label class="font-label-caps text-xs text-on-surface-variant block mb-2">
                        Type <span class="text-error font-bold">DELETE</span> to confirm:
                    </label>
                    <input type="text" name="confirm" id="confirm-delete-input"
                           class="stitch-input @error('confirm') border-error/50 @enderror"
                           placeholder="DELETE"
                           autocomplete="off">
                    <x-input-error :messages="$errors->get('confirm')" class="mt-1 text-xs text-error font-mono-data" />
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('admin.users.show', $user) }}"
                       class="btn-stitch-secondary flex-1 justify-center text-sm">
                        <span class="material-symbols-outlined text-[16px]">cancel</span> Cancel
                    </a>
                    <button type="submit"
                            class="btn-stitch-danger flex-1 justify-center text-sm"
                            id="delete-user-btn">
                        <span class="material-symbols-outlined text-[16px]">delete_forever</span>
                        Delete {{ $user->name }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>