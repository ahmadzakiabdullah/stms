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
        // Support both old name (during rename) and new name
        $tableName = Schema::hasTable('event_sessions') ? 'event_sessions' : 'sessions';

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (! Schema::hasColumn($tableName, 'organization_id')) {
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete()->after('id');
            }

            if (! Schema::hasColumn($tableName, 'name')) {
                $table->string('name')->nullable()->after('organization_id');
            }

            if (! Schema::hasColumn($tableName, 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn($tableName, 'description')) {
                $table->text('description')->nullable()->after('slug');
            }

            if (! Schema::hasColumn($tableName, 'start_date')) {
                $table->date('start_date')->nullable()->after('description');
            }

            if (! Schema::hasColumn($tableName, 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }

            if (! Schema::hasColumn($tableName, 'is_active')) {
                $table->boolean('is_active')->default(true)->after('end_date');
            }

            if (! Schema::hasColumn($tableName, 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            // We don't drop columns in down to avoid data loss on existing table
        });
    }
};
