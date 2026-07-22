<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('match_id')->constrained('matches')->cascadeOnDelete();
            $table->unsignedInteger('score_home')->nullable();
            $table->unsignedInteger('score_away')->nullable();
            $table->foreignUuid('winner_participant_id')->nullable()->constrained('participants')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('match_id');
            $table->index(['organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
