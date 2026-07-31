<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

class RestoreApplication extends Command
{
    protected $signature = 'stms:restore
        {archive : Absolute path to an STMS backup archive}
        {--force : Required to perform the destructive restore}';

    protected $description = 'Restore the database and public storage from a verified encrypted STMS backup';

    public function handle(BackupService $backups): int
    {
        if (! $this->option('force')) {
            $this->error('Restore refused. Re-run with --force after taking the application offline.');

            return self::FAILURE;
        }

        if (! $this->confirm('This replaces the current database and public storage. Continue?', false)) {
            $this->warn('Restore cancelled.');

            return self::FAILURE;
        }

        try {
            $backups->restore((string) $this->argument('archive'));
            $this->info('Restore completed and backup integrity verified.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Restore failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
