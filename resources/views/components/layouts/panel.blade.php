@props(['title' => null])

@php
    // [label, nama route, gate, (opsional) jenis badge stok menipis]
    $menu = [
        'Utama' => [
            ['Dashboard', 'dashboard', 'dashboard'],
        ],
        'Inventory' => [
            ['Bahan Baku', 'bahan-baku', 'inventory'],
            ['Produk Jadi', 'produk-jadi', 'inventory'],
            ['Resep (BOM)', 'bom', 'inventory'],
            ['Stok Bahan Baku', 'stok-bahan-baku', 'inventory', 'bahan_baku'],
            ['Stok Produk Jadi', 'stok-produk-jadi', 'inventory', 'produk_jadi'],
            ['Peringatan Stok', 'peringatan-stok', 'inventory', 'total'],
            ['Riwayat Stok', 'riwayat-stok', 'inventory'],
        ],
        'Purchasing' => [
            ['Supplier', 'supplier', 'purchasing'],
            ['Purchase Order', 'po', 'purchasing'],
            ['Harga Supplier', 'harga-supplier', 'purchasing'],
        ],
        'Sales' => [
            ['Mitra', 'mitra', 'sales'],
            ['Sales Order', 'order', 'sales'],
        ],
        'Operasional' => [
            ['Produksi', 'produksi', 'produksi'],
        ],
        'Distribusi' => [
            ['Jadwal Pengiriman', 'distribusi', 'distribusi'],
            ['Riwayat Pengiriman', 'riwayat-pengiriman', 'distribusi'],
        ],
        'Keuangan' => [
            ['Laba-Rugi', 'keuangan', 'keuangan'],
            ['Arus Kas', 'arus-kas', 'keuangan'],
            ['Biaya Operasional', 'biaya-operasional', 'keuangan'],
            ['HPP Produksi', 'hpp', 'keuangan'],
            ['Periode', 'periode', 'keuangan'],
        ],
        'SDM' => [
            ['Karyawan', 'karyawan', 'sdm'],
            ['Payroll', 'payroll', 'sdm'],
        ],
        'Sistem' => [
            ['Pengaturan', 'pengaturan', 'pengaturan'],
        ],
    ];
    $user = auth()->user();
@endphp

<x-layouts.app :title="$title">
    <div x-data="{ sidebar: false }" class="min-h-screen lg:flex">
        {{-- Latar gelap saat menu dibuka di HP --}}
        <div x-show="sidebar" x-cloak x-on:click="sidebar = false" class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>

        {{-- Sidebar --}}
        <aside x-bind:class="sidebar ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r bg-background transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
            <div class="flex h-14 items-center border-b px-5">
                <span class="font-semibold tracking-tight">Koombaah</span>
            </div>

            <nav class="flex-1 overflow-y-auto p-3">
                @foreach ($menu as $grup => $items)
                    @php $items = array_filter($items, fn ($i) => $user->can($i[2])); @endphp
                    @if (count($items))
                        <p class="px-2 pb-1 pt-4 text-xs font-medium text-muted-foreground">{{ $grup }}</p>
                        @foreach ($items as $i)
                            @php $aktif = request()->routeIs($i[1], $i[1] . '.*'); @endphp
                            <a href="{{ route($i[1]) }}" wire:navigate
                               @class([
                                   'flex h-9 items-center justify-between gap-2 rounded-md px-2 text-sm transition-colors',
                                   'bg-accent font-medium text-accent-foreground' => $aktif,
                                   'text-muted-foreground hover:bg-accent hover:text-accent-foreground' => ! $aktif,
                               ])>
                                <span>{{ $i[0] }}</span>
                                @isset($i[3])
                                    <livewire:inventory.low-stock-badge :jenis="$i[3]" :key="'badge-' . $i[3]" />
                                @endisset
                            </a>
                        @endforeach
                    @endif
                @endforeach
            </nav>

            <div class="border-t p-3">
                <a href="{{ route('profil') }}" wire:navigate class="mb-2 block rounded-md px-2 py-2 hover:bg-accent">
                    <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                    <p class="text-xs text-muted-foreground">{{ $user->role->label() }} · Profil & password</p>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-ui.button type="submit" variant="outline" size="sm" class="w-full">Keluar</x-ui.button>
                </form>
            </div>
        </aside>

        {{-- Konten --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b bg-background px-4 lg:hidden">
                <x-ui.button variant="ghost" size="icon" x-on:click="sidebar = true" aria-label="Buka menu">☰</x-ui.button>
                <span class="font-semibold">Koombaah</span>
                @can('inventory')
                    <a href="{{ route('peringatan-stok') }}" wire:navigate class="ml-auto flex items-center gap-1.5 text-sm text-muted-foreground">
                        Stok
                        <livewire:inventory.low-stock-badge jenis="total" key="badge-mobile" />
                    </a>
                @endcan
            </header>
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Toast notifikasi --}}
    <div x-data="{ show: false, message: '', timer: null }"
         x-init="@if (session('toast')) message = @js(session('toast')); show = true; timer = setTimeout(() => show = false, 3000) @endif"         x-on:toast.window="message = $event.detail.message; show = true; clearTimeout(timer); timer = setTimeout(() => show = false, 3000)"
         x-show="show" x-transition x-cloak
         class="fixed bottom-4 right-4 z-[60] rounded-lg border bg-background px-4 py-3 text-sm font-medium shadow-lg">
        <span x-text="message"></span>
    </div>
</x-layouts.app>