<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('participants', 'logo_path')) {
            return;
        }

        Schema::table('participants', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
