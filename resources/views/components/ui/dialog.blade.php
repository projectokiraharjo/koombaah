@props(['model' => 'showForm', 'title' => null, 'description' => null])

<div x-data="{ open: $wire.entangle('{{ $model }}') }" x-show="open" x-cloak
     x-on:keydown.escape.window="open = false"
     class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center">
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/50" x-on:click="open = false"></div>

    <div x-show="open" x-transition
         class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border bg-background p-6 shadow-lg">
        @if ($title)
            <h2 class="text-lg font-semibold leading-none">{{ $title }}</h2>
        @endif
        @if ($description)
            <p class="mt-2 text-sm text-muted-foreground">{{ $description }}</p>
        @endif
        <div class="mt-5">{{ $slot }}</div>
    </div>
</div>