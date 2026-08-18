<?php

namespace App\Console\Commands;

use App\Services\ReleasePreflightService;
use Illuminate\Console\Command;

class CheckReleasePreflight extends Command
{
    protected $signature = 'stms:release-preflight
        {--json : Output machine-readable JSON}
        {--max-backup-age-hours=24 : Maximum acceptable age of the latest configured backup}';

    protected $description = 'Run non-destructive repository and runtime checks before collecting release evidence';

    public function handle(ReleasePreflightService $preflight): int
    {
        $maxBackupAgeHours = filter_var($this->option('max-backup-age-hours'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 168],
        ]);

        if ($maxBackupAgeHours === false) {
            $this->error('The maximum backup age must be an integer between 1 and 168 hours.');

            return self::INVALID;
        }

        $result = $preflight->check($maxBackupAgeHours);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $rows = collect($result['checks'])
                ->map(fn (array $check, string $name) => [
                    $name,
                    strtoupper($check['status']),
                    $check['message'],
                ])
                ->values()
                ->all();

            $this->table(['Check', 'Status', 'Message'], $rows);
            $this->newLine();
            $this->line('This command does not send mail, receive external alerts, test load, or perform a restore/deploy.');
        }

        return $result['status'] === 'ok' ? self::SUCCESS : self::FAILURE;
    }
}
