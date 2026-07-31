<?php

use App\Models\Organization;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

Artisan::call('migrate:fresh');

// Use memory sqlite for speed? The default is whatever is in .env. We should use it.
// Actually, let's just create some dummy data.

DB::transaction(function () {
    // Clear out related tables for clean test
    // Not actually necessary if we use fresh models.
    $org = Organization::factory()->create();
    $session = Session::factory()->create(['organization_id' => $org->id]);

    $tournament = Tournament::factory()->create([
        'organization_id' => $org->id,
        'session_id' => $session->id,
    ]);

    $sports = Sport::factory()->count(10)->create(['organization_id' => $org->id]);
    $tournament->sports()->sync($sports->pluck('id')->toArray());

    foreach ($sports as $sport) {
        $categories = SportCategory::factory()->count(20)->create([
            'organization_id' => $org->id,
            'sport_id' => $sport->id,
        ]);
    }

    $service = new TournamentService;

    // First run - creates 200 events
    $service->generateEventsFromCategories($tournament);

    // Second run - checks 200 existing events (this is where the N+1 is)
    DB::enableQueryLog();

    $start = microtime(true);
    $service->generateEventsFromCategories($tournament);
    $end = microtime(true);

    $queries = DB::getQueryLog();

    echo 'Time: '.round(($end - $start) * 1000, 2)."ms\n";
    echo 'Queries: '.count($queries)."\n";

    DB::rollBack();
});
