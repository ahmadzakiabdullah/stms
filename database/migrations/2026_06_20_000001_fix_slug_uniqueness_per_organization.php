<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix slug uniqueness to be per-organization for multi-tenancy.
     * Previously slugs were globally unique, which breaks when multiple
     * organizations use the same sport/session/tournament names.
     */
    public function up(): void
    {
        // Sports: organization_id + slug must be unique
        if (Schema::hasTable('sports')) {
            Schema::table('sports', function (Blueprint $table) {
                // Drop old global unique safely (without Doctrine, which is removed in newer Laravel)
                try {
                    $table->dropUnique('sports_slug_unique');
                } catch (\Throwable $e) {
                    // Index may not exist or already dropped — safe to ignore
                }

                // Add new composite unique per organization
                $table->unique(['organization_id', 'slug'], 'sports_org_slug_unique');
            });
        }

        // Event Sessions (domain sessions table)
        $sessionTable = Schema::hasTable('event_sessions') ? 'event_sessions' : 'sessions';

        if (Schema::hasTable($sessionTable)) {
            Schema::table($sessionTable, function (Blueprint $table) use ($sessionTable) {
                try {
                    $table->dropUnique($sessionTable . '_slug_unique');
                } catch (\Throwable $e) {
                    // ignore if not exists
                }

                $table->unique(['organization_id', 'slug'], $sessionTable . '_org_slug_unique');
            });
        }

        // Tournaments
        if (Schema::hasTable('tournaments')) {
            Schema::table('tournaments', function (Blueprint $table) {
                try {
                    $table->dropUnique('tournaments_slug_unique');
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->unique(['organization_id', 'slug'], 'tournaments_org_slug_unique');
            });
        }

        // Events - scope by organization + slug
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                try {
                    $table->dropUnique('events_slug_unique');
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->unique(['organization_id', 'slug'], 'events_org_slug_unique');
            });
        }
    }

    public function down(): void
    {
        // Revert is intentionally conservative — we don't want to accidentally re-introduce global unique constraints
        // in a way that breaks existing data. Manual intervention may be required on rollback.
    }
};
