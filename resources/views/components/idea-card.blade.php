@props(['idea'])

@php
    $targetHours = (float)($idea->target_hours ?? 0);
    $fundedHours = (float)($idea->collaborators ? $idea->collaborators->where('status', 'accepted')->sum('hours_pledged') : 0);
    $progressPercent = $targetHours > 0 ? min(100, round(($fundedHours / $targetHours) * 100)) : 0;
    $collaboratorsCount = $idea->collaborators ? $idea->collaborators->where('status', 'accepted')->count() : 0;
@endphp

<div class="glass-card p-6 rounded-xl flex flex-col justify-between group glow-hover relative overflow-hidden transition-all duration-300">
    <div>
        {{-- Category & Status --}}
        <div class="flex items-center justify-between mb-4">
            <x-badge :variant="'cyan'">
                <span class="material-symbols-outlined text-[13px] mr-1">eco</span>
                {{ $idea->category->name ?? 'Initiative' }}
            </x-badge>
            <span class="font-mono-data text-xs text-on-surface-variant/80">
                {{ number_format($targetHours, 0) }} Hrs Needed
            </span>
        </div>

        {{-- Title --}}
        <a href="{{ route('ideas.show', $idea->id) }}" class="block mb-2">
            <h3 class="font-headline-md text-base md:text-lg font-bold text-primary group-hover:text-secondary transition-colors line-clamp-1">
                {{ $idea->title }}
            </h3>
        </a>

        {{-- Mission / Description --}}
        <p class="font-body-md text-xs md:text-sm text-on-surface-variant line-clamp-2 mb-6">
            {{ $idea->mission_statement ?? $idea->description }}
        </p>

        {{-- Progress Bar (Hours funded vs needed) --}}
        <div class="mb-5">
            <div class="flex items-center justify-between font-mono-data text-xs mb-1.5">
                <span class="text-secondary font-semibold">{{ number_format($fundedHours, 0) }} <span class="text-on-surface-variant text-[11px] font-normal">hrs funded</span></span>
                <span class="text-on-surface-variant">{{ number_format($targetHours, 0) }} hrs target</span>
            </div>
            <div class="w-full h-1.5 bg-surface-container-high rounded-full overflow-hidden">
                <div class="h-full bg-secondary rounded-full shadow-[0_0_8px_rgba(93,230,255,0.6)] transition-all duration-500"
                     style="width: {{ $progressPercent }}%"></div>
            </div>
        </div>
    </div>

    {{-- Footer: Collaborator Stack & View Link --}}
    <div class="pt-4 border-t border-white/10 flex items-center justify-between mt-auto">
        <div class="flex items-center -space-x-2">
            <div class="w-7 h-7 rounded-full bg-surface-container border border-secondary/40 flex items-center justify-center font-mono-data text-[10px] text-secondary font-bold">
                {{ strtoupper(substr($idea->user->name ?? 'A', 0, 1)) }}
            </div>
            @if($collaboratorsCount > 0)
                <div class="w-7 h-7 rounded-full bg-primary-container border border-white/10 flex items-center justify-center font-mono-data text-[10px] text-on-surface-variant font-bold">
                    +{{ $collaboratorsCount }}
                </div>
            @endif
        </div>

        <a href="{{ route('ideas.show', $idea->id) }}"
           class="inline-flex items-center gap-1 font-label-caps text-xs text-secondary hover:text-white transition-colors group-hover:translate-x-1 duration-200">
            <span>View Details</span>
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </a>
    </div>
</div>
