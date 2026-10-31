<x-layouts.app title="Akses ditolak · Koombaah">
    <div class="flex min-h-screen items-center justify-center px-4">
        <x-ui.card class="w-full max-w-sm text-center" title="Akses ditolak"
            description="Role akunmu tidak memiliki izin untuk membuka halaman ini.">
            <x-slot:footer>
                <x-ui.button href="{{ url('/dashboard') }}" class="w-full">Kembali ke Dashboard</x-ui.button>
            </x-slot:footer>
        </x-ui.card>
    </div>
</x-layouts.app>