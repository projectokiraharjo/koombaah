<div class="flex min-h-screen items-center justify-center bg-muted/40 px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-semibold tracking-tight">Koombaah</h1>
            <p class="text-sm text-muted-foreground">Sistem Internal CV Khia Nawasena</p>
        </div>

        <x-ui.card title="Masuk" description="Masukkan email dan password akunmu.">
            <form wire:submit="login" class="flex flex-col gap-4">
                <div class="grid gap-2">
                    <x-ui.label for="email">Email</x-ui.label>
                    <x-ui.input id="email" type="email" wire:model="email"
                        autocomplete="username" autofocus
                        :aria-invalid="$errors->has('email') ? 'true' : null" />
                    @error('email') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-2">
                    <x-ui.label for="password">Password</x-ui.label>
                    <x-ui.input id="password" type="password" wire:model="password"
                        autocomplete="current-password"
                        :aria-invalid="$errors->has('password') ? 'true' : null" />
                    @error('password') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="remember" class="size-4 rounded border-input accent-primary">
                    Ingat saya
                </label>

                <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="login">
                    <span wire:loading.remove wire:target="login">Masuk</span>
                    <span wire:loading wire:target="login">Memproses…</span>
                </x-ui.button>
            </form>
        </x-ui.card>
    </div>
</div>