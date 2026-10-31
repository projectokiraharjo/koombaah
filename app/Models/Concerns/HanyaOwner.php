<?php

namespace App\Models\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

/**
 * PRD C.1 & B.7.7 — data gaji hanya untuk Owner, ditegakkan di LEVEL QUERY:
 *  - SELECT oleh non-Owner selalu kosong (global scope)
 *  - INSERT/UPDATE/DELETE oleh non-Owner ditolak (403)
 * Seeder/artisan (konsol tanpa login) tetap diizinkan.
 */
trait HanyaOwner
{
    protected static function bootHanyaOwner(): void
    {
        static::addGlobalScope('hanya_owner', function (Builder $query) {
            if (! static::aksesGajiDiizinkan()) {
                $query->whereRaw('1 = 0');
            }
        });

        $tolak = function () {
            if (! static::aksesGajiDiizinkan()) {
                throw new AuthorizationException('Data gaji hanya dapat diakses oleh Owner.');
            }
        };

        static::saving($tolak);
        static::deleting($tolak);
    }

    public static function aksesGajiDiizinkan(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return app()->runningInConsole(); // seeder & perintah artisan
        }

        return $user->is_active && $user->isOwner();
    }
}