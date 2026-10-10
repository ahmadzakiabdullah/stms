<?php

namespace App\Jobs;

use App\Contracts\TenantAwareJob;
use App\Models\DataTransfer;
use App\Queue\Middleware\SetTenantContext;
use App\Services\DataTransferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessDataTransfer implements ShouldQueue, TenantAwareJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(public string $transferId, public string $organizationId)
    {
        if ($organizationId === '') {
            throw new \InvalidArgumentException('A data transfer job requires an organization.');
        }
        $this->onConnection('data-transfers');
    }

    public function tenantOrganizationId(): string
    {
        return $this->organizationId;
    }

    public function middleware(): array
    {
        return [new SetTenantContext, (new WithoutOverlapping('data-transfer:'.$this->transferId))->releaseAfter(30)->expireAfter(960)];
    }

    public function handle(DataTransferService $service): void
    {
        $service->process($this->transferId, $this->organizationId);
    }

    public function failed(Throwable $exception): void
    {
        $transfer = DataTransfer::forOrganization($this->organizationId)->find($this->transferId);

        if (! $transfer || $transfer->isSuccessful()) {
            return;
        }

        $report = $transfer->failure_report ?? [];
        Log::error('Data transfer queue job failed', [
            'transfer_id' => $this->transferId,
            'error' => $exception->getMessage(),
        ]);
        $report[] = 'Queue processing failed. Please retry or contact an administrator.';

        $transfer->forceFill([
            'status' => DataTransfer::STATUS_FAILED,
            'failure_report' => $report,
        ])->save();
    }
}
