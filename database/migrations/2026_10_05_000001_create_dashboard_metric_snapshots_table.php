<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->string('scope_key')->default('global');
            $table->unsignedInteger('organizations')->default(0);
            $table->unsignedInteger('users')->default(0);
            $table->unsignedInteger('events')->default(0);
            $table->unsignedInteger('matches')->default(0);
            $table->timestamps();
            $table->unique(['snapshot_date', 'scope_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_metric_snapshots');
    }
};
