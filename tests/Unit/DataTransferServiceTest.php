<?php

namespace Tests\Unit;

use App\Jobs\ProcessDataTransfer;
use App\Models\DataTransfer;
use App\Models\Organization;
use App\Models\Session;
use App\Services\DataTransferService;
use App\Services\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class DataTransferServiceTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    private function transfer(array $attributes = []): DataTransfer
    {
        $org = Organization::factory()->create();
        $owner = $this->createOrgAdmin($org);

        return DataTransfer::factory()->create(['organization_id' => $org->id, 'requested_by' => $owner->uuid, ...$attributes]);
    }

    private function runJob(DataTransfer $transfer): void
    {
        $job = new ProcessDataTransfer($transfer->id, $transfer->organization_id);
        app(Pipeline::class)->send($job)->through($job->middleware())
            ->then(fn ($job) => $job->handle(app(DataTransferService::class)));
    }

    public function test_export_runs_with_job_tenant_and_clears_previous_context(): void
    {
        Storage::fake('local');
        $transfer = $this->transfer();
        TenantContext::setOrganizationId(Organization::factory()->create()->id);
        $this->runJob($transfer);
        $this->assertFalse(TenantContext::isInitialized());
        $transfer->refresh();
        $this->assertSame(DataTransfer::STATUS_COMPLETED, $transfer->status);
        $this->assertSame(100, $transfer->progress);
        Storage::disk('local')->assertExists($transfer->output_path);
        $this->runJob($transfer);
        $this->assertSame($transfer->output_path, $transfer->fresh()->output_path);
    }

    public function test_import_locks_tenant_and_parent_fields_and_does_not_repeat_completed_work(): void
    {
        Storage::fake('local');
        $transfer = $this->transfer(['type' => DataTransfer::TYPE_IMPORT_PARTICIPANTS]);
        $session = Session::factory()->create(['organization_id' => $transfer->organization_id]);
        $foreign = Session::factory()->create();
        $path = 'transfers/'.$transfer->organization_id.'/input/preview.json';
        Storage::disk('local')->put($path, json_encode([['data' => ['name' => 'Test Team', 'slug' => 'test-team', 'participant_type' => 'team', 'organization_id' => $foreign->organization_id, 'session_id' => $foreign->id]]], JSON_THROW_ON_ERROR));
        $transfer->update(['source_path' => $path, 'payload' => ['session_id' => $session->id]]);
        $this->runJob($transfer);
        $this->assertDatabaseHas('participants', ['slug' => 'test-team', 'organization_id' => $transfer->organization_id, 'session_id' => $session->id]);
        Storage::disk('local')->assertMissing($path);
        $this->runJob($transfer);
        $this->assertDatabaseCount('participants', 1);
    }

    public function test_failed_import_can_retry_after_source_is_restored_and_context_is_cleaned(): void
    {
        Storage::fake('local');
        $transfer = $this->transfer(['type' => DataTransfer::TYPE_IMPORT_PARTICIPANTS]);
        $path = 'transfers/'.$transfer->organization_id.'/input/missing.json';
        $transfer->update(['source_path' => $path]);
        try {
            $this->runJob($transfer);
            $this->fail('Missing source must fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('The import source file is missing.', $exception->getMessage());
        }
        $this->assertFalse(TenantContext::isInitialized());
        $this->assertSame(DataTransfer::STATUS_FAILED, $transfer->fresh()->status);
        Storage::disk('local')->put($path, '[]');
        $this->runJob($transfer);
        $this->assertSame(DataTransfer::STATUS_COMPLETED, $transfer->fresh()->status);
    }

    public function test_failed_callback_cannot_update_another_tenants_transfer_or_successful_import(): void
    {
        $transfer = $this->transfer();
        (new ProcessDataTransfer($transfer->id, Organization::factory()->create()->id))->failed(new \RuntimeException('failed'));
        $this->assertSame(DataTransfer::STATUS_PENDING, $transfer->fresh()->status);
        $transfer->update(['status' => DataTransfer::STATUS_COMPLETED_WITH_ERRORS]);
        (new ProcessDataTransfer($transfer->id, $transfer->organization_id))->failed(new \RuntimeException('failed'));
        $this->assertSame(DataTransfer::STATUS_COMPLETED_WITH_ERRORS, $transfer->fresh()->status);
    }

    public function test_process_fails_closed_without_a_matching_tenant_context(): void
    {
        $transfer = $this->transfer();
        TenantContext::setOrganizationId(Organization::factory()->create()->id);
        try {
            app(DataTransferService::class)->process($transfer->id, $transfer->organization_id);
            $this->fail('Wrong tenant must fail.');
        } catch (\LogicException) {
            $this->assertSame(DataTransfer::STATUS_PENDING, $transfer->fresh()->status);
        } finally {
            TenantContext::reset();
        }
    }

    public function test_worker_rechecks_permission_revocation(): void
    {
        $transfer = $this->transfer();
        $transfer->requestedBy->syncRoles([]);
        try {
            $this->runJob($transfer);
            $this->fail('Revoked permission must fail.');
        } catch (AuthorizationException) {
            $this->assertSame(DataTransfer::STATUS_FAILED, $transfer->fresh()->status);
            $this->assertFalse(TenantContext::isInitialized());
        }
    }

    public function test_abandoned_running_transfer_is_recovered_under_the_job_lock(): void
    {
        Storage::fake('local');
        $transfer = $this->transfer(['status' => DataTransfer::STATUS_RUNNING]);
        $this->runJob($transfer);
        $this->assertSame(DataTransfer::STATUS_COMPLETED, $transfer->fresh()->status);
        $job = new ProcessDataTransfer($transfer->id, $transfer->organization_id);
        $this->assertGreaterThan($job->timeout, config('queue.connections.'.$job->connection.'.retry_after'));
    }

    public function test_import_cannot_read_a_source_in_another_tenants_partition(): void
    {
        Storage::fake('local');
        $transfer = $this->transfer(['type' => DataTransfer::TYPE_IMPORT_PARTICIPANTS]);
        $path = 'transfers/'.Organization::factory()->create()->id.'/input/private.json';
        Storage::disk('local')->put($path, '[]');
        $transfer->update(['source_path' => $path]);
        try {
            $this->runJob($transfer);
            $this->fail('Foreign source must fail.');
        } catch (\RuntimeException) {
            $this->assertSame(DataTransfer::STATUS_FAILED, $transfer->fresh()->status);
            Storage::disk('local')->assertExists($path);
        }
    }

    public function test_import_cannot_select_a_session_in_another_organization(): void
    {
        Storage::fake('local');
        $transfer = $this->transfer(['type' => DataTransfer::TYPE_IMPORT_PARTICIPANTS]);
        $path = 'transfers/'.$transfer->organization_id.'/input/preview.json';
        Storage::disk('local')->put($path, '[]');
        $transfer->update(['source_path' => $path, 'payload' => ['session_id' => Session::factory()->create()->id]]);
        try {
            $this->runJob($transfer);
            $this->fail('Foreign session must fail.');
        } catch (ModelNotFoundException) {
            $this->assertSame(DataTransfer::STATUS_FAILED, $transfer->fresh()->status);
        }
    }
}
