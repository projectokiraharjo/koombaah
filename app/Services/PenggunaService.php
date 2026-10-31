<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Pengaturan › Akun Pengguna (PRD A.4, B.7.5, C.1) */
class PenggunaService
{
    /**
     * Buat / ubah akun. Akun tidak pernah dihapus — cukup dinonaktifkan (C.1 audit trail).
     *
     * @param  array{name: string, email: string, role: string|Role, is_active: bool, password?: ?string, employee_id?: mixed}  $data
     */
    public function simpan(?User $user, array $data, User $oleh): User
    {
        if (! $oleh->is_active || ! $oleh->isOwner()) {
            throw new DomainException('Hanya Owner yang dapat mengelola akun pengguna.');
        }

        return DB::transaction(function () use ($user, $data, $oleh) {
            $role = $data['role'] instanceof Role ? $data['role'] : Role::from($data['role']);
            $aktif = (bool) $data['is_active'];

            if ($user) {
                $user = User::lockForUpdate()->findOrFail($user->id);

                if ($user->is($oleh) && ($role !== Role::Owner || ! $aktif)) {
                    throw new DomainException('Anda tidak dapat menurunkan role atau menonaktifkan akun Anda sendiri.');
                }

                // Selalu sisakan minimal satu Owner aktif
                $kehilanganOwner = $user->isOwner() && $user->is_active && ($role !== Role::Owner || ! $aktif);
                if ($kehilanganOwner && User::where('role', Role::Owner->value)->where('is_active', true)->whereKeyNot($user->id)->doesntExist()) {
                    throw new DomainException('Sistem harus memiliki minimal satu Owner aktif.');
                }
            }

            $isi = [
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'role' => $role,
                'is_active' => $aktif,
            ];
            if (filled($data['password'] ?? null)) {
                $isi['password'] = $data['password']; // di-hash otomatis oleh cast 'hashed'
            }

            if ($user) {
                $user->update($isi);
            } else {
                $user = User::create($isi + ['email_verified_at' => now()]);
            }

            // B.7.5: hubungkan akun ke data karyawan (opsional, satu akun = satu karyawan)
            Employee::where('user_id', $user->id)->update(['user_id' => null]);
            if (filled($data['employee_id'] ?? null)) {
                $terhubung = Employee::whereKey($data['employee_id'])->whereNull('user_id')->update(['user_id' => $user->id]);
                if ($terhubung === 0) {
                    throw new DomainException('Karyawan tersebut sudah terhubung ke akun lain.');
                }
            }

            return $user;
        });
    }
}