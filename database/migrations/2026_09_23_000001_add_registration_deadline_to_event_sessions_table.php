<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('event_sessions', 'registration_deadline')) {
            Schema::table('event_sessions', function (Blueprint $table) {
                $table->date('registration_deadline')->nullable()->after('end_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('event_sessions', 'registration_deadline')) {
            Schema::table('event_sessions', function (Blueprint $table) {
                $table->dropColumn('registration_deadline');
            });
        }
    }
};
