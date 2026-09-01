@section('title', $user->name . ' — Profile')
@section('meta_description', ($user->headline ?? 'TimeBank community member') . ' — View ' . $user->name . '\'s skills, reviews, and initiatives on TimeBank.')

<x-app-layout>
    {{-- Back Link --}}
    <div class="mb-6">
        <a href="{{ url()->previous(route('services.index')) }}"
           class="inline-flex items-center gap-1.5 font-label-caps text-xs text-on-surface-variant hover:text-secondary transition-colors">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back
        </a>
    </div>

    @php
        $completedCount  = $user->completed_exchanges_count ?? 0;
        $reviewCount     = $user->reviews_received_count ?? $user->reviewsReceived->count();
        $avgRating       = $avgRating ? (float) $avgRating : null;
        $repScore        = $avgRating ? round($avgRating * 20, 1) : null;

        $archLevel = match(true) {
            $completedCount >= 20 => ['label' => 'Architect Level 5', 'color' => 'text-tertiary', 'pct' => 96],
            $completedCount >= 10 => ['label' => 'Architect Level 4', 'color' => 'text-tertiary', 'pct' => 80],
            $completedCount >= 5  => ['label' => 'Architect Level 3', 'color' => 'text-secondary', 'pct' => 65],
            $completedCount >= 2  => ['label' => 'Architect Level 2', 'color' => 'text-secondary', 'pct' => 45],
            default               => ['label' => 'Architect Level 1', 'color' => 'text-on-surface-variant', 'pct' => 20],
        };
        $offset = round(276 * (1 - $archLevel['pct'] / 100));
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter md:gap-8">

        {{-- ====== LEFT: Identity + Stats ====== --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Profile Identity Card --}}
            <div class="glass-card p-6 md:p-8 rounded-2xl relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-48 h-48 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>

                {{-- Avatar --}}
                <div class="flex flex-col items-center text-center relative z-10">
                    <x-avatar :user="$user" size="xl" class="mb-4" />

                    <h1 class="font-headline-lg text-xl md:text-2xl font-bold text-on-surface mb-1">
                        {{ $user->name }}
                    </h1>

                    @if($user->headline)
                        <p class="font-mono-data text-xs text-secondary mb-3">
                            {{ $user->headline }}
                        </p>
                    @endif

                    {{-- Rep Score badge --}}
                    @if($repScore)
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-tertiary/10 border border-tertiary/30 font-mono-data text-[11px] text-tertiary mb-4">
                            <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">star</span>
                            {{ number_format($avgRating, 1) }} · {{ $repScore }} Rep Score
                        </div>
                    @else
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 font-mono-data text-[11px] text-on-surface-variant mb-4">
                            New to the network
                        </div>
                    @endif

                    @if($user->bio)
                        <p class="font-body-md text-sm text-on-surface-variant leading-relaxed mt-1">
                            {{ $user->bio }}
                        </p>
                    @endif

                    {{-- Own profile edit shortcut (only visible to the user themselves) --}}
                    @auth
                        @if(Auth::id() === $user->id)
                            <a href="{{ route('profile.edit') }}"
                               class="btn-stitch-ghost text-xs py-2 px-5 mt-4 w-full justify-center">
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                Edit My Profile
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            {{-- Architect Rank Card --}}
            <div class="glass-card p-6 rounded-2xl flex flex-col items-center text-center relative overflow-hidden">
                <div class="relative w-24 h-24 mb-4">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="44" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="5"></circle>
                        <circle cx="50" cy="50" r="44" fill="none" stroke="#f9bd22" stroke-width="5"
                                stroke-dasharray="276" stroke-dashoffset="{{ $offset }}"
                                stroke-linecap="round"
                                class="drop-shadow-[0_0_10px_rgba(249,189,34,0.6)]"></circle>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[32px] text-tertiary drop-shadow-[0_0_12px_rgba(249,189,34,0.6)]"
                              style="font-variation-settings: 'FILL' 1;">military_tech</span>
                    </div>
                </div>
                <h3 class="font-headline-md text-base font-bold {{ $archLevel['color'] }} mb-1">
                    {{ $archLevel['label'] }}
                </h3>
                <p class="font-body-md text-xs text-on-surface-variant">
                    {{ $completedCount }} completed exchange{{ $completedCount !== 1 ? 's' : '' }}
                </p>
            </div>

            {{-- Stats Row --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="glass-card p-5 rounded-xl text-center">
                    <span class="font-display-lg text-2xl font-bold text-secondary drop-shadow-[0_0_10px_rgba(93,230,255,0.4)] block">
                        {{ $completedCount }}
                    </span>
                    <span class="font-label-caps text-[10px] text-on-surface-variant mt-1 block">Exchanges</span>
                </div>
                <div class="glass-card p-5 rounded-xl text-center">
                    <span class="font-display-lg text-2xl font-bold text-tertiary drop-shadow-[0_0_10px_rgba(249,189,34,0.4)] block">
                        {{ $reviewCount }}
                    </span>
                    <span class="font-label-caps text-[10px] text-on-surface-variant mt-1 block">Reviews</span>
                </div>
            </div>

            {{-- CTA: Request a service (if viewer is not the profile owner) --}}
            @auth
                @if(Auth::id() !== $user->id && $user->services->isNotEmpty())
                    <a href="{{ route('services.index') }}"
                       class="btn-stitch-primary w-full text-xs py-3 shadow-[0_0_16px_rgba(93,230,255,0.25)]">
                        <span class="material-symbols-outlined text-[16px]">sync_alt</span>
                        Book a Service
                    </a>
                @endif
            @else
                <a href="{{ route('login') }}" class="btn-stitch-primary w-full text-xs py-3">
                    <span class="material-symbols-outlined text-[16px]">login</span>
                    Sign In to Book
                </a>
            @endauth
        </div>

        {{-- ====== RIGHT: Services + Reviews + Ideas ====== --}}
        <div class="lg:col-span-8 space-y-8">

            {{-- ---- Active Services ---- --}}
            <section>
                <h2 class="font-headline-md text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary text-[20px]">hub</span>
                    Active Skills &amp; Services
                    <span class="font-mono-data text-xs text-on-surface-variant font-normal">({{ $user->services->count() }})</span>
                </h2>

                @if($user->services->isEmpty())
                    <x-empty-state
                        icon="hub"
                        title="No Active Services"
                        description="{{ $user->name }} has not listed any active services yet." />
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        @foreach($user->services as $service)
                            <a href="{{ route('services.show', $service->id) }}"
                               class="glass-card p-5 rounded-xl flex flex-col justify-between group glow-hover relative overflow-hidden transition-all duration-300">
                                <div class="absolute -right-10 -top-10 w-28 h-28 bg-secondary/5 rounded-full blur-2xl group-hover:bg-secondary/15 transition-all duration-500"></div>

                                <div>
                                    <div class="flex items-start justify-between mb-2">
                                        <x-badge :variant="'cyan'">
                                            {{ $service->category->name ?? 'Skill' }}
                                        </x-badge>
                                        <span class="font-display-lg text-lg font-bold text-secondary leading-none">
                                            {{ number_format($service->hourly_rate, 2) }}
                                            <span class="font-mono-data text-[10px] text-on-surface-variant font-normal">TC/hr</span>
                                        </span>
                                    </div>

                                    <h3 class="font-headline-md text-sm font-semibold text-primary group-hover:text-secondary transition-colors line-clamp-2 mt-3 mb-1">
                                        {{ $service->title }}
                                    </h3>
                                </div>

                                <div class="flex items-center justify-end mt-4 pt-3 border-t border-white/10">
                                    <span class="inline-flex items-center gap-1 font-label-caps text-xs text-secondary group-hover:translate-x-1 transition-transform duration-200">
                                        Book Time <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- ---- Reviews Received ---- --}}
            <section>
                <h2 class="font-headline-md text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-tertiary text-[20px]" style="font-variation-settings: 'FILL' 1;">star</span>
                    Community Reviews
                    @if($avgRating)
                        <span class="font-mono-data text-xs text-tertiary font-normal">{{ number_format($avgRating, 1) }} avg</span>
                    @endif
                </h2>

                @if($user->reviewsReceived->isEmpty())
                    <x-empty-state
                        icon="reviews"
                        title="No Reviews Yet"
                        description="{{ $user->name }} has not received any reviews yet. Complete an exchange to be the first!" />
                @else
                    <div class="space-y-4">
                        @foreach($user->reviewsReceived as $review)
                            <div class="glass-card p-5 rounded-xl">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :user="$review->reviewer" size="sm" />
                                        <span class="font-headline text-sm font-semibold text-on-surface">
                                            {{ $review->reviewer->name ?? 'Anonymous' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-0.5 text-tertiary">
                                        @for($s = 1; $s <= 5; $s++)
                                            <span class="material-symbols-outlined text-[14px] {{ $s <= $review->rating ? 'fill' : '' }}"
                                                  style="{{ $s <= $review->rating ? "font-variation-settings: 'FILL' 1;" : '' }}">star</span>
                                        @endfor
                                    </div>
                                </div>
                                @if($review->comment)
                                    <p class="font-body-md text-sm text-on-surface-variant leading-relaxed">
                                        "{{ $review->comment }}"
                                    </p>
                                @endif
                                <span class="font-mono-data text-[10px] text-on-surface-variant/50 block mt-2">
                                    {{ $review->created_at->diffForHumans() }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- ---- Open / Recruiting Ideas ---- --}}
            @if($user->ideas->isNotEmpty())
                <section>
                    <h2 class="font-headline-md text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]" style="color: #a78bfa;">lightbulb</span>
                        Open Initiatives
                        <span class="font-mono-data text-xs text-on-surface-variant font-normal">({{ $user->ideas->count() }})</span>
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        @foreach($user->ideas as $idea)
                            <x-idea-card :idea="$idea" />
                        @endforeach
                    </div>
                </section>
            @endif

        </div>
    </div>
</x-app-layout>
