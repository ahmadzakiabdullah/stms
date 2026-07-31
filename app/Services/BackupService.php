<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupService
{
    public function create(?string $destination = null): string
    {
        $directory = $destination ?: config('app.backup.path');
        File::ensureDirectoryExists($directory, 0700);

        $path = rtrim($directory, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'stms-'.now()->format('Ymd-His').'.zip';
        $temporaryDirectory = storage_path('app/backup-work-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($temporaryDirectory, 0700);

        try {
            $databaseFile = $this->dumpDatabase($temporaryDirectory);
            $zip = new ZipArchive;

            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new RuntimeException("Unable to create backup archive [{$path}].");
            }

            $entries = [];
            $this->addFile($zip, $databaseFile, 'database/'.basename($databaseFile), $entries);

            $publicStorage = config('app.backup.source_path', storage_path('app/public'));
            if (File::isDirectory($publicStorage)) {
                foreach (File::allFiles($publicStorage) as $file) {
                    $relative = str_replace('\\', '/', $file->getRelativePathname());
                    $this->addFile($zip, $file->getPathname(), 'files/'.$relative, $entries);
                }
            }

            $manifest = json_encode([
                'format' => 1,
                'created_at' => now()->toIso8601String(),
                'database_connection' => DB::getDefaultConnection(),
                'entries' => $entries,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            $zip->addFromString('manifest.json', $manifest);

            $key = $this->encryptionKey();
            foreach ([...array_keys($entries), 'manifest.json'] as $entry) {
                if (! $zip->setEncryptionName($entry, ZipArchive::EM_AES_256, $key)) {
                    $zip->close();
                    throw new RuntimeException("Unable to encrypt backup entry [{$entry}].");
                }
            }

            $zip->close();

            return $path;
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }

    public function restore(string $archive): void
    {
        if (! File::isFile($archive)) {
            throw new RuntimeException("Backup archive [{$archive}] does not exist.");
        }

        $temporaryDirectory = storage_path('app/restore-work-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($temporaryDirectory, 0700);

        try {
            $zip = new ZipArchive;
            if ($zip->open($archive) !== true) {
                throw new RuntimeException("Unable to open backup archive [{$archive}].");
            }

            $zip->setPassword($this->encryptionKey());
            if (! $zip->extractTo($temporaryDirectory)) {
                $zip->close();
                throw new RuntimeException('Unable to decrypt or extract the backup archive.');
            }
            $zip->close();

            $manifestPath = $temporaryDirectory.DIRECTORY_SEPARATOR.'manifest.json';
            $manifest = json_decode(File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);
            $this->verifyManifest($temporaryDirectory, $manifest);

            $databaseFiles = File::files($temporaryDirectory.DIRECTORY_SEPARATOR.'database');
            if (count($databaseFiles) !== 1) {
                throw new RuntimeException('Backup must contain exactly one database dump.');
            }

            $this->restoreDatabase($databaseFiles[0]->getPathname());

            $restoredFiles = $temporaryDirectory.DIRECTORY_SEPARATOR.'files';
            $publicStorage = config('app.backup.source_path', storage_path('app/public'));
            File::deleteDirectory($publicStorage);
            File::ensureDirectoryExists($publicStorage, 0755);
            if (File::isDirectory($restoredFiles)) {
                File::copyDirectory($restoredFiles, $publicStorage);
            }
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }

    public function prune(?string $directory = null): int
    {
        $path = $directory ?: config('app.backup.path');
        if (! File::isDirectory($path)) {
            return 0;
        }

        $cutoff = now()->subDays(max(1, (int) config('app.backup.retention_days', 14)))->getTimestamp();
        $deleted = 0;

        foreach (File::glob(rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'stms-*.zip') as $archive) {
            if (File::lastModified($archive) < $cutoff && File::delete($archive)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private function dumpDatabase(string $directory): string
    {
        $connection = DB::getDefaultConnection();
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? null) === 'sqlite') {
            $source = $config['database'];
            if (! is_string($source) || ! File::isFile($source)) {
                throw new RuntimeException('SQLite database file does not exist.');
            }

            $target = $directory.DIRECTORY_SEPARATOR.'database.sqlite';
            DB::disconnect($connection);
            File::copy($source, $target);

            return $target;
        }

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('Only SQLite and MySQL backups are supported.');
        }

        $target = $directory.DIRECTORY_SEPARATOR.'database.sql';
        $arguments = [
            config('app.backup.mysqldump_binary', 'mysqldump'),
            '--single-transaction', '--quick', '--skip-lock-tables',
            '--host='.$config['host'], '--port='.(string) $config['port'],
            '--user='.$config['username'], '--result-file='.$target,
            $config['database'],
        ];
        $process = new Process($arguments, null, ['MYSQL_PWD' => (string) $config['password']]);
        $process->setTimeout(3600);
        $process->mustRun();

        return $target;
    }

    private function restoreDatabase(string $dump): void
    {
        $connection = DB::getDefaultConnection();
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? null) === 'sqlite') {
            DB::disconnect($connection);
            File::copy($dump, $config['database']);
            DB::purge($connection);

            return;
        }

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('Only SQLite and MySQL restores are supported.');
        }

        $process = new Process([
            config('app.backup.mysql_binary', 'mysql'),
            '--host='.$config['host'], '--port='.(string) $config['port'],
            '--user='.$config['username'],
            $config['database'],
        ], null, ['MYSQL_PWD' => (string) $config['password']]);
        $input = fopen($dump, 'rb');
        if ($input === false) {
            throw new RuntimeException('Unable to read the database dump.');
        }
        $process->setInput($input);
        $process->setTimeout(3600);
        try {
            $process->mustRun();
        } finally {
            fclose($input);
        }
        DB::purge($connection);
    }

    private function addFile(ZipArchive $zip, string $source, string $entry, array &$entries): void
    {
        if (! $zip->addFile($source, $entry)) {
            throw new RuntimeException("Unable to add [{$entry}] to the backup.");
        }

        $entries[$entry] = hash_file('sha256', $source);
    }

    private function verifyManifest(string $directory, array $manifest): void
    {
        if (($manifest['format'] ?? null) !== 1 || ! is_array($manifest['entries'] ?? null)) {
            throw new RuntimeException('Backup manifest is invalid or unsupported.');
        }

        foreach ($manifest['entries'] as $entry => $checksum) {
            $path = $directory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $entry);
            if (! File::isFile($path) || ! hash_equals($checksum, hash_file('sha256', $path))) {
                throw new RuntimeException("Backup integrity check failed for [{$entry}].");
            }
        }
    }

    private function encryptionKey(): string
    {
        $key = (string) config('app.backup.encryption_key');
        if (strlen($key) < 32) {
            throw new RuntimeException('BACKUP_ENCRYPTION_KEY must contain at least 32 characters.');
        }

        return $key;
    }
}
