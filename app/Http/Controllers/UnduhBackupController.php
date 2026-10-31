<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Support\Facades\Storage;

class UnduhBackupController
{
    public function __invoke(string $nama, BackupService $backup)
    {
        return Storage::disk('local')->download($backup->lokasi($nama));
    }
}