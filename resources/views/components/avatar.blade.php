@props(['user' => null, 'name' => '', 'size' => 'md', 'src' => null, 'bordered' => true])

@php
$displayName = $user ? $user->name : $name;
$avatarUrl = $src ?? ($user ? $user->avatar_url : null);
$initial = strtoupper(substr($displayName ?: 'U', 0, 1));
$sizes = [
    'xs' => 'w-6 h-6 text-[10px]',
    'sm' => 'w-8 h-8 text-xs',
    'md' => 'w-10 h-10 text-sm',
    'lg' => 'w-14 h-14 text-lg',
    'xl' => 'w-20 h-20 text-2xl',
];
$sizeClass = $sizes[$size] ?? $sizes['md'];
$borderClass = $bordered ? 'border border-secondary/40 shadow-[0_0_8px_rgba(93,230,255,0.2)]' : '';
@endphp

@if($avatarUrl)
    <img src="{{ $avatarUrl }}" alt="{{ $displayName }}" {{ $attributes->merge(['class' => "rounded-full object-cover flex-shrink-0 $sizeClass $borderClass"]) }}>
@else
    <div {{ $attributes->merge(['class' => "rounded-full bg-primary-container text-secondary flex items-center justify-center font-bold font-headline flex-shrink-0 $sizeClass $borderClass"]) }}>
        {{ $initial }}
    </div>
@endif
