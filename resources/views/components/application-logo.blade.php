@props(['class' => ''])

<a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2.5 group {{ $class }}">
    <div class="w-8 h-8 rounded-lg bg-secondary/15 border border-secondary/40 flex items-center justify-center text-secondary group-hover:bg-secondary/25 group-hover:shadow-[0_0_12px_rgba(93,230,255,0.4)] transition-all duration-300">
        <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">schedule</span>
    </div>
    <span class="font-headline text-lg md:text-xl font-bold tracking-tighter text-primary group-hover:text-white transition-colors">
        TIMEBANK
    </span>
</a>
