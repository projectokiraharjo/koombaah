@props(['title' => null, 'description' => null, 'footer' => null])

<div {{ $attributes->merge(['class' => 'bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm']) }}>
    @if ($title || $description)
        <div class="grid gap-1.5 px-6">
            @if ($title)<h3 class="leading-none font-semibold">{{ $title }}</h3>@endif
            @if ($description)<p class="text-muted-foreground text-sm">{{ $description }}</p>@endif
        </div>
    @endif

    <div class="px-6">{{ $slot }}</div>

    @if ($footer)
        <div class="flex items-center gap-2 px-6">{{ $footer }}</div>
    @endif
</div>