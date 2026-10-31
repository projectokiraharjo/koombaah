@use('App\Support\Format')

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Payroll</h1>
        <p class="text-sm text-muted-foreground">Proses penggajian bulanan. Setelah final, hanya total agregat yang dikirim ke Keuangan sebagai Beban Gaji.</p>
    </div>

    <x-ui.card title="Proses Payroll" description="Pilih bulan. Sistem otomatis memasukkan karyawan aktif beserta gaji pokok & tunjangan yang berlaku.">
        <form wire:submit="buat" class="flex flex-col gap-2 sm:flex-row sm:items-start">
            <div class="grid gap-1">
                <x-ui.input type="month" wire:model="bulan" class="sm:w-48" />
                <x-ui.error for="bulan" />
            </div>
            <x-ui.button type="submit" wire:loading.attr="disabled">Buat / Buka Draft</x-ui.button>
        </form>
    </x-ui.card>

    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Periode</th>
                    <th class="px-4 py-3 text-right font-medium">Karyawan</th>
                    <th class="px-4 py-3 text-right font-medium">Total Gaji Agregat</th>
                    <th class="px-4 py-3 font-medium">Diproses</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($daftar as $r)
                    <tr wire:key="pr-{{ $r->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3">
                            <a href="{{ route('payroll.detail', $r) }}" wire:navigate class="font-medium hover:underline">{{ $r->periode_bulan->translatedFormat('F Y') }}</a>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $r->details_count }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Format::rupiah($r->total_gaji_agregat) }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ $r->diproses_pada->format('d/m/Y H:i') }} · {{ $r->pemroses->name }}</td>
                        <td class="px-4 py-3"><x-ui.badge :variant="$r->status->badge()">{{ $r->status->label() }}</x-ui.badge></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-muted-foreground">Belum ada payroll.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>