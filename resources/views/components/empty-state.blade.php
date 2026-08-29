@props(['title' => 'No Data Found', 'description' => '', 'icon' => 'inbox', 'actionUrl' => null, 'actionLabel' => null])

<div class="glass-card p-10 md:p-14 text-center rounded-xl flex flex-col items-center justify-center my-6">
    <div class="w-16 h-16 rounded-2xl bg-surface-container-high border border-white/5 flex items-center justify-center text-secondary mb-4 shadow-[0_0_16px_rgba(93,230,255,0.15)]">
        <span class="material-symbols-outlined text-[32px]">{{ $icon }}</span>
    </div>
    <h3 class="font-headline-md text-primary text-lg font-semibold mb-1.5">{{ $title }}</h3>
    @if($description)
        <p class="font-body-md text-sm text-on-surface-variant max-w-md mx-auto mb-6">{{ $description }}</p>
    @endif
    @if($actionUrl)
        <a href="{{ $actionUrl }}" class="btn-stitch-primary text-xs">
            <span class="material-symbols-outlined text-[16px]">add</span>
            {{ $actionLabel ?? 'Get Started' }}
        </a>
    @endif
</div>
