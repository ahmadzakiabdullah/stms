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
        // Rename the old 'sessions' table (which may have been Laravel's or our previous) to 'event_sessions'
        // if 'event_sessions' does not exist yet. This avoids conflict with Laravel's built-in sessions table for session storage.
        if (Schema::hasTable('sessions') && !Schema::hasTable('event_sessions')) {
            Schema::rename('sessions', 'event_sessions');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('event_sessions') && !Schema::hasTable('sessions')) {
            Schema::rename('event_sessions', 'sessions');
        }
    }
};
