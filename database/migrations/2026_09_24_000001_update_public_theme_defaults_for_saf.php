<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacy = [
            'public_theme_dark' => '#071B33',
            'public_theme_primary' => '#0057A8',
            'public_theme_accent' => '#20B8E6',
            'public_theme_highlight' => '#F4B942',
            'public_theme_background' => '#F4F7FA',
            'public_theme_text' => '#102A43',
        ];

        $current = [
            'public_theme_dark' => '#09091A',
            'public_theme_primary' => '#3020A8',
            'public_theme_accent' => '#F21D32',
            'public_theme_highlight' => '#FF8614',
            'public_theme_background' => '#F7F6FC',
            'public_theme_text' => '#17132F',
        ];

        foreach ($legacy as $key => $value) {
            DB::table('settings')
                ->where('key', $key)
                ->where('value', $value)
                ->update(['value' => $current[$key], 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $legacy = [
            'public_theme_dark' => '#071B33',
            'public_theme_primary' => '#0057A8',
            'public_theme_accent' => '#20B8E6',
            'public_theme_highlight' => '#F4B942',
            'public_theme_background' => '#F4F7FA',
            'public_theme_text' => '#102A43',
        ];

        $current = [
            'public_theme_dark' => '#09091A',
            'public_theme_primary' => '#3020A8',
            'public_theme_accent' => '#F21D32',
            'public_theme_highlight' => '#FF8614',
            'public_theme_background' => '#F7F6FC',
            'public_theme_text' => '#17132F',
        ];

        foreach ($current as $key => $value) {
            DB::table('settings')
                ->where('key', $key)
                ->where('value', $value)
                ->update(['value' => $legacy[$key], 'updated_at' => now()]);
        }
    }
};
