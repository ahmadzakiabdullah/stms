<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the single event venue with a JSON list of venues so an event
     * can offer several locations. The first venue is the default used when
     * creating matches; the rest are optional alternatives.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('venues')->nullable()->after('description');
            $table->dropColumn('venue');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('venue')->nullable()->after('description');
            $table->dropColumn('venues');
        });
    }
};
