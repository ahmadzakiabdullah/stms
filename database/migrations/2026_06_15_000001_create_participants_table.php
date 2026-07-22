<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->uuid('session_id')->nullable();
            $table->foreign('session_id')->references('id')->on('event_sessions')->cascadeOnDelete();
            $table->string('name');
            $table->string('identification_number')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('date_of_birth')->nullable();
            $table->enum('participant_type', ['individual', 'team'])->default('individual');
            $table->string('team_name')->nullable();
            $table->enum('status', ['registered', 'confirmed', 'withdrawn', 'disqualified'])->default('registered');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'session_id', 'status'], 'participants_org_session_status_idx');
            $table->index('identification_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
