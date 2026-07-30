<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'uuid')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support dropping primary keys or columns easily without table rebuilds.
            // We'll skip the raw statements and rely on a rebuild approach if strictly necessary,
            // but for simplicity, we won't drop the 'id' column here if it's SQLite.
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
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropPrimary(['uuid']);
        });
        DB::statement('ALTER TABLE users ADD COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');
    }
};
