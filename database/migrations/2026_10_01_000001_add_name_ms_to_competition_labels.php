<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sports', 'sport_categories', 'events'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('name_ms')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        foreach (['sports', 'sport_categories', 'events'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('name_ms');
            });
        }
    }
};
