<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** PRD C.1 — backup PostgreSQL berkala, disimpan di storage/app/private/backups */
class BackupService
{
    private const FOLDER = 'backups';
    private const POLA = '/^koombaah-\d{4}-\d{2}-\d{2}_\d{6}\.sql\.gz$/';
    private const SIMPAN_TERAKHIR = 14; // rotasi: 14 cadangan terbaru

    public function buat(): string
    {
        $db = config('database.connections.' . config('database.default'));
        if (($db['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException('Backup hanya mendukung PostgreSQL.');
        }

        $disk = Storage::disk('local');
        $disk->makeDirectory(self::FOLDER);
        $nama = 'koombaah-' . now()->format('Y-m-d_His') . '.sql.gz';
        $lokasi = $disk->path(self::FOLDER . '/' . $nama);

        $hasil = Process::env(['PGPASSWORD' => (string) $db['password']])
            ->timeout(300)
            ->run([
                'pg_dump', '--host=' . $db['host'], '--port=' . $db['port'], '--username=' . $db['username'],
                '--no-owner', '--no-privileges', '--compress=6', '--file=' . $lokasi, $db['database'],
            ]);

        if ($hasil->failed()) {
            @unlink($lokasi);
            throw new RuntimeException('Backup gagal: ' . trim($hasil->errorOutput() ?: $hasil->output()));
        }

        $this->daftar()->slice(self::SIMPAN_TERAKHIR)->each(fn ($f) => $disk->delete(self::FOLDER . '/' . $f['nama']));

        return $nama;
    }

    /** @return Collection<int, array{nama: string, ukuran: int, waktu: Carbon}> terbaru di atas */
    public function daftar(): Collection
    {
        $disk = Storage::disk('local');

        return collect($disk->files(self::FOLDER))
            ->map(fn ($p) => basename($p))
            ->filter(fn ($n) => preg_match(self::POLA, $n))
            ->map(fn ($n) => [
                'nama' => $n,
                'ukuran' => $disk->size(self::FOLDER . '/' . $n),
                'waktu' => Carbon::createFromTimestamp($disk->lastModified(self::FOLDER . '/' . $n)),
            ])
            ->sortByDesc('nama')->values();
    }

    /** Path relatif yang aman — menolak nama file di luar pola (mencegah path traversal) */
    public function lokasi(string $nama): string
    {
        $path = self::FOLDER . '/' . $nama;
        abort_unless(preg_match(self::POLA, $nama) && Storage::disk('local')->exists($path), 404);

        return $path;
    }

    public function hapus(string $nama): void
    {
        Storage::disk('local')->delete($this->lokasi($nama));
    }
}