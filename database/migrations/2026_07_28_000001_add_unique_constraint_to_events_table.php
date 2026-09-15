<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any duplicate events (keep the earliest created) before adding unique constraint
        $duplicates = DB::table('events as e')
            ->select('e.tournament_id', 'e.sport_id', 'e.sport_category_id', DB::raw('MIN(e.id) as keep_id'))
            ->groupBy('e.tournament_id', 'e.sport_id', 'e.sport_category_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            DB::table('events')
                ->where('tournament_id', $dup->tournament_id)
                ->where('sport_id', $dup->sport_id)
                ->where('sport_category_id', $dup->sport_category_id)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        try {
            Schema::table('events', function (Blueprint $table) {
                $table->unique(['tournament_id', 'sport_id', 'sport_category_id'], 'events_tournament_sport_category_unique');
            });
        } catch (\Throwable $e) {
            // Index may already exist
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique('events_tournament_sport_category_unique');
        });
    }
};
