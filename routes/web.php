<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/health', \App\Http\Controllers\HealthCheckController::class);

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Faculty Dashboard (for faculty-representative role)
    Route::get('/faculty', [\App\Http\Controllers\FacultyDashboardController::class, 'index'])->name('faculty.dashboard');
    Route::post('/faculty/squad', [\App\Http\Controllers\FacultyDashboardController::class, 'storeSquad'])->name('faculty.squad.store');
    Route::post('/faculty/squad/import', [\App\Http\Controllers\FacultyDashboardController::class, 'importSquad'])->name('faculty.squad.import');
    Route::get('/faculty/squad/template', [\App\Http\Controllers\FacultyDashboardController::class, 'downloadTemplate'])->name('faculty.squad.template');
    Route::delete('/faculty/squad/{squadMember}', [\App\Http\Controllers\FacultyDashboardController::class, 'destroySquad'])->name('faculty.squad.destroy');

    // Dean Verification Dashboard
    Route::get('/dean', [\App\Http\Controllers\DeanVerificationController::class, 'index'])->name('dean.dashboard');
    Route::post('/dean/approve/{eventParticipant}', [\App\Http\Controllers\DeanVerificationController::class, 'approve'])->name('dean.approve');
    Route::post('/dean/reject/{eventParticipant}', [\App\Http\Controllers\DeanVerificationController::class, 'reject'])->name('dean.reject');

    // M1: Organization foundation (see CURRENT_STATE.md + docs)
    Route::get('/organizations', [\App\Http\Controllers\OrganizationController::class, 'index'])->name('organizations.index');
    Route::post('/organizations', [\App\Http\Controllers\OrganizationController::class, 'store'])->name('organizations.store');
    Route::put('/organizations/{organization}', [\App\Http\Controllers\OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('/organizations/{organization}', [\App\Http\Controllers\OrganizationController::class, 'destroy'])->name('organizations.destroy');

    // M1: User Management + RBAC
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update');
    Route::put('/users/{user}/reset-password', [\App\Http\Controllers\UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::delete('/users/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');

    // Roles & Permissions
    Route::get('/roles', [\App\Http\Controllers\RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [\App\Http\Controllers\RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [\App\Http\Controllers\RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [\App\Http\Controllers\RoleController::class, 'destroy'])->name('roles.destroy');

    // M2: Sport (core, complete CRUD)
    Route::get('/sports', [\App\Http\Controllers\SportController::class, 'index'])->name('sports.index');
    Route::post('/sports', [\App\Http\Controllers\SportController::class, 'store'])->name('sports.store');
    Route::put('/sports/{sport}', [\App\Http\Controllers\SportController::class, 'update'])->name('sports.update');
    Route::delete('/sports/{sport}', [\App\Http\Controllers\SportController::class, 'destroy'])->name('sports.destroy');

    // M2: SportCategory (basic per-sport management)
    Route::get('/sport-categories', [\App\Http\Controllers\SportCategoryController::class, 'index'])->name('sport-categories.index');
    Route::post('/sport-categories', [\App\Http\Controllers\SportCategoryController::class, 'store'])->name('sport-categories.store');
    Route::put('/sport-categories/{sportCategory}', [\App\Http\Controllers\SportCategoryController::class, 'update'])->name('sport-categories.update');
    Route::delete('/sport-categories/{sportCategory}', [\App\Http\Controllers\SportCategoryController::class, 'destroy'])->name('sport-categories.destroy');

    // M2: Session Management
    Route::get('/sessions', [\App\Http\Controllers\SessionController::class, 'index'])->name('sessions.index');
    Route::post('/sessions', [\App\Http\Controllers\SessionController::class, 'store'])->name('sessions.store');
    Route::put('/sessions/{session}', [\App\Http\Controllers\SessionController::class, 'update'])->name('sessions.update');
    Route::delete('/sessions/{session}', [\App\Http\Controllers\SessionController::class, 'destroy'])->name('sessions.destroy');

    // M2: Tournament (Basic)
    Route::get('/tournaments', [\App\Http\Controllers\TournamentController::class, 'index'])->name('tournaments.index');
    Route::post('/tournaments', [\App\Http\Controllers\TournamentController::class, 'store'])->name('tournaments.store');
    Route::put('/tournaments/{tournament}', [\App\Http\Controllers\TournamentController::class, 'update'])->name('tournaments.update');
    Route::delete('/tournaments/{tournament}', [\App\Http\Controllers\TournamentController::class, 'destroy'])->name('tournaments.destroy');
    Route::post('/tournaments/{tournament}/generate-events', [\App\Http\Controllers\TournamentController::class, 'generateEvents'])->name('tournaments.generate-events');

    // M2: Event (completion of core hierarchy)
    Route::get('/events', [\App\Http\Controllers\EventController::class, 'index'])->name('events.index');
    Route::post('/events', [\App\Http\Controllers\EventController::class, 'store'])->name('events.store');
    Route::put('/events/{event}', [\App\Http\Controllers\EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [\App\Http\Controllers\EventController::class, 'destroy'])->name('events.destroy');
    Route::get('/events/{event}/draw-result', [\App\Http\Controllers\DrawController::class, 'show'])->name('events.draw-result');
    Route::post('/events/{event}/draw', [\App\Http\Controllers\DrawController::class, 'draw'])->name('events.draw');
    Route::post('/events/{event}/draw/move-participant', [\App\Http\Controllers\DrawController::class, 'moveParticipant'])->name('events.draw.move-participant');
    Route::post('/events/batch-delete', [\App\Http\Controllers\EventController::class, 'batchDestroy'])->name('events.batch-destroy');

    // Participant Dashboard (consolidated overview)
    Route::get('/participant-dashboard', [\App\Http\Controllers\ParticipantDashboardController::class, 'index'])->name('participant-dashboard.index');

    // M3: Participant & Registration
    Route::get('/participants', [\App\Http\Controllers\ParticipantController::class, 'index'])->name('participants.index');
    Route::post('/participants', [\App\Http\Controllers\ParticipantController::class, 'store'])->name('participants.store');
    Route::put('/participants/{participant}', [\App\Http\Controllers\ParticipantController::class, 'update'])->name('participants.update');
    Route::delete('/participants/{participant}', [\App\Http\Controllers\ParticipantController::class, 'destroy'])->name('participants.destroy');

    Route::get('/registrations', [\App\Http\Controllers\RegistrationController::class, 'index'])->name('registrations.index');
    Route::post('/registrations', [\App\Http\Controllers\RegistrationController::class, 'store'])->name('registrations.store');
    Route::put('/registrations/{registration}', [\App\Http\Controllers\RegistrationController::class, 'update'])->name('registrations.update');
    Route::delete('/registrations/{registration}', [\App\Http\Controllers\RegistrationController::class, 'destroy'])->name('registrations.destroy');

    Route::get('/event-participants', [\App\Http\Controllers\EventParticipantController::class, 'index'])->name('event-participants.index');
    Route::post('/event-participants', [\App\Http\Controllers\EventParticipantController::class, 'store'])->name('event-participants.store');
    Route::delete('/event-participants/{eventParticipant}', [\App\Http\Controllers\EventParticipantController::class, 'destroy'])->name('event-participants.destroy');

    // M4: Match Scheduling
    Route::get('/matches', [\App\Http\Controllers\MatchController::class, 'index'])->name('matches.index');
    Route::post('/matches', [\App\Http\Controllers\MatchController::class, 'store'])->name('matches.store');
    Route::put('/matches/{match}', [\App\Http\Controllers\MatchController::class, 'update'])->name('matches.update');
    Route::delete('/matches/{match}', [\App\Http\Controllers\MatchController::class, 'destroy'])->name('matches.destroy');

    // M4: Result Entry
    Route::get('/results', [\App\Http\Controllers\ResultController::class, 'index'])->name('results.index');
    Route::post('/results', [\App\Http\Controllers\ResultController::class, 'store'])->name('results.store');
    Route::put('/results/{result}', [\App\Http\Controllers\ResultController::class, 'update'])->name('results.update');
    Route::delete('/results/{result}', [\App\Http\Controllers\ResultController::class, 'destroy'])->name('results.destroy');

    // M5: Rankings
    Route::get('/rankings', [\App\Http\Controllers\RankingController::class, 'index'])->name('rankings.index');
    Route::put('/rankings/{tournament}/strategy', [\App\Http\Controllers\RankingController::class, 'updateStrategy'])->name('rankings.updateStrategy');

    // M6: Exports
    Route::get('/exports/fixtures/pdf', [\App\Http\Controllers\ExportController::class, 'fixturesPdf'])->name('exports.fixtures.pdf');
    Route::get('/exports/fixtures/excel', [\App\Http\Controllers\ExportController::class, 'fixturesExcel'])->name('exports.fixtures.excel');
    Route::get('/exports/results/pdf', [\App\Http\Controllers\ExportController::class, 'resultsPdf'])->name('exports.results.pdf');
    Route::get('/exports/results/excel', [\App\Http\Controllers\ExportController::class, 'resultsExcel'])->name('exports.results.excel');
    Route::get('/exports/rankings/{tournament}/pdf', [\App\Http\Controllers\ExportController::class, 'rankingsPdf'])->name('exports.rankings.pdf');
    Route::get('/exports/rankings/{tournament}/excel', [\App\Http\Controllers\ExportController::class, 'rankingsExcel'])->name('exports.rankings.excel');
    Route::get('/exports/match-sheet/{fixture}', [\App\Http\Controllers\ExportController::class, 'matchSheet'])->name('exports.matchSheet');

    // M6: Reporting Dashboard
    Route::get('/reports', [\App\Http\Controllers\ReportingController::class, 'index'])->name('reports.index');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');

    // Activity Log
    Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
});

require __DIR__.'/auth.php';
