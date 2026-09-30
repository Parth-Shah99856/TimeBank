@section('title', 'Confirm Delete Service')

<x-app-layout>
    <div class="max-w-2xl mx-auto">
        <a href="{{ route('admin.services.show', $service) }}" class="inline-flex items-center gap-1 font-label-caps text-xs text-on-surface-variant hover:text-secondary mb-4">
            <span class="material-symbols-outlined text-[14px]">arrow_back</span> Back to Service
        </a>

        <div class="glass-card p-6 md:p-8 rounded-2xl border-error/30 bg-error/5">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-full bg-error/15 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-error text-[22px]">delete_forever</span>
                </div>
                <div>
                    <h1 class="font-headline-md text-xl font-bold text-error">Delete Service</h1>
                    <p class="font-body-md text-sm text-on-surface-variant mt-0.5">This action is permanent and cannot be undone.</p>
                </div>
            </div>

            <div class="glass-card p-4 rounded-xl mb-6 bg-surface-container-high/30">
                <h2 class="font-headline-md text-base font-bold text-on-surface mb-1">{{ $service->title }}</h2>
                <p class="font-mono-data text-xs text-on-surface-variant">Owner: {{ $service->user?->name ?? 'Deleted user' }} &bull; {{ $service->category?->name }}</p>
                <p class="font-mono-data text-sm text-secondary font-bold mt-2">
                    {{ $requestCount }} associated request(s) will also be deleted.
                </p>
            </div>

            <div class="bg-error/10 border border-error/30 rounded-xl p-4 mb-6">
                <ul class="font-mono-data text-xs text-error/80 space-y-1 list-disc list-inside">
                    <li>Service listing permanently removed</li>
                    <li>All {{ $requestCount }} service request(s) and associated messages/OTPs deleted</li>
                    <li>Reviews associated with these requests deleted</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('admin.services.destroy', $service) }}">
                @csrf
                @method('DELETE')
                <div class="mb-5">
                    <label class="font-label-caps text-xs text-on-surface-variant block mb-2">
                        Type <span class="text-error font-bold">DELETE</span> to confirm:
                    </label>
                    <input type="text" name="confirm" class="stitch-input @error('confirm') border-error/50 @enderror" placeholder="DELETE" autocomplete="off">
                    <x-input-error :messages="$errors->get('confirm')" class="mt-1 text-xs text-error font-mono-data" />
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('admin.services.show', $service) }}" class="btn-stitch-secondary flex-1 justify-center text-sm">
                        <span class="material-symbols-outlined text-[16px]">cancel</span> Cancel
                    </a>
                    <button type="submit" class="btn-stitch-danger flex-1 justify-center text-sm">
                        <span class="material-symbols-outlined text-[16px]">delete_forever</span> Delete Service
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>