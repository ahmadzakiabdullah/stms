<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draw_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->unsignedInteger('version');
            $table->string('action', 40);
            $table->string('seed')->nullable();
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['event_id', 'version']);
            $table->index(['organization_id', 'event_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draw_versions');
    }
};
