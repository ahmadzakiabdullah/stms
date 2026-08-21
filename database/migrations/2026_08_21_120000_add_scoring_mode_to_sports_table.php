<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->string('scoring_mode')->default('none')->after('icon');
        });

        DB::table('sports')
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) like ?', ['%hockey%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%football%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%soccer%']);
            })
            ->update(['scoring_mode' => 'individual']);
    }

    public function down(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->dropColumn('scoring_mode');
        });
    }
};
