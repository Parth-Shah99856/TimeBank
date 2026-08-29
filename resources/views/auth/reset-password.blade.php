@section('title', 'Reset Cryptographic Key')

<x-guest-layout>
    <div class="mb-6">
        <h2 class="font-headline-lg text-2xl font-bold text-on-surface mb-1">Reset Cryptographic Key</h2>
        <p class="font-body-md text-xs text-on-surface-variant leading-relaxed">
            Specify a new high-entropy key for your temporal node.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-4">
            <label for="email" class="stitch-label">SECURE COMM LINK</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus
                   class="stitch-input @error('email') border-error/50 focus:border-error @enderror">
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-error font-mono-data" />
        </div>

        <div class="mb-4">
            <label for="password" class="stitch-label">NEW CRYPTOGRAPHIC KEY</label>
            <input id="password" type="password" name="password" required
                   class="stitch-input @error('password') border-error/50 focus:border-error @enderror"
                   placeholder="••••••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-error font-mono-data" />
        </div>

        <div class="mb-6">
            <label for="password_confirmation" class="stitch-label">CONFIRM NEW KEY</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                   class="stitch-input"
                   placeholder="••••••••••••">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-xs text-error font-mono-data" />
        </div>

        <button type="submit" class="btn-stitch-primary w-full shadow-[0_0_16px_rgba(93,230,255,0.3)]" :disabled="loading">
            <span x-show="!loading">UPDATE CRYPTOGRAPHIC KEY</span>
            <span x-show="loading">UPDATING...</span>
        </button>
    </form>
</x-guest-layout>
