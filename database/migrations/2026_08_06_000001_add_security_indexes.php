<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->index(['organization_id', 'event_id', 'status'], 'ep_org_event_status_idx');
            $table->index(['organization_id', 'participant_id', 'status'], 'ep_org_participant_status_idx');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->index(['organization_id', 'event_id', 'stage', 'status'], 'matches_org_event_stage_status_idx');
            $table->index(['organization_id', 'event_id', 'pool_id'], 'matches_org_event_pool_idx');
        });

        Schema::table('results', function (Blueprint $table) {
            $table->index(['organization_id', 'winner_participant_id'], 'results_org_winner_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['organization_id', 'deleted_at'], 'users_org_deleted_idx');
        });

        Schema::table('squad_members', function (Blueprint $table) {
            $table->index(['organization_id', 'event_participant_id', 'role'], 'squad_org_ep_role_idx');
            $table->index(['organization_id', 'role', 'is_active'], 'squad_org_role_active_idx');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id'], 'activity_subject_idx');
            $table->index(['causer_type', 'causer_id'], 'activity_causer_idx');
            $table->index('created_at', 'activity_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropIndex('ep_org_event_status_idx');
            $table->dropIndex('ep_org_participant_status_idx');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('matches_org_event_stage_status_idx');
            $table->dropIndex('matches_org_event_pool_idx');
        });

        Schema::table('results', function (Blueprint $table) {
            $table->dropIndex('results_org_winner_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_org_deleted_idx');
        });

        Schema::table('squad_members', function (Blueprint $table) {
            $table->dropIndex('squad_org_ep_role_idx');
            $table->dropIndex('squad_org_role_active_idx');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_subject_idx');
            $table->dropIndex('activity_causer_idx');
            $table->dropIndex('activity_created_idx');
        });
    }
};
