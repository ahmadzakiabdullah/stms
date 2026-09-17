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
use App\Http\Controllers\MatchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ParticipationConfirmationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPortalController;
use App\Http\Controllers\PublicStorageController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SportCategoryController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\SportDocumentController;
use App\Http\Controllers\SessionDocumentController;
use App\Http\Controllers\TeamRegistrationFormController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\HealthEndpointToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthCheckController::class)
    ->middleware(HealthEndpointToken::class);

// IIS cannot create Laravel's public/storage junction in this deployment.
// Serve only files from the configured public disk, while keeping legacy
// /storage URLs functional.
Route::get('/storage/{path}', PublicStorageController::class)
    ->where('path', '.*')
    ->name('public-storage.show');

// Some IIS deployments send a non-GET method when resolving the application
// directory default document. Keep the clean root URL available while the
// controller remains read-only and only renders the public portal.
Route::any('/', PublicPortalController::class)->name('public.index');
Route::get('/schedule', [PublicPortalController::class, 'schedule'])->name('public.schedule');
Route::get('/athletes', [PublicPortalController::class, 'athletes'])->name('public.athletes');
Route::get('/athletes/{squadMember}', [PublicPortalController::class, 'athlete'])->name('public.athletes.show');
Route::get('/sports', [PublicPortalController::class, 'directory'])->defaults('section', 'sports')->name('public.sports');
Route::get('/faculties', [PublicPortalController::class, 'directory'])->defaults('section', 'faculties')->name('public.faculties');
Route::get('/venues', [PublicPortalController::class, 'directory'])->defaults('section', 'venues')->name('public.venues');
// Matches, results and live all share the same data as the consolidated schedule page.
Route::redirect('/matches', '/schedule', 301)->name('public.matches');
Route::redirect('/results', '/schedule', 301)->name('public.results');
Route::redirect('/live', '/schedule', 301)->name('public.live');
Route::get('/news', [PublicPortalController::class, 'info'])->defaults('section', 'news')->name('public.news');
Route::get('/downloads', [PublicPortalController::class, 'info'])->defaults('section', 'downloads')->name('public.downloads');
Route::get('/faq', [PublicPortalController::class, 'info'])->defaults('section', 'faq')->name('public.faq');
Route::get('/about', [PublicPortalController::class, 'info'])->defaults('section', 'about')->name('public.about');
Route::get('/contact-us', [PublicPortalController::class, 'contact'])->name('public.contact');
Route::get('/sitemap.xml', [PublicPortalController::class, 'sitemap'])->name('public.sitemap');
Route::any('/index.php', static fn () => redirect('/', 301));
Route::post('/locale', function (Request $request) {
    $supportedLocales = config('app.supported_locales', []);
    if (! is_array($supportedLocales) || $supportedLocales === []) {
        $supportedLocales = ['en'];
    }

    $validated = $request->validate([
        'locale' => ['required', 'string', 'in:'.implode(',', $supportedLocales)],
    ]);

    $request->session()->put('locale', $validated['locale']);

    // Use 303 for POST locale changes so Inertia always follows with a GET.
    return redirect()->back(303)->withCookie(cookie('app_locale', $validated['locale'], 60 * 24 * 365, '/'));
})->name('locale.update');
// Legacy /portal mount path — redirect to the canonical root URL.
Route::redirect('/portal', '/', 301);
// Keep /portal/storage serving files directly for legacy URLs and IIS compatibility.
Route::get('/portal/storage/{path}', PublicStorageController::class)
    ->where('path', '.*')
    ->name('legacy-public-storage.show');

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
    Route::get('/manage/sports', [SportController::class, 'index'])->name('sports.index');
    Route::post('/manage/sports', [SportController::class, 'store'])->name('sports.store');
    Route::put('/manage/sports/{sport}', [SportController::class, 'update'])->name('sports.update');
    Route::delete('/manage/sports/{sport}', [SportController::class, 'destroy'])->name('sports.destroy');
    Route::post('/manage/sports/{sport}/documents', [SportDocumentController::class, 'store'])->name('sports.documents.store');
    Route::delete('/manage/sport-documents/{sportDocument}', [SportDocumentController::class, 'destroy'])->name('sports.documents.destroy');

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
    Route::post('/sessions/{session}/documents', [SessionDocumentController::class, 'store'])->name('sessions.documents.store');
    Route::get('/sessions/{session}/documents/available', [SessionDocumentController::class, 'available'])->name('sessions.documents.available');
    Route::post('/sessions/{session}/documents/select', [SessionDocumentController::class, 'select'])->name('sessions.documents.select');
    Route::delete('/manage/session-documents/{sportDocument}', [SessionDocumentController::class, 'destroy'])->name('sessions.documents.destroy');

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
    Route::get('/participants/import/template', [ParticipantController::class, 'downloadImportTemplate'])->name('participants.import.template');
    Route::post('/participants', [ParticipantController::class, 'store'])->name('participants.store');
    Route::put('/participants/{participant}', [ParticipantController::class, 'update'])->name('participants.update');
    Route::delete('/participants/{participant}', [ParticipantController::class, 'destroy'])->name('participants.destroy');
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/participants/import/preview', [ParticipantController::class, 'previewImport'])->name('participants.import.preview');
        Route::post('/participants/import/confirm', [ParticipantController::class, 'confirmImport'])->name('participants.import.confirm');
    });

    Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::post('/registrations', [RegistrationController::class, 'store'])->name('registrations.store');
    Route::put('/registrations/{registration}', [RegistrationController::class, 'update'])->name('registrations.update');
    Route::delete('/registrations/{registration}', [RegistrationController::class, 'destroy'])->name('registrations.destroy');

    Route::get('/event-participants', [EventParticipantController::class, 'index'])->name('event-participants.index');
    Route::get('/faculty/register-events', [EventParticipantController::class, 'registerEvents'])->name('faculty.register-events');
    Route::get('/participation-confirmations', [ParticipationConfirmationController::class, 'index'])->name('participation-confirmations.index');
    Route::get('/event-participants/{eventParticipant}/team-form', [TeamRegistrationFormController::class, 'show'])->name('event-participants.team-form');
    Route::get('/event-participants/import/template', [EventParticipantController::class, 'downloadImportTemplate'])->name('event-participants.import.template');
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/event-participants', [EventParticipantController::class, 'store'])->name('event-participants.store');
        Route::post('/event-participants/import', [EventParticipantController::class, 'import'])->name('event-participants.import');
        Route::post('/event-participants/batch-status', [EventParticipantController::class, 'batchUpdateStatus'])->name('event-participants.batch-status');
        Route::post('/dashboard/registrations', [EventParticipantController::class, 'storeBatch'])->name('event-participants.store-batch');
        Route::patch('/event-participants/{eventParticipant}/status', [EventParticipantController::class, 'updateStatus'])->name('event-participants.status');
        // IIS deployments may reject PATCH before Laravel receives the request.
        // Keep a POST equivalent for method-constrained subfolder hosting.
        Route::post('/event-participants/{eventParticipant}/status', [EventParticipantController::class, 'updateStatus'])->name('event-participants.status-post');
        Route::post('/event-participants/{eventParticipant}/withdraw', [EventParticipantController::class, 'withdraw'])->name('event-participants.withdraw');
        Route::delete('/event-participants/{eventParticipant}', [EventParticipantController::class, 'destroy'])->name('event-participants.destroy');
        Route::post('/event-participants/{eventParticipant}/squad', [EventParticipantController::class, 'storeSquad'])->name('event-participants.squad.store');
        Route::put('/event-participants/{eventParticipant}/squad/{squadMember}', [EventParticipantController::class, 'updateSquad'])->name('event-participants.squad.update');
        Route::delete('/event-participants/{eventParticipant}/squad/{squadMember}', [EventParticipantController::class, 'destroySquad'])->name('event-participants.squad.destroy');
    });

    // M4: Match Scheduling (rate limited for mutations)
    Route::get('/manage/matches', [MatchController::class, 'index'])->name('matches.index');
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/manage/matches', [MatchController::class, 'store'])->name('matches.store');
        Route::put('/manage/matches/{match}', [MatchController::class, 'update'])->name('matches.update');
        Route::delete('/manage/matches/{match}', [MatchController::class, 'destroy'])->name('matches.destroy');
        Route::post('/manage/matches/{event}/generate-knockout', [MatchController::class, 'generateKnockout'])->name('matches.generate-knockout');
    });

    // M4: Result Entry (rate limited for mutations)
    Route::get('/results/manage', [ResultController::class, 'index'])->name('results.index');
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/results', [ResultController::class, 'store'])->name('results.store');
        Route::put('/results/{result}', [ResultController::class, 'update'])->name('results.update');
        Route::delete('/results/{result}', [ResultController::class, 'destroy'])->name('results.destroy');
        Route::post('/results/{result}/submit', [ResultController::class, 'submit'])->name('results.submit');
        Route::post('/results/{result}/approve', [ResultController::class, 'approve'])->name('results.approve');
        Route::post('/results/{result}/lock', [ResultController::class, 'lock'])->name('results.lock');
        Route::post('/results/{result}/unlock', [ResultController::class, 'unlock'])->name('results.unlock');
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
        Route::get('/exports/medals/{session}/pdf', [ExportController::class, 'medalTallyPdf'])->name('exports.medals.pdf');
        Route::get('/exports/medals/{session}/excel', [ExportController::class, 'medalTallyExcel'])->name('exports.medals.excel');
        Route::get('/exports/match-sheet/{fixture}', [ExportController::class, 'matchSheet'])->name('exports.matchSheet');
        Route::get('/exports/result-sheet/{fixture}', [ExportController::class, 'resultSheet'])->name('exports.resultSheet');
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
