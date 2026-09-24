<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('event_sessions', 'event_registration_start_date')) {
                $table->date('event_registration_start_date')->nullable()->after('end_date');
            }
            if (! Schema::hasColumn('event_sessions', 'squad_registration_start_date')) {
                $table->date('squad_registration_start_date')->nullable()->after('squad_registration_deadline');
            }
        });
    }

    public function down(): void
    {
        Schema::table('event_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('event_sessions', 'squad_registration_start_date')) {
                $table->dropColumn('squad_registration_start_date');
            }
            if (Schema::hasColumn('event_sessions', 'event_registration_start_date')) {
                $table->dropColumn('event_registration_start_date');
            }
        });
    }
};
