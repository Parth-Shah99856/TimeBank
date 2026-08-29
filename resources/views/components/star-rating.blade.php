@props(['rating' => 0, 'reviewsCount' => null, 'interactive' => false, 'name' => 'rating', 'size' => 'md'])

@php
$sizes = ['sm' => 'text-[14px]', 'md' => 'text-[18px]', 'lg' => 'text-[24px]'];
$iconSize = $sizes[$size] ?? $sizes['md'];
@endphp

@if($interactive)
<div x-data="{ rating: {{ (int)$rating }}, hover: 0 }" class="flex items-center gap-1">
    @for($i = 1; $i <= 5; $i++)
        <button type="button"
                @click="rating = {{ $i }}"
                @mouseenter="hover = {{ $i }}"
                @mouseleave="hover = 0"
                class="focus:outline-none transition-transform hover:scale-110 p-0.5">
            <span class="material-symbols-outlined {{ $iconSize }}"
                  :class="(hover || rating) >= {{ $i }} ? 'text-tertiary drop-shadow-[0_0_8px_rgba(249,189,34,0.5)] fill' : 'text-on-surface-variant/30'">star</span>
        </button>
    @endfor
    <input type="hidden" name="{{ $name }}" :value="rating">
</div>
@else
<div class="inline-flex items-center gap-1.5 font-label-caps text-tertiary">
    <span class="material-symbols-outlined {{ $iconSize }} fill text-tertiary drop-shadow-[0_0_6px_rgba(249,189,34,0.4)]">star</span>
    <span class="font-bold text-xs">{{ number_format($rating, 1) }}</span>
    @if($reviewsCount !== null)
        <span class="text-on-surface-variant/60 font-normal">({{ $reviewsCount }})</span>
    @endif
</div>
@endif
