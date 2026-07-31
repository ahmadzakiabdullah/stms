<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('stage', 20)->default('group')->after('pool_id')->index();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->unsignedTinyInteger('qualifiers_per_pool')->default(2)->after('pool_size');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('stage');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('qualifiers_per_pool');
        });
    }
};
