<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_scoring_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('result_id')->constrained('results')->cascadeOnDelete();
            $table->foreignUuid('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignUuid('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->foreignUuid('squad_member_id')->constrained('squad_members')->restrictOnDelete();
            $table->string('event_type')->default('goal');
            $table->unsignedSmallInteger('period')->nullable();
            $table->unsignedSmallInteger('minute')->nullable();
            $table->unsignedTinyInteger('second')->nullable();
            $table->unsignedSmallInteger('points')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['match_id', 'participant_id']);
            $table->index(['result_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_scoring_events');
    }
};
