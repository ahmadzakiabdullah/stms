<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('event_sessions', 'registration_deadline') && ! Schema::hasColumn('event_sessions', 'event_registration_deadline')) {
            Schema::table('event_sessions', function (Blueprint $table) {
                $table->renameColumn('registration_deadline', 'event_registration_deadline');
            });
        }

        if (! Schema::hasColumn('event_sessions', 'event_registration_deadline')) {
            Schema::table('event_sessions', function (Blueprint $table) {
                $table->date('event_registration_deadline')->nullable()->after('end_date');
            });
        }

        if (! Schema::hasColumn('event_sessions', 'squad_registration_deadline')) {
            Schema::table('event_sessions', function (Blueprint $table) {
                $table->date('squad_registration_deadline')->nullable()->after('event_registration_deadline');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('event_sessions', 'squad_registration_deadline')) {
            Schema::table('event_sessions', function (Blueprint $table) {
                $table->dropColumn('squad_registration_deadline');
            });
        }

        if (Schema::hasColumn('event_sessions', 'event_registration_deadline') && ! Schema::hasColumn('event_sessions', 'registration_deadline')) {
            Schema::table('event_sessions', function (Blueprint $table) {
                $table->renameColumn('event_registration_deadline', 'registration_deadline');
            });
        }
    }
};
