<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('event_participants', 'organization_id')) {
            Schema::table('event_participants', function (Blueprint $table) {
                $table->uuid('organization_id')->nullable()->after('id');
                $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
                $table->index('organization_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
