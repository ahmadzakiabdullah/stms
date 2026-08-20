<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a default venue at the event level so matches inherit it and the
     * public portal can surface venues before any match has one assigned.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('venue')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('venue');
        });
    }
};
