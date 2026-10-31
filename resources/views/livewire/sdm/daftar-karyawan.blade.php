@use('App\Support\Format')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Karyawan</h1>
            <p class="text-sm text-muted-foreground">Data karyawan internal Koombaah. Halaman ini hanya dapat diakses Owner.</p>
        </div>
        <x-ui.button wire:click="create">+ Tambah Karyawan</x-ui.button>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row">
        <x-ui.input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama, kode, posisi…" class="sm:max-w-xs" />
        <x-ui.select wire:model.live="filterStatus" class="sm:w-44">
            <option value="">Semua status</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif (resign)</option>
        </x-ui.select>
    </div>

    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Posisi</th>
                    <th class="px-4 py-3 font-medium">Masa Kerja</th>
                    <th class="px-4 py-3 text-right font-medium">Gaji Pokok Kini</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($items as $e)
                    <tr wire:key="emp-{{ $e->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-mono text-xs">{{ $e->kode_karyawan }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $e->nama_lengkap }}</div>
                            @if ($e->user)
                                <div class="text-xs text-muted-foreground">Akun: {{ $e->user->name }} ({{ $e->user->role->label() }})</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $e->posisi }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">
                            {{ $e->tanggal_mulai_kerja->format('d/m/Y') }} → {{ $e->tanggal_berhenti?->format('d/m/Y') ?? 'sekarang' }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ $e->gaji_pokok_kini !== null ? Format::rupiah($e->gaji_pokok_kini) : '–' }}
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$e->status->value === 'aktif' ? 'secondary' : 'outline'">{{ $e->status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <x-ui.button variant="ghost" size="sm" href="{{ route('karyawan.detail', $e) }}" wire:navigate>Gaji & Riwayat</x-ui.button>
                                <x-ui.button variant="ghost" size="sm" wire:click="edit({{ $e->id }})">Edit</x-ui.button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-muted-foreground">Belum ada data karyawan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}

    <x-ui.dialog :title="$editingId ? 'Edit Karyawan' : 'Tambah Karyawan'">
        <form wire:submit="save" class="flex flex-col gap-4">
            <div class="grid gap-2">
                <x-ui.label>Kode</x-ui.label>
                <x-ui.input :value="$kode_karyawan" disabled />
            </div>
            <div class="grid gap-2">
                <x-ui.label for="nama_lengkap">Nama Lengkap</x-ui.label>
                <x-ui.input id="nama_lengkap" wire:model="nama_lengkap" />
                <x-ui.error for="nama_lengkap" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <x-ui.label for="posisi">Posisi</x-ui.label>
                    <x-ui.input id="posisi" wire:model="posisi" list="posisi-list" />
                    <datalist id="posisi-list">
                        @foreach ($posisiList as $p) <option value="{{ $p }}"></option> @endforeach
                    </datalist>
                    <x-ui.error for="posisi" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="no_telepon">No. Telepon</x-ui.label>
                    <x-ui.input id="no_telepon" wire:model="no_telepon" />
                    <x-ui.error for="no_telepon" />
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <x-ui.label for="tanggal_mulai_kerja">Mulai Kerja</x-ui.label>
                    <x-ui.input id="tanggal_mulai_kerja" type="date" wire:model="tanggal_mulai_kerja" />
                    <x-ui.error for="tanggal_mulai_kerja" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="status">Status</x-ui.label>
                    <x-ui.select id="status" wire:model.live="status">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif (resign)</option>
                    </x-ui.select>
                </div>
                @if ($status === 'nonaktif')
                    <div class="grid gap-2">
                        <x-ui.label for="tanggal_berhenti">Tanggal Berhenti</x-ui.label>
                        <x-ui.input id="tanggal_berhenti" type="date" wire:model="tanggal_berhenti" />
                        <x-ui.error for="tanggal_berhenti" />
                    </div>
                @endif
            </div>
            <div class="grid gap-2">
                <x-ui.label for="user_id">Akun Sistem (opsional)</x-ui.label>
                <x-ui.select id="user_id" wire:model="user_id">
                    <option value="">— Tidak punya akun —</option>
                    @foreach ($userList as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role->label() }})</option>
                    @endforeach
                </x-ui.select>
                <x-ui.error for="user_id" />
            </div>

            @unless ($editingId)
                <div class="grid gap-4 rounded-lg border p-3 sm:grid-cols-2">
                    <p class="text-xs text-muted-foreground sm:col-span-2">Komponen gaji awal (opsional — bisa juga ditambah nanti di halaman Gaji & Riwayat)</p>
                    <div class="grid gap-2">
                        <x-ui.label for="gaji_pokok_awal">Gaji Pokok / bulan</x-ui.label>
                        <x-ui.input id="gaji_pokok_awal" type="number" step="1000" min="0" wire:model="gaji_pokok_awal" />
                        <x-ui.error for="gaji_pokok_awal" />
                    </div>
                    <div class="grid gap-2">
                        <x-ui.label for="tunjangan_awal">Tunjangan Tetap / bulan</x-ui.label>
                        <x-ui.input id="tunjangan_awal" type="number" step="1000" min="0" wire:model="tunjangan_awal" />
                        <x-ui.error for="tunjangan_awal" />
                    </div>
                </div>
            @endunless

            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>