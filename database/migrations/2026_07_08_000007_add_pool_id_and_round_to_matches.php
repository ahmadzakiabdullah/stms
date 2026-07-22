<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignUuid('pool_id')->nullable()->after('event_id')->constrained('pools')->nullOnDelete();
            $table->unsignedTinyInteger('round')->nullable()->after('pool_id');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['pool_id']);
            $table->dropColumn(['pool_id', 'round']);
        });
    }
};
