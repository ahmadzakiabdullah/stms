<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squad_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_participant_id')->constrained('event_participants')->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->string('name');
            $table->enum('role', ['athlete_male', 'athlete_female', 'manager', 'coach', 'physio']);
            $table->string('identification_no', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squad_members');
    }
};
