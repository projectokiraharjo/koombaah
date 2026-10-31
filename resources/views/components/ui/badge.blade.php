@props(['variant' => 'default'])

@php
    $variants = [
        'default'     => 'border-transparent bg-primary text-primary-foreground',
        'secondary'   => 'border-transparent bg-secondary text-secondary-foreground',
        'destructive' => 'border-transparent bg-destructive text-white',
        'outline'     => 'text-foreground',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-medium whitespace-nowrap ' . $variants[$variant]]) }}>
    {{ $slot }}
</span>