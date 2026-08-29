@props(['hover' => true, 'class' => ''])

<div {{ $attributes->merge(['class' => 'glass-card p-6 rounded-xl ' . ($hover ? 'glow-hover ' : '') . $class]) }}>
    {{ $slot }}
</div>
