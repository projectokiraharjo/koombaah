<main class="mx-auto flex max-w-xl flex-col gap-6 px-4 py-12">
    <h1 class="text-2xl font-semibold tracking-tight">Cek Setup Koombaah</h1>

    <x-ui.card title="Database" description="Koneksi ke PostgreSQL">
        <div class="flex flex-col gap-2 text-sm">
            <div>Driver: <x-ui.badge>{{ $driver }}</x-ui.badge></div>
            <p class="text-muted-foreground">{{ $dbVersion }}</p>
        </div>
    </x-ui.card>

    <x-ui.card title="Livewire" description="Klik tombol, angka harus bertambah tanpa reload halaman.">
        <p class="text-3xl font-semibold tabular-nums">{{ $count }}</p>
        <x-slot:footer>
            <x-ui.button wire:click="increment">Tambah</x-ui.button>
            <x-ui.button variant="outline" wire:click="$set('count', 0)">Reset</x-ui.button>
        </x-slot:footer>
    </x-ui.card>

    <x-ui.card title="Komponen UI">
        <div class="flex flex-col gap-3">
            <x-ui.label for="contoh">Nama bahan baku</x-ui.label>
            <x-ui.input id="contoh" placeholder="mis. SLES" />
            <div class="flex flex-wrap gap-2">
                <x-ui.badge variant="secondary">Ring 1</x-ui.badge>
                <x-ui.badge variant="destructive">Stok menipis</x-ui.badge>
                <x-ui.badge variant="outline">Draft</x-ui.badge>
            </div>
        </div>
    </x-ui.card>
</main>