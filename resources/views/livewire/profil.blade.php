<div class="flex max-w-2xl flex-col gap-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Profil Saya</h1>
        <p class="text-sm text-muted-foreground">{{ $user->email }} · {{ $user->role->label() }}</p>
    </div>

    <x-ui.card title="Data Profil" description="Email dan role hanya dapat diubah oleh Owner melalui menu Pengaturan.">
        <form wire:submit="simpanProfil" class="flex flex-col gap-4">
            <div class="grid gap-2">
                <x-ui.label for="name">Nama</x-ui.label>
                <x-ui.input id="name" wire:model="name" />
                <x-ui.error for="name" />
            </div>
            <div><x-ui.button type="submit">Simpan</x-ui.button></div>
        </form>
    </x-ui.card>

    <x-ui.card title="Ganti Password">
        <form wire:submit="gantiPassword" class="flex flex-col gap-4">
            <div class="grid gap-2">
                <x-ui.label for="current_password">Password Lama</x-ui.label>
                <x-ui.input id="current_password" type="password" wire:model="current_password" autocomplete="current-password" />
                <x-ui.error for="current_password" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <x-ui.label for="password">Password Baru</x-ui.label>
                    <x-ui.input id="password" type="password" wire:model="password" autocomplete="new-password" />
                    <x-ui.error for="password" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="password_confirmation">Ulangi Password Baru</x-ui.label>
                    <x-ui.input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" />
                </div>
            </div>
            <div><x-ui.button type="submit">Ganti Password</x-ui.button></div>
        </form>
    </x-ui.card>
</div>