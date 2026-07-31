<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('participants', function (Blueprint $table) {
            if (Schema::hasColumn('participants', 'identification_number')) {
                $table->dropColumn('identification_number');
            }
            if (Schema::hasColumn('participants', 'gender')) {
                $table->dropColumn('gender');
            }
            if (Schema::hasColumn('participants', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        Schema::table('participants', function (Blueprint $table) {
            $table->string('identification_number')->nullable()->index();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
        });
    }
};
