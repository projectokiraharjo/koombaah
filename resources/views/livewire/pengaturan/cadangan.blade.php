<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Pengaturan</h1>
        <p class="text-sm text-muted-foreground">Cadangan database untuk pemulihan bila terjadi kerusakan data.</p>
    </div>

    @include('livewire.pengaturan._tab', ['aktif' => 'pengaturan.backup'])

    <x-ui.card title="Cadangan Database"
               description="Dibuat otomatis setiap hari pukul 01:00. Sistem menyimpan 14 cadangan terbaru, termasuk data periode keuangan yang sudah ditutup.">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button wire:click="buatSekarang" wire:loading.attr="disabled" wire:target="buatSekarang">
                <span wire:loading.remove wire:target="buatSekarang">Buat Cadangan Sekarang</span>
                <span wire:loading wire:target="buatSekarang">Membuat cadangan…</span>
            </x-ui.button>
            <span class="text-xs text-muted-foreground">Simpan juga salinannya di luar server (laptop / Google Drive).</span>
        </div>
        <x-ui.error for="backup" class="mt-3" />
    </x-ui.card>

    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">File</th>
                    <th class="px-4 py-3 font-medium">Dibuat</th>
                    <th class="px-4 py-3 text-right font-medium">Ukuran</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($file as $f)
                    <tr wire:key="bk-{{ $f['nama'] }}">
                        <td class="px-4 py-3 font-mono text-xs">{{ $f['nama'] }}</td>
                        <td class="px-4 py-3">{{ $f['waktu']->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ number_format($f['ukuran'] / 1024, 1, ',', '.') }} KB</td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.button variant="ghost" size="sm" href="{{ route('pengaturan.backup.unduh', $f['nama']) }}">Unduh</x-ui.button>
                            <x-ui.button variant="ghost" size="sm" wire:click="hapus('{{ $f['nama'] }}')"
                                         wire:confirm="Hapus cadangan {{ $f['nama'] }}?">Hapus</x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-muted-foreground">Belum ada cadangan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>