<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('events')) {
            try {
                Schema::table('events', function (Blueprint $table) {
                    $table->dropUnique('events_slug_unique');
                });
            } catch (Throwable $e) {
                // Index may not exist — safe to ignore
            }

            try {
                Schema::table('events', function (Blueprint $table) {
                    $table->dropIndex('events_slug_index');
                });
            } catch (Throwable $e) {
                // Index may not exist — safe to ignore
            }

            try {
                Schema::table('events', function (Blueprint $table) {
                    $table->unique(['organization_id', 'slug'], 'events_org_slug_unique');
                });
            } catch (Throwable $e) {
                // Index may already exist — safe to ignore
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                try {
                    $table->dropUnique('events_org_slug_unique');
                } catch (Throwable $e) {
                    // ignore
                }

                $table->unique('slug', 'events_slug_unique');
            });
        }
    }
};
