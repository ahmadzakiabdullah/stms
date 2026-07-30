<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\TournamentController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\MatchController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public/Guest routes
    Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');

    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

    Route::get('/matches', [MatchController::class, 'index'])->name('matches.index');
    Route::get('/matches/{match}', [MatchController::class, 'show'])->name('matches.show');

    // Protected routes (require token)
    Route::middleware('auth:sanctum')->group(function () {
        // We'll keep it simple for now and allow authenticated users to view protected info if any
    });

    // AI Assistant Endpoints
    Route::post('/events/{event}/ai/optimize-schedule', [\App\Http\Controllers\Api\V1\AIAssistantController::class, 'optimizeSchedule'])->name('ai.optimize');
    Route::get('/matches/{match}/ai/predict', [\App\Http\Controllers\Api\V1\AIAssistantController::class, 'predictMatch'])->name('ai.predict');
});
