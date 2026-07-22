<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('event_participants')) {
            Schema::create('event_participants', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('event_id');
                $table->uuid('participant_id');
                $table->timestamp('registration_date')->nullable();
                $table->string('status')->default('registered');
                $table->unsignedInteger('seed_number')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
                $table->foreign('participant_id')->references('id')->on('participants')->cascadeOnDelete();
                $table->unique(['event_id', 'participant_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
    }
};
