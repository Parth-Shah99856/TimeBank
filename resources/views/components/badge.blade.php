@props(['variant' => 'neutral', 'size' => 'sm'])

@php
$variantClasses = [
    'cyan'        => 'stitch-badge-cyan',
    'gold'        => 'stitch-badge-gold',
    'green'       => 'stitch-badge-green',
    'red'         => 'stitch-badge-red',
    'neutral'     => 'stitch-badge-neutral',
    // Status mappings
    'pending'     => 'stitch-badge-gold',
    'accepted'    => 'stitch-badge-cyan',
    'in_progress' => 'stitch-badge-cyan',
    'completed'   => 'stitch-badge-green',
    'cancelled'   => 'stitch-badge-neutral',
    'disputed'    => 'stitch-badge-red',
    'open'        => 'stitch-badge-green',
    'recruiting'  => 'stitch-badge-cyan',
    'converted_to_project' => 'stitch-badge-cyan',
    'active'      => 'stitch-badge-green',
    'locked'      => 'stitch-badge-neutral',
];
$class = $variantClasses[$variant] ?? 'stitch-badge-neutral';
@endphp

<span {{ $attributes->merge(['class' => "stitch-badge $class"]) }}>
    {{ $slot }}
</span>
