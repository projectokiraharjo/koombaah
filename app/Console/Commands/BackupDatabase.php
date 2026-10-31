<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use RuntimeException;

class BackupDatabase extends Command
{
    protected $signature = 'koombaah:backup';
    protected $description = 'Backup database PostgreSQL (PRD C.1)';

    public function handle(BackupService $backup): int
    {
        try {
            $this->info('Cadangan dibuat: ' . $backup->buat());

            return self::SUCCESS;
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}