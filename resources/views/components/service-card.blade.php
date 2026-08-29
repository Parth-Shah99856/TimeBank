@props(['service'])

<div class="glass-card p-6 rounded-xl flex flex-col justify-between group glow-hover relative overflow-hidden transition-all duration-300">
    {{-- Decorative Glow on Hover --}}
    <div class="absolute -right-16 -top-16 w-36 h-36 bg-secondary/5 rounded-full blur-3xl group-hover:bg-secondary/15 transition-all duration-500"></div>

    <div>
        {{-- Header: Provider & Badges --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <x-avatar :user="$service->user" size="md" />
                <div>
                    <h4 class="font-headline text-sm font-semibold text-on-surface group-hover:text-secondary transition-colors">{{ $service->user->name ?? 'Architect' }}</h4>
                    @php
                        $providerRating = $service->user ? (float)$service->user->reviewsReceived()->avg('rating') : 0;
                    @endphp
                    @if($providerRating > 0)
                        <div class="flex items-center gap-1 mt-0.5">
                            <span class="material-symbols-outlined text-[13px] text-tertiary fill">star</span>
                            <span class="font-mono-data text-xs text-tertiary font-semibold">{{ number_format($providerRating, 1) }}</span>
                        </div>
                    @else
                        <span class="font-mono-data text-[10px] text-on-surface-variant/60 block mt-0.5">New Architect</span>
                    @endif
                </div>
            </div>

            <x-badge :variant="'cyan'">
                {{ $service->category->name ?? 'Skill' }}
            </x-badge>
        </div>

        {{-- Service Title --}}
        <a href="{{ route('services.show', $service->id) }}" class="block mb-2">
            <h3 class="font-headline-md text-base md:text-lg font-semibold text-primary group-hover:text-secondary transition-colors line-clamp-1">
                {{ $service->title }}
            </h3>
        </a>

        {{-- Description --}}
        <p class="font-body-md text-xs md:text-sm text-on-surface-variant line-clamp-2 mb-4">
            {{ $service->description }}
        </p>

        {{-- Tags --}}
        @if(!empty($service->tags) && is_array($service->tags))
            <div class="flex flex-wrap gap-1.5 mb-5">
                @foreach(array_slice($service->tags, 0, 3) as $tag)
                    <span class="px-2.5 py-0.5 rounded-md bg-surface-container-high border border-white/5 font-mono-data text-[11px] text-on-surface-variant">
                        {{ $tag }}
                    </span>
                @endforeach
                @if(count($service->tags) > 3)
                    <span class="px-2 py-0.5 rounded-md bg-surface-container-high font-mono-data text-[10px] text-on-surface-variant/60">
                        +{{ count($service->tags) - 3 }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- Footer: Rate & Action --}}
    <div class="pt-4 border-t border-white/10 flex items-center justify-between mt-auto">
        <div>
            <span class="block font-label-caps text-[10px] text-on-surface-variant/70">Rate</span>
            <div class="flex items-baseline gap-1">
                <span class="font-display-lg text-lg font-bold text-secondary">{{ number_format($service->hourly_rate, 2) }}</span>
                <span class="font-mono-data text-[11px] text-on-surface-variant">TC/hr</span>
            </div>
        </div>

        <a href="{{ route('services.show', $service->id) }}"
           class="btn-stitch-secondary text-xs py-2 px-4">
            Book Time
        </a>
    </div>
</div>
