<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sport_categories', function (Blueprint $table) {
            $table->foreignUuid('organization_id')->after('id')->constrained('organizations')->cascadeOnDelete();
            $table->softDeletes()->after('updated_at');

            // Make slug unique within a sport (composite consideration)
            $table->unique(['sport_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sport_categories', function (Blueprint $table) {
            $table->dropUnique(['sport_id', 'slug']);
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
