<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('squad_members', function (Blueprint $table) {
            $table->enum('role', [
                'athlete_male',
                'athlete_female',
                'assistant_manager',
                'manager',
                'coach',
                'physio',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('squad_members', function (Blueprint $table) {
            $table->enum('role', [
                'athlete_male',
                'athlete_female',
                'manager',
                'coach',
                'physio',
            ])->change();
        });
    }
};
