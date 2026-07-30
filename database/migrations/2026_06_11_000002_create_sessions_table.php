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
        // If the table exists but is the old polluted Laravel 'sessions' table
        // (has 'payload' column from session storage), drop it so we can create a clean one.
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
        Schema::dropIfExists('event_sessions');
    }
};
