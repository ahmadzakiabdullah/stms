<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_categories', function (Blueprint $table) {
            $table->unsignedInteger('max_male_athletes')->nullable()->after('slug');
            $table->unsignedInteger('max_female_athletes')->nullable()->after('max_male_athletes');
            $table->unsignedInteger('max_officials')->nullable()->after('max_female_athletes');
        });
    }

    public function down(): void
    {
        Schema::table('sport_categories', function (Blueprint $table) {
            $table->dropColumn(['max_male_athletes', 'max_female_athletes', 'max_officials']);
        });
    }
};
