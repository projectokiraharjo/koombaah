@use('App\Enums\Role')

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Pengaturan</h1>
            <p class="text-sm text-muted-foreground">Akun pengguna sistem dan hak aksesnya.</p>
        </div>
        <x-ui.button wire:click="create">+ Tambah Akun</x-ui.button>
    </div>

    @include('livewire.pengaturan._tab', ['aktif' => 'pengaturan'])

    <x-ui.input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email…" class="sm:max-w-xs" />

    <div class="overflow-x-auto rounded-xl border bg-card">
        <table class="w-full text-sm">
            <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Karyawan Terhubung</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($items as $u)
                    <tr wire:key="user-{{ $u->id }}" class="hover:bg-muted/30">
                        <td class="px-4 py-3">
                            <div class="font-medium">
                                {{ $u->name }}
                                @if ($u->is(auth()->user()))
                                    <x-ui.badge variant="outline" class="ml-1">Anda</x-ui.badge>
                                @endif
                            </div>
                            <div class="text-xs text-muted-foreground">{{ $u->email }}</div>
                        </td>
                        <td class="px-4 py-3"><x-ui.badge variant="secondary">{{ $u->role->label() }}</x-ui.badge></td>
                        <td class="px-4 py-3">{{ $u->employee?->nama_lengkap ?? '–' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$u->is_active ? 'secondary' : 'outline'">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.button variant="ghost" size="sm" wire:click="edit({{ $u->id }})">Edit</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-ui.dialog :title="$editingId ? 'Edit Akun' : 'Tambah Akun'"
                 description="Akun tidak dihapus — nonaktifkan bila staf tidak lagi bekerja, agar riwayat transaksinya tetap utuh.">
        <form wire:submit="save" class="flex flex-col gap-4">
            <div class="grid gap-2">
                <x-ui.label for="name">Nama</x-ui.label>
                <x-ui.input id="name" wire:model="name" />
                <x-ui.error for="name" />
            </div>
            <div class="grid gap-2">
                <x-ui.label for="email">Email (untuk login)</x-ui.label>
                <x-ui.input id="email" type="email" wire:model="email" />
                <x-ui.error for="email" />
            </div>
            <div class="grid gap-2">
                <x-ui.label for="role">Role</x-ui.label>
                <x-ui.select id="role" wire:model.live="role">
                    @foreach (Role::cases() as $r)
                        <option value="{{ $r->value }}">{{ $r->label() }}</option>
                    @endforeach
                </x-ui.select>
                <p class="text-xs text-muted-foreground">
                    {{ match ($role) {
                        'owner' => 'Semua modul, termasuk data gaji, laba-rugi, dan pengaturan.',
                        'admin' => 'Inventory, Purchasing, Produksi, Sales, dan Distribusi.',
                        default => 'Modul Keuangan, tanpa rincian gaji karyawan.',
                    } }}
                </p>
                <x-ui.error for="role" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <x-ui.label for="password">Password</x-ui.label>
                    <x-ui.input id="password" type="password" wire:model="password" autocomplete="new-password"
                                :placeholder="$editingId ? 'Kosongkan jika tidak diganti' : 'Minimal 8 karakter'" />
                    <x-ui.error for="password" />
                </div>
                <div class="grid gap-2">
                    <x-ui.label for="password_confirmation">Ulangi Password</x-ui.label>
                    <x-ui.input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" />
                </div>
            </div>
            <div class="grid gap-2">
                <x-ui.label for="employee_id">Hubungkan ke Karyawan (opsional)</x-ui.label>
                <x-ui.select id="employee_id" wire:model="employee_id">
                    <option value="">— Tidak dihubungkan —</option>
                    @foreach ($karyawan as $k)
                        <option value="{{ $k->id }}">{{ $k->nama_lengkap }} · {{ $k->posisi }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.error for="employee_id" />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="is_active" class="size-4 rounded border-input">
                Akun aktif (dapat login)
            </label>

            <x-ui.error for="form" />

            <div class="mt-2 flex justify-end gap-2">
                <x-ui.button variant="outline" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">Simpan</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</div>