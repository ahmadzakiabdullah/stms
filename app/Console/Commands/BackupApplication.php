<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

class BackupApplication extends Command
{
    protected $signature = 'stms:backup {--path= : Override the configured backup directory}';

    protected $description = 'Create an encrypted database and public-storage backup, then enforce retention';

    public function handle(BackupService $backups): int
    {
        try {
            $path = $backups->create($this->option('path') ?: null);
            $deleted = $backups->prune($this->option('path') ?: null);
            $this->info("Encrypted backup created: {$path}");
            $this->line("Expired backups removed: {$deleted}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
