<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table): void {
            $table->string('status', 20)->default('approved')->index()->after('notes');
            $table->foreignUuid('submitted_by')->nullable()->after('status')->constrained('users', 'uuid')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->foreignUuid('approved_by')->nullable()->after('submitted_at')->constrained('users', 'uuid')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignUuid('locked_by')->nullable()->after('approved_at')->constrained('users', 'uuid')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('locked_by');
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table): void {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['locked_by']);
            $table->dropColumn([
                'status',
                'submitted_by',
                'submitted_at',
                'approved_by',
                'approved_at',
                'locked_by',
                'locked_at',
            ]);
        });
    }
};
