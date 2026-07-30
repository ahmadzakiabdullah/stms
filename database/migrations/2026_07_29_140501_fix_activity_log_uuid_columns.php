<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE activity_log MODIFY subject_id VARCHAR(36) NULL');
        DB::statement('ALTER TABLE activity_log MODIFY causer_id VARCHAR(36) NULL');
    }
};
