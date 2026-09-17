<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('sport_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('session_id')->constrained('event_sessions')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignUuid('created_by')->nullable()->constrained('users', 'uuid')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'session_id', 'sport_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_documents');
    }
};
