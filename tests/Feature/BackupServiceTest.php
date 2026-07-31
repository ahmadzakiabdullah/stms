<?php

namespace Tests\Feature;

use App\Services\BackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = storage_path('framework/testing/backup-'.bin2hex(random_bytes(5)));
        File::ensureDirectoryExists($this->workspace.'/public');
        File::ensureDirectoryExists($this->workspace.'/archives');

        $database = $this->workspace.'/database.sqlite';
        touch($database);

        config()->set('database.default', 'backup_test');
        config()->set('database.connections.backup_test', [
            'driver' => 'sqlite',
            'database' => $database,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('app.backup.path', $this->workspace.'/archives');
        config()->set('app.backup.source_path', $this->workspace.'/public');
        config()->set('app.backup.encryption_key', str_repeat('a', 32));
        DB::purge('backup_test');

        DB::statement('CREATE TABLE recovery_test (value TEXT NOT NULL)');
        DB::table('recovery_test')->insert(['value' => 'before-backup']);
        File::put($this->workspace.'/public/logo.txt', 'original-logo');
    }

    protected function tearDown(): void
    {
        DB::purge('backup_test');
        File::deleteDirectory($this->workspace);

        parent::tearDown();
    }

    public function test_encrypted_backup_can_restore_database_and_public_storage(): void
    {
        $service = app(BackupService::class);
        $archive = $service->create();

        $this->assertFileExists($archive);

        DB::table('recovery_test')->update(['value' => 'after-backup']);
        File::put($this->workspace.'/public/logo.txt', 'changed-logo');

        $service->restore($archive);

        $this->assertSame('before-backup', DB::table('recovery_test')->value('value'));
        $this->assertSame('original-logo', File::get($this->workspace.'/public/logo.txt'));
    }

    public function test_backup_requires_a_strong_encryption_key(): void
    {
        config()->set('app.backup.encryption_key', 'short');

        $this->expectExceptionMessage('BACKUP_ENCRYPTION_KEY must contain at least 32 characters.');

        app(BackupService::class)->create();
    }
}
