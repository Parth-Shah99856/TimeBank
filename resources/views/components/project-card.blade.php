@props(['project'])

@php
    $targetHours = (float)($project->target_hours ?? 0);
    $contributed = (float)($project->members ? $project->members->sum('hours_contributed') : ($project->hours_contributed ?? 0));
    $progress = $targetHours > 0 ? min(100, round(($contributed / $targetHours) * 100)) : 0;
    $membersCount = $project->members_count ?? ($project->members ? $project->members->count() : 1);
@endphp

<div class="glass-card p-5 rounded-xl group glow-hover flex flex-col justify-between transition-all duration-300 relative overflow-hidden">
    <div>
        {{-- Status & ID --}}
        <div class="flex items-center justify-between mb-3">
            <x-badge :variant="$project->status ?? 'active'">
                {{ ucfirst(str_replace('_', ' ', $project->status ?? 'active')) }}
            </x-badge>
            <span class="font-mono-data text-[11px] text-on-surface-variant">
                PRJ-{{ str_pad($project->id ?? 1, 3, '0', STR_PAD_LEFT) }}
            </span>
        </div>

        {{-- Title --}}
        <a href="{{ route('projects.show', $project->id) }}" class="block mb-1.5">
            <h4 class="font-headline-md text-base font-bold text-primary group-hover:text-secondary transition-colors line-clamp-1">
                {{ $project->title }}
            </h4>
        </a>

        {{-- Description --}}
        @if($project->description)
            <p class="font-body-md text-xs text-on-surface-variant line-clamp-2 mb-4">
                {{ $project->description }}
            </p>
        @endif

        {{-- Progress Bar --}}
        <div class="mb-4">
            <div class="flex items-center justify-between font-mono-data text-xs mb-1">
                <span class="text-on-surface-variant">Completion</span>
                <span class="text-secondary font-bold">{{ $progress }}%</span>
            </div>
            <div class="w-full h-1.5 bg-surface-container-high rounded-full overflow-hidden">
                <div class="h-full bg-secondary rounded-full shadow-[0_0_8px_rgba(93,230,255,0.6)]" style="width: {{ $progress }}%"></div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="pt-3 border-t border-white/10 flex items-center justify-between text-xs text-on-surface-variant font-mono-data">
        <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px] text-secondary">group</span>
            <span>{{ $membersCount }} Nodes</span>
        </div>
        <div class="flex items-center gap-1">
            <span class="text-secondary font-bold">{{ number_format($contributed, 0) }}</span>
            <span>/ {{ number_format($targetHours, 0) }} hrs</span>
        </div>
    </div>
</div>
