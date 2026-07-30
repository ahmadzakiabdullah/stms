<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE activity_log MODIFY subject_id VARCHAR(36) NULL');
            DB::statement('ALTER TABLE activity_log MODIFY causer_id VARCHAR(36) NULL');
        }
    }
};
