<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_sports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('session_id')->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignUuid('sport_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['session_id', 'sport_id'], 'session_sports_session_sport_unique');
            $table->index(['organization_id', 'session_id', 'is_active']);
        });

        Schema::create('session_sport_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('session_sport_id')->constrained('session_sports')->cascadeOnDelete();
            $table->foreignUuid('sport_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('quota_mode')->default('gender_based');
            $table->unsignedInteger('max_athletes_total')->nullable();
            $table->unsignedInteger('max_male_athletes')->nullable();
            $table->unsignedInteger('max_female_athletes')->nullable();
            $table->unsignedInteger('max_officials')->default(0);
            $table->string('competition_system')->nullable();
            $table->string('venue')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->unique(['session_sport_id', 'sport_category_id'], 'session_sport_category_unique');
            $table->index(['organization_id', 'session_sport_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_sport_categories');
        Schema::dropIfExists('session_sports');
    }
};
