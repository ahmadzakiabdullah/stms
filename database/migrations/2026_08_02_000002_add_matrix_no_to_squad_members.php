<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('squad_members', function (Blueprint $table) {
            $table->string('matrix_no', 20)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('squad_members', function (Blueprint $table) {
            $table->dropColumn('matrix_no');
        });
    }
};
