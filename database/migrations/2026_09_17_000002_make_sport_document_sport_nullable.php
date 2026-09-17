<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_documents', fn (Blueprint $table) => $table->foreignUuid('sport_id')->nullable()->change());
    }

    public function down(): void
    {
        Schema::table('sport_documents', fn (Blueprint $table) => $table->foreignUuid('sport_id')->nullable(false)->change());
    }
};
