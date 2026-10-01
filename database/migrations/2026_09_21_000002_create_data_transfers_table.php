<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_transfers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('requested_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->string('type', 60);
            $table->string('status', 30)->default('pending');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('total')->nullable();
            $table->string('source_path')->nullable();
            $table->string('output_path')->nullable();
            $table->string('output_name')->nullable();
            $table->json('payload')->nullable();
            $table->json('failure_report')->nullable();
            $table->string('idempotency_key', 150)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'idempotency_key'], 'data_transfers_org_idempotency_unique');
            $table->index(['organization_id', 'status', 'created_at'], 'data_transfers_org_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_transfers');
    }
};
