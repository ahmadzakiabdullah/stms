<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Guarded so already-deployed installs that recorded the previous
        // file name (2026_06_12_000002) simply re-run this as a no-op.
        if (Schema::hasTable('tournament_sport')) {
            return;
        }

        Schema::create('tournament_sport', function (Blueprint $table) {
            $table->foreignUuid('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignUuid('sport_id')->constrained('sports')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['tournament_id', 'sport_id']);
            $table->index('tournament_id');
            $table->index('sport_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_sport');
    }
};
