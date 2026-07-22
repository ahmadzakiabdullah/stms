<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make uuid the primary key for users (following project UUID standard).
     * Assumes uuid column exists and is backfilled from previous migration.
     * WARNING: High risk on production with existing data/auth. Test thoroughly.
     * This migration drops the old id PK and makes uuid primary.
     * Spatie tables use morph, so should be ok as long as keys match.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'uuid')) {
            return;
        }

        DB::statement('ALTER TABLE users MODIFY id BIGINT UNSIGNED NOT NULL');
        Schema::table('users', function (Blueprint $table) {
            $table->dropPrimary(['id']);
            $table->primary('uuid');
            $table->dropColumn('id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropPrimary(['uuid']);
        });
        DB::statement('ALTER TABLE users ADD COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');
    }
};
