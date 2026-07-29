<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE event_participants MODIFY COLUMN status ENUM('pending','confirmed','withdrawn','disqualified','rejected') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE event_participants MODIFY COLUMN status ENUM('pending','confirmed','withdrawn','disqualified') NOT NULL DEFAULT 'pending'");
    }
};
