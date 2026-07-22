<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_categories', function (Blueprint $table) {
            $table->string('quota_mode')->default('gender_based')->after('slug');
            $table->unsignedInteger('max_athletes_total')->nullable()->after('quota_mode');
            $table->unsignedInteger('min_male_athletes')->nullable()->after('max_female_athletes');
            $table->unsignedInteger('min_female_athletes')->nullable()->after('min_male_athletes');
        });
    }

    public function down(): void
    {
        Schema::table('sport_categories', function (Blueprint $table) {
            $table->dropColumn([
                'quota_mode',
                'max_athletes_total',
                'min_male_athletes',
                'min_female_athletes',
            ]);
        });
    }
};
