@props(['value', 'label', 'icon' => null, 'trend' => null, 'trendUp' => true, 'unit' => ''])

<div class="glass-card p-6 rounded-xl relative overflow-hidden group glow-hover">
    {{-- Glow background orb --}}
    <div class="absolute -right-12 -top-12 w-32 h-32 bg-secondary/10 rounded-full blur-2xl group-hover:bg-secondary/20 transition-all duration-500"></div>

    <div class="flex items-center justify-between mb-3 relative z-10">
        <span class="font-label-caps text-on-surface-variant text-xs">{{ $label }}</span>
        @if($icon)
            <div class="w-8 h-8 rounded-lg bg-surface-container-high border border-white/5 flex items-center justify-center text-secondary">
                <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
            </div>
        @endif
    </div>

    <div class="flex items-baseline gap-2 relative z-10">
        <span class="font-display-lg text-3xl md:text-4xl font-bold text-secondary drop-shadow-[0_0_12px_rgba(93,230,255,0.4)]">
            {{ $value }}
        </span>
        @if($unit)
            <span class="font-body-lg text-sm text-on-surface-variant font-mono">{{ $unit }}</span>
        @endif
    </div>

    @if($trend)
        <div class="flex items-center gap-1.5 mt-2.5 font-mono-data text-xs {{ $trendUp ? 'text-tertiary' : 'text-error' }} relative z-10">
            <span class="material-symbols-outlined text-[14px]">{{ $trendUp ? 'trending_up' : 'trending_down' }}</span>
            <span>{{ $trend }}</span>
        </div>
    @endif
</div>
