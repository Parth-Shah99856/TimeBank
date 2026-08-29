@props(['amount', 'size' => 'md', 'showIcon' => true, 'showUnit' => true])

@php
$sizes = [
    'xs' => 'text-xs',
    'sm' => 'text-sm',
    'md' => 'text-base font-semibold',
    'lg' => 'text-xl font-bold',
    'xl' => 'text-3xl md:text-4xl font-bold font-display-lg',
];
$sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-baseline gap-1 font-mono-data text-secondary $sizeClass drop-shadow-[0_0_8px_rgba(93,230,255,0.3)]"]) }}>
    @if($showIcon)
        <span class="material-symbols-outlined text-[16px] self-center" style="font-variation-settings: 'FILL' 1;">schedule</span>
    @endif
    <span>{{ number_format((float)$amount, 2) }}</span>
    @if($showUnit)
        <span class="text-xs font-label-caps text-on-surface-variant font-normal opacity-80">TC</span>
    @endif
</span>
