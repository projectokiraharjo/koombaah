@props(['variant' => 'default', 'size' => 'default', 'href' => null, 'type' => 'button'])

@php
    $base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all cursor-pointer disabled:pointer-events-none disabled:opacity-50 [&_svg]:size-4 [&_svg]:shrink-0 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50';

    $variants = [
        'default'     => 'bg-primary text-primary-foreground shadow-xs hover:bg-primary/90',
        'destructive' => 'bg-destructive text-white shadow-xs hover:bg-destructive/90',
        'outline'     => 'border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input',
        'secondary'   => 'bg-secondary text-secondary-foreground shadow-xs hover:bg-secondary/80',
        'ghost'       => 'hover:bg-accent hover:text-accent-foreground',
        'link'        => 'text-primary underline-offset-4 hover:underline',
    ];

    $sizes = [
        'default' => 'h-9 px-4 py-2',
        'sm'      => 'h-8 gap-1.5 px-3',
        'lg'      => 'h-10 px-6',
        'icon'    => 'size-9',
    ];

    $classes = "$base {$variants[$variant]} {$sizes[$size]}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif