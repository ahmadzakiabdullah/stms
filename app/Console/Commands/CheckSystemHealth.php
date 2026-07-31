<?php

namespace App\Console\Commands;

use App\Services\SystemHealthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSystemHealth extends Command
{
    protected $signature = 'stms:health-check';

    protected $description = 'Check database, cache, queue, and disk health for monitoring and alerting';

    public function handle(SystemHealthService $health): int
    {
        $result = $health->check();

        if ($result['status'] !== 'ok') {
            Log::critical('STMS system health is degraded', $result);
            $this->error(json_encode($result, JSON_PRETTY_PRINT));

            return self::FAILURE;
        }

        $this->info(json_encode($result, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
