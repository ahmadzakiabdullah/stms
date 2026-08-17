<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('participants', 'inverse_logo_path')) {
            return;
        }

        Schema::table('participants', function (Blueprint $table) {
            $table->string('inverse_logo_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('participants', 'inverse_logo_path')) {
            return;
        }

        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('inverse_logo_path');
        });
    }
};
