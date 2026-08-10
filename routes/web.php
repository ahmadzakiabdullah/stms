<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeanVerificationController;
use App\Http\Controllers\DrawController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventParticipantController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FacultyDashboardController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Middleware\HealthEndpointToken;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ParticipationConfirmationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPortalController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SportCategoryController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\TeamRegistrationFormController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthCheckController::class)
    ->middleware(HealthEndpointToken::class);

Route::get('/', PublicPortalController::class)->name('public.index');
Route::redirect('/index.php', '/portal/', 301);
Route::post('/locale', function (Request $request) {
    $supportedLocales = config('app.supported_locales', []);
    if (! is_array($supportedLocales) || $supportedLocales === []) {
        $supportedLocales = ['en'];
    }

    $validated = $request->validate([
        'locale' => ['required', 'string', 'in:'.implode(',', $supportedLocales)],
    ]);

    $request->session()->put('locale', $validated['locale']);

    return back();
})->name('locale.update');
// IIS includes the /portal mount point in REQUEST_URI. Laravel normalizes both
// /portal and /portal/ to this route, so redirecting it to APP_URL (/portal)
// creates a self-redirect in production.
Route::get('/portal', PublicPortalController::class);

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(config('app.email_verification_required') ? ['auth', 'verified'] : ['auth'])
    ->name('dashboard');

// Profile remains available for unverified users so they can correct their email.
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

// All operational and administrative functions require a verified email.
Route::middleware(config('app.email_verification_required') ? ['auth', 'verified'] : ['auth'])->group(function () {
        // Faculty Dashboard is merged into the main dashboard (see DashboardController)
        Route::redirect('/faculty', '/dashboard');

        // Faculty squad management (faculty-representative role)
        Route::post('/faculty/squad', [FacultyDashboardController::class, 'storeSquad'])->name('faculty.squad.store');
        Route::post('/faculty/squad/import', [FacultyDashboardController::class, 'importSquad'])->name('faculty.squad.import');
        Route::get('/faculty/squad/template', [FacultyDashboardController::class, 'downloadTemplate'])->name('faculty.squad.template');
        Route::delete('/faculty/squad/{squadMember}', [FacultyDashboardController::class, 'destroySquad'])->name('faculty.squad.destroy');

        // Dean Verification Dashboard (rate limited for approvals)
        Route::get('/dean', [DeanVerificationController::class, 'index'])->name('dean.dashboard');
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('/dean/approve/{eventParticipant}', [DeanVerificationController::class, 'approve'])->name('dean.approve');
            Route::post('/dean/reject/{eventParticipant}', [DeanVerificationController::class, 'reject'])->name('dean.reject');
        });

        // M1: Organization foundation (see CURRENT_STATE.md + docs)
        Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
        Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');

        // M1: User Management + RBAC
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::put('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Roles & Permissions
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // M2: Sport (core, complete CRUD)
        Route::get('/sports', [SportController::class, 'index'])->name('sports.index');
        Route::post('/sports', [SportController::class, 'store'])->name('sports.store');
        Route::put('/sports/{sport}', [SportController::class, 'update'])->name('sports.update');
        Route::delete('/sports/{sport}', [SportController::class, 'destroy'])->name('sports.destroy');

        // M2: SportCategory (basic per-sport management)
        Route::get('/sport-categories', [SportCategoryController::class, 'index'])->name('sport-categories.index');
        Route::post('/sport-categories', [SportCategoryController::class, 'store'])->name('sport-categories.store');
        Route::put('/sport-categories/{sportCategory}', [SportCategoryController::class, 'update'])->name('sport-categories.update');
        Route::delete('/sport-categories/{sportCategory}', [SportCategoryController::class, 'destroy'])->name('sport-categories.destroy');

        // M2: Session Management
        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
        Route::put('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
        Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');

        // M2: Tournament (Basic)
        Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
        Route::post('/tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
        Route::put('/tournaments/{tournament}', [TournamentController::class, 'update'])->name('tournaments.update');
        Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy'])->name('tournaments.destroy');
        Route::post('/tournaments/{tournament}/generate-events', [TournamentController::class, 'generateEvents'])->name('tournaments.generate-events');

        // M2: Event (completion of core hierarchy)
        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
        Route::get('/events/{event}/draw-result', [DrawController::class, 'show'])->name('events.draw-result');
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/events/{event}/draw', [DrawController::class, 'draw'])->name('events.draw');
            Route::post('/events/{event}/draw/generate-fixtures', [DrawController::class, 'generateFixtures'])->name('events.generate-fixtures');
            Route::post('/events/{event}/draw/reset', [DrawController::class, 'resetDraw'])->name('events.reset-draw');
            Route::post('/events/{event}/draw/versions/{drawVersion}/rollback', [DrawController::class, 'rollback'])->name('events.draw.rollback');
            Route::post('/events/{event}/draw/move-participant', [DrawController::class, 'moveParticipant'])->name('events.draw.move-participant');
            Route::post('/events/batch-delete', [EventController::class, 'batchDestroy'])->name('events.batch-destroy');
        });

        // M3: Participant & Registration
        Route::get('/participants', [ParticipantController::class, 'index'])->name('participants.index');
        Route::post('/participants', [ParticipantController::class, 'store'])->name('participants.store');
        Route::put('/participants/{participant}', [ParticipantController::class, 'update'])->name('participants.update');
        Route::delete('/participants/{participant}', [ParticipantController::class, 'destroy'])->name('participants.destroy');

        Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
        Route::post('/registrations', [RegistrationController::class, 'store'])->name('registrations.store');
        Route::put('/registrations/{registration}', [RegistrationController::class, 'update'])->name('registrations.update');
        Route::delete('/registrations/{registration}', [RegistrationController::class, 'destroy'])->name('registrations.destroy');

        Route::get('/event-participants', [EventParticipantController::class, 'index'])->name('event-participants.index');
        Route::get('/participation-confirmations', [ParticipationConfirmationController::class, 'index'])->name('participation-confirmations.index');
        Route::get('/event-participants/{eventParticipant}/team-form', [TeamRegistrationFormController::class, 'show'])->name('event-participants.team-form');
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('/event-participants', [EventParticipantController::class, 'store'])->name('event-participants.store');
            Route::post('/dashboard/registrations', [EventParticipantController::class, 'storeBatch'])->name('event-participants.store-batch');
            Route::patch('/event-participants/{eventParticipant}/status', [EventParticipantController::class, 'updateStatus'])->name('event-participants.status');
            Route::delete('/event-participants/{eventParticipant}', [EventParticipantController::class, 'destroy'])->name('event-participants.destroy');
            Route::post('/event-participants/{eventParticipant}/squad', [EventParticipantController::class, 'storeSquad'])->name('event-participants.squad.store');
            Route::put('/event-participants/{eventParticipant}/squad/{squadMember}', [EventParticipantController::class, 'updateSquad'])->name('event-participants.squad.update');
            Route::delete('/event-participants/{eventParticipant}/squad/{squadMember}', [EventParticipantController::class, 'destroySquad'])->name('event-participants.squad.destroy');
        });

        // M4: Match Scheduling (rate limited for mutations)
        Route::get('/matches', [MatchController::class, 'index'])->name('matches.index');
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('/matches', [MatchController::class, 'store'])->name('matches.store');
            Route::put('/matches/{match}', [MatchController::class, 'update'])->name('matches.update');
            Route::delete('/matches/{match}', [MatchController::class, 'destroy'])->name('matches.destroy');
            Route::post('/matches/{event}/generate-knockout', [MatchController::class, 'generateKnockout'])->name('matches.generate-knockout');
        });

        // M4: Result Entry (rate limited for mutations)
        Route::get('/results', [ResultController::class, 'index'])->name('results.index');
        Route::middleware('throttle:30,1')->group(function () {
            Route::post('/results', [ResultController::class, 'store'])->name('results.store');
            Route::put('/results/{result}', [ResultController::class, 'update'])->name('results.update');
            Route::delete('/results/{result}', [ResultController::class, 'destroy'])->name('results.destroy');
        });

        // M5: Rankings (rate limited for strategy updates)
        Route::get('/rankings', [RankingController::class, 'index'])->name('rankings.index');
        Route::middleware('throttle:10,1')->group(function () {
            Route::put('/rankings/{tournament}/strategy', [RankingController::class, 'updateStrategy'])->name('rankings.updateStrategy');
            Route::put('/rankings/session/{session}/strategy', [RankingController::class, 'updateSessionStrategy'])->name('rankings.updateSessionStrategy');
        });

        // M6: Exports (rate limited — resource intensive)
        Route::middleware('throttle:10,1')->group(function () {
            Route::get('/exports/fixtures/pdf', [ExportController::class, 'fixturesPdf'])->name('exports.fixtures.pdf');
            Route::get('/exports/fixtures/excel', [ExportController::class, 'fixturesExcel'])->name('exports.fixtures.excel');
            Route::get('/exports/results/pdf', [ExportController::class, 'resultsPdf'])->name('exports.results.pdf');
            Route::get('/exports/results/excel', [ExportController::class, 'resultsExcel'])->name('exports.results.excel');
            Route::get('/exports/rankings/{tournament}/pdf', [ExportController::class, 'rankingsPdf'])->name('exports.rankings.pdf');
            Route::get('/exports/rankings/{tournament}/excel', [ExportController::class, 'rankingsExcel'])->name('exports.rankings.excel');
            Route::get('/exports/match-sheet/{fixture}', [ExportController::class, 'matchSheet'])->name('exports.matchSheet');
        });

        // M6: Reporting Dashboard
        Route::get('/reports', [ReportingController::class, 'index'])->name('reports.index');

        // Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

        // Activity Log
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

});

Route::middleware(config('app.email_verification_required') ? ['auth', 'verified'] : ['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
});

require __DIR__.'/auth.php';
