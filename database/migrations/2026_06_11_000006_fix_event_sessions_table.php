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
        // The current event_sessions table is polluted from the old Laravel 'sessions' table
        // (it has 'payload', 'user_id' etc. which have no defaults and cause insert errors).
        // Drop it and recreate clean.
        if (Schema::hasTable('event_sessions')) {
            if (Schema::hasColumn('event_sessions', 'payload')) {
                Schema::drop('event_sessions');
            }
        }

        if (! Schema::hasTable('event_sessions')) {
            Schema::create('event_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['organization_id', 'is_active']);
                $table->index('slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Do not drop in down to avoid losing data accidentally.
        // If needed, manual drop.
    }
};
