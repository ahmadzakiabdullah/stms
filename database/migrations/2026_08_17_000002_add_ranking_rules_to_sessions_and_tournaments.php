<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_sessions', function (Blueprint $table): void {
            $table->json('ranking_rules')->nullable()->after('ranking_strategy');
        });

        Schema::table('tournaments', function (Blueprint $table): void {
            $table->json('ranking_rules')->nullable()->after('ranking_strategy');
        });
    }

    public function down(): void
    {
        Schema::table('event_sessions', function (Blueprint $table): void {
            $table->dropColumn('ranking_rules');
        });

        Schema::table('tournaments', function (Blueprint $table): void {
            $table->dropColumn('ranking_rules');
        });
    }
};
