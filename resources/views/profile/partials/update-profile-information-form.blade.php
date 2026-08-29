<section>
    <header class="mb-6">
        <h2 class="font-headline-md text-lg font-bold text-on-surface">
            Profile Information
        </h2>
        <p class="font-body-md text-xs text-on-surface-variant mt-1">
            Update your temporal node identity, headline, and secure comm link.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="stitch-label">LEGAL NAME *</label>
            <input id="name" name="name" type="text" class="stitch-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            <x-input-error class="mt-1 text-xs text-error font-mono-data" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="headline" class="stitch-label">PROFESSIONAL HEADLINE / DESIGNATION</label>
            <input id="headline" name="headline" type="text" class="stitch-input" value="{{ old('headline', $user->headline) }}" placeholder="e.g., Systems Engineer & Temporal Architect" />
            <x-input-error class="mt-1 text-xs text-error font-mono-data" :messages="$errors->get('headline')" />
        </div>

        <div>
            <label for="bio" class="stitch-label">BIOGRAPHY / EXPERTISE SUMMARY</label>
            <textarea id="bio" name="bio" rows="3" class="stitch-textarea" placeholder="Detail your technical background and collaborative interests...">{{ old('bio', $user->bio) }}</textarea>
            <x-input-error class="mt-1 text-xs text-error font-mono-data" :messages="$errors->get('bio')" />
        </div>

        <div>
            <label for="email" class="stitch-label">SECURE COMM LINK *</label>
            <input id="email" name="email" type="email" class="stitch-input" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            <x-input-error class="mt-1 text-xs text-error font-mono-data" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="font-mono-data text-xs text-tertiary">
                        Your comm link is unverified.
                        <button form="send-verification" class="text-secondary hover:underline">
                            Click here to re-send verification link.
                        </button>
                    </p>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-stitch-primary text-xs py-2.5 px-6 shadow-[0_0_12px_rgba(93,230,255,0.3)]">
                SAVE CHANGES
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                   class="font-mono-data text-xs text-secondary flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">check</span> Profile Updated.
                </p>
            @endif
        </div>
    </form>
</section>
