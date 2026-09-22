<?php

namespace Tests\Feature;

use App\Jobs\ProcessDataTransfer;
use App\Models\DataTransfer;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Participant;
use App\Services\DataTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class DataTransferTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_queue_export_returns_a_tenant_scoped_transfer_and_is_idempotent(): void
    {
        Queue::fake();
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);

        $first = $this->actingAs($admin)->postJson(route('exports.queue'), [
            'type' => 'fixtures',
            'idempotency_key' => 'fixtures:daily:2026-09-21',
        ])->assertStatus(202);

        $transferId = $first->json('data.id');
        $second = $this->actingAs($admin)->postJson(route('exports.queue'), [
            'type' => 'fixtures',
            'idempotency_key' => 'fixtures:daily:2026-09-21',
        ])->assertOk();

        $this->assertSame($transferId, $second->json('data.id'));
        Queue::assertPushed(ProcessDataTransfer::class, 1);
        $this->assertDatabaseHas('data_transfers', [
            'id' => $transferId,
            'organization_id' => $org->id,
            'status' => DataTransfer::STATUS_PENDING,
        ]);
    }

    public function test_transfer_status_and_download_cannot_cross_tenant_boundaries(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $userB = $this->createOrgAdmin($orgB);
        $transfer = DataTransfer::factory()->create([
            'organization_id' => $orgA->id,
            'status' => DataTransfer::STATUS_COMPLETED,
        ]);

        $this->actingAs($userB)->getJson(route('data-transfers.show', $transfer->id))->assertNotFound();
        $this->actingAs($userB)->get(route('data-transfers.download', $transfer->id))->assertNotFound();
    }

    public function test_queue_event_participant_import_stores_source_and_dispatches_once(): void
    {
        Queue::fake();
        Storage::fake('local');
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($admin)->post(route('event-participants.import.queue'), [
            'participant_id' => $participant->id,
            'file' => UploadedFile::fake()->createWithContent('registrations.csv', "event_name\nBadminton - Singles\n"),
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('data_transfers', 1);
        $transfer = DataTransfer::query()->firstOrFail();
        $this->assertSame(DataTransfer::TYPE_IMPORT_EVENT_PARTICIPANTS, $transfer->type);
        $this->assertNotNull($transfer->source_path);
        Storage::disk('local')->assertExists($transfer->source_path);
        Queue::assertPushed(ProcessDataTransfer::class, 1);
    }

    public function test_service_status_exposes_failure_report_and_no_download_for_failed_transfer(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $transfer = DataTransfer::factory()->create([
            'organization_id' => $org->id,
            'requested_by' => $admin->uuid,
            'status' => DataTransfer::STATUS_COMPLETED_WITH_ERRORS,
            'progress' => 100,
            'failure_report' => ['Row 4: duplicate slug.'],
        ]);

        $payload = app(DataTransferService::class)->status($transfer);

        $this->assertSame(['Row 4: duplicate slug.'], $payload['failure_report']);
        $this->assertNull($payload['download_url']);
    }

    public function test_same_tenant_peer_cannot_read_or_download_another_users_transfer(): void
    {
        $org = Organization::factory()->create();
        $owner = $this->createOrgAdmin($org);
        $peer = $this->createOrgAdmin($org);
        $transfer = DataTransfer::factory()->create(['organization_id' => $org->id, 'requested_by' => $owner->uuid]);

        $this->actingAs($peer)->getJson(route('data-transfers.show', $transfer))->assertForbidden();
        $this->get(route('data-transfers.download', $transfer))->assertForbidden();
        $this->actingAs($owner)->getJson(route('data-transfers.show', $transfer))->assertOk()->assertJsonPath('data.id', $transfer->id);
    }

    public function test_owner_losing_export_access_cannot_read_the_transfer(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createUserInOrganization($org);
        $transfer = DataTransfer::factory()->create(['organization_id' => $org->id, 'requested_by' => $user->uuid]);
        $this->actingAs($user)->getJson(route('data-transfers.show', $transfer))->assertForbidden();
        $this->postJson(route('exports.queue'), ['type' => 'fixtures'])->assertForbidden();
    }

    public function test_download_requires_completed_output_in_the_transfers_own_partition(): void
    {
        Storage::fake('local');
        $org = Organization::factory()->create();
        $owner = $this->createOrgAdmin($org);
        $transfer = DataTransfer::factory()->create(['organization_id' => $org->id, 'requested_by' => $owner->uuid]);
        $path = 'transfers/'.$org->id.'/output/'.$transfer->id.'/fixtures.xlsx';
        Storage::disk('local')->put($path, 'export');
        $transfer->update(['output_path' => $path, 'output_name' => 'fixtures.xlsx']);
        $this->actingAs($owner)->get(route('data-transfers.download', $transfer))->assertStatus(409);
        $transfer->update(['status' => DataTransfer::STATUS_COMPLETED]);
        $this->get(route('data-transfers.download', $transfer))->assertDownload('fixtures.xlsx');
        Storage::disk('local')->put('private-secret.xlsx', 'private');
        $transfer->update(['output_path' => 'private-secret.xlsx']);
        $this->get(route('data-transfers.download', $transfer))->assertNotFound();
    }

    public function test_idempotency_key_cannot_be_reused_by_a_peer_or_for_another_export(): void
    {
        Queue::fake();
        $org = Organization::factory()->create();
        $owner = $this->createOrgAdmin($org);
        $peer = $this->createOrgAdmin($org);
        $input = ['type' => 'fixtures', 'idempotency_key' => 'daily'];
        $this->actingAs($owner)->postJson(route('exports.queue'), $input)->assertStatus(202);
        $this->postJson(route('exports.queue'), [...$input, 'type' => 'results'])->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $this->actingAs($peer)->postJson(route('exports.queue'), $input)->assertUnprocessable();
        Queue::assertPushed(ProcessDataTransfer::class, 1);
        $this->assertDatabaseCount('data_transfers', 1);
    }

    public function test_guest_and_cross_tenant_export_parameters_are_rejected(): void
    {
        Queue::fake();
        $transfer = DataTransfer::factory()->create();
        $this->getJson(route('data-transfers.show', $transfer))->assertUnauthorized();
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $otherEvent = Event::factory()->create();
        $this->actingAs($admin)->postJson(route('exports.queue'), ['type' => 'fixtures', 'event_id' => $otherEvent->id])->assertNotFound();
        Queue::assertNothingPushed();
    }
}
