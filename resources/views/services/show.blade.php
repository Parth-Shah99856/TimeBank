@section('title', $service->title)

<x-app-layout>
    {{-- Back link --}}
    <div class="mb-6">
        <a href="{{ route('services.index') }}" class="inline-flex items-center gap-1.5 font-label-caps text-xs text-on-surface-variant hover:text-secondary transition-colors">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Back to Explore
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter md:gap-8">
        {{-- Left Column: Service Details & Overview --}}
        <div class="lg:col-span-8 space-y-6">
            {{-- Service Hero Header Card --}}
            <div class="glass-card p-6 md:p-8 rounded-2xl relative overflow-hidden group">
                <div class="absolute -right-20 -top-20 w-64 h-64 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex items-center justify-between mb-4">
                    <x-badge :variant="'cyan'">
                        {{ $service->category->name ?? 'System Architecture' }}
                    </x-badge>
                    @php
                        $providerAvg = $service->user->reviewsReceived()->avg('rating');
                        $reviewCount = $service->user->reviewsReceived()->count();
                    @endphp
                    @if($providerAvg && $reviewCount > 0)
                        <div class="flex items-center gap-1 text-tertiary">
                            <span class="material-symbols-outlined text-[16px] fill">star</span>
                            <span class="font-mono-data text-xs font-bold">{{ number_format($providerAvg, 1) }} ({{ $reviewCount }})</span>
                        </div>
                    @endif
                </div>

                <h1 class="font-headline-lg text-2xl md:text-3xl lg:text-4xl font-bold text-on-surface mb-3">
                    {{ $service->title }}
                </h1>

                <p class="font-body-lg text-sm md:text-base text-on-surface-variant leading-relaxed mb-6">
                    {{ $service->description }}
                </p>

                {{-- Skill Tags --}}
                @if(!empty($service->tags) && is_array($service->tags))
                    <div class="flex flex-wrap gap-2 pt-4 border-t border-white/10">
                        @foreach($service->tags as $tag)
                            <span class="px-3 py-1 rounded-md bg-surface-container-high border border-white/5 font-mono-data text-xs text-on-surface-variant">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Provider Card --}}
            <div class="glass-card p-6 rounded-2xl flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <x-avatar :user="$service->user" size="lg" />
                    <div>
                        <h3 class="font-headline-md text-base md:text-lg font-bold text-on-surface">
                            {{ $service->user->name }}
                        </h3>
                        <p class="font-mono-data text-xs text-on-surface-variant mt-0.5">
                            {{ $service->user->headline ?? 'Temporal Architect' }}
                        </p>
                        @php $providerExchanges = $service->user->providedServiceRequests()->where('status', 'completed')->count(); @endphp
                        @if($providerExchanges > 0)
                            <div class="flex items-center gap-2 mt-1 font-mono-data text-[11px] text-secondary">
                                <span class="material-symbols-outlined text-[13px]">verified</span>
                                <span>{{ $providerExchanges }} completed exchange{{ $providerExchanges !== 1 ? 's' : '' }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Reviews Section --}}
            @php
                $providerReviews = \App\Models\Review::query()
                    ->where('reviewee_id', $service->user_id)
                    ->with('reviewer')
                    ->latest()
                    ->take(3)
                    ->get();
            @endphp
            @if($providerReviews->count() > 0)
                <div class="glass-card p-6 md:p-8 rounded-2xl">
                    <h3 class="font-headline-md text-lg font-bold text-on-surface mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary text-[20px] fill">reviews</span>
                        Provider Reviews
                    </h3>
                    <div class="space-y-4">
                        @foreach($providerReviews as $review)
                            <div class="p-4 rounded-xl bg-surface-container-high/40 border border-white/5">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <x-avatar :user="$review->reviewer" size="sm" />
                                        <span class="font-headline text-xs font-semibold text-on-surface">{{ $review->reviewer->name ?? 'Anonymous' }}</span>
                                    </div>
                                    <div class="flex items-center gap-0.5 text-tertiary">
                                        @for($s = 1; $s <= 5; $s++)
                                            <span class="material-symbols-outlined text-[13px] {{ $s <= $review->rating ? 'fill' : '' }}">star</span>
                                        @endfor
                                    </div>
                                </div>
                                @if($review->comment)
                                    <p class="font-body-md text-xs text-on-surface-variant leading-relaxed">{{ $review->comment }}</p>
                                @endif
                                <span class="font-mono-data text-[10px] text-on-surface-variant/50 block mt-1">{{ $review->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Column: Time Required & Request Action --}}
        <div class="lg:col-span-4">
            <div class="glass-card p-6 md:p-8 rounded-2xl sticky top-24 space-y-6">
                {{-- Rate & Time Required --}}
                <div>
                    <span class="font-label-caps text-xs text-on-surface-variant block mb-2">Hourly Rate</span>
                    <div class="flex items-baseline gap-2 mb-6">
                        <span class="font-display-lg text-4xl font-bold text-secondary">
                            {{ number_format($service->hourly_rate, 2) }}
                        </span>
                        <span class="font-body-lg text-sm text-on-surface-variant font-mono">TC / Hour</span>
                    </div>
                </div>

                {{-- Exchange Breakdown Items --}}
                <div class="space-y-3 pt-4 border-t border-white/10 font-body-md text-xs text-on-surface-variant">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px] text-secondary">check_circle</span>
                        <span>Direct 1-on-1 Consultation Session</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px] text-secondary">check_circle</span>
                        <span>Architectural Blueprint Delivery</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px] text-secondary">check_circle</span>
                        <span>Protected Temporal Escrow Protocol</span>
                    </div>
                </div>

                {{-- Request Button --}}
                <div class="pt-4 border-t border-white/10">
                    @auth
                        @if(Auth::id() !== $service->user_id)
                            <a href="{{ route('service-requests.create', ['service_id' => $service->id]) }}"
                               class="btn-stitch-primary w-full shadow-[0_0_20px_rgba(93,230,255,0.35)] py-3.5 text-xs">
                                REQUEST SERVICE <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                            <p class="font-mono-data text-[10px] text-on-surface-variant/70 text-center mt-3">
                                Requires Escrow Approval &bull; Zero Intermediary Fees
                            </p>
                        @else
                            <div class="space-y-3">
                                <div class="p-3 rounded-lg bg-secondary/10 border border-secondary/30 text-center font-mono-data text-xs text-secondary">
                                    You are the architect of this skill.
                                </div>
                                <div class="flex gap-2">
                                    <a href="{{ route('my-services') }}" class="btn-stitch-secondary w-full text-xs justify-center py-2.5">
                                        <span class="material-symbols-outlined text-[16px]">tune</span> Manage My Skills
                                    </a>
                                </div>
                            </div>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn-stitch-primary w-full text-xs py-3.5">
                            SIGN IN TO REQUEST
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
