<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add UUID support to users table (following project standard of UUID primary keys).
     * We add a `uuid` column for now (non-breaking).
     * Full PK swap can be done in a future controlled migration after verification.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }
        });

        // Backfill existing users with UUIDs (safe, one-time)
        DB::table('users')->whereNull('uuid')->update([
            'uuid' => DB::raw('UUID()'),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};
