<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Result;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use App\Policies\ActivityLogPolicy;
use App\Policies\DashboardPolicy;
use App\Policies\DeanVerificationPolicy;
use App\Policies\EventParticipantPolicy;
use App\Policies\EventPolicy;
use App\Policies\ExportPolicy;
use App\Policies\MatchPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\ParticipationConfirmationPolicy;
use App\Policies\RankingPolicy;
use App\Policies\ReportingPolicy;
use App\Policies\ResultPolicy;
use App\Policies\RolePolicy;
use App\Policies\SessionPolicy;
use App\Policies\SettingPolicy;
use App\Policies\SportCategoryPolicy;
use App\Policies\SportPolicy;
use App\Policies\TournamentPolicy;
use App\Services\TenantContext;
use App\Support\ProductionConfiguration;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // A fresh instance is created for every request/job lifecycle, including
        // long-running Octane and queue workers.
        $this->app->scoped(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            if (config('app.production_config_enforce')) {
                ProductionConfiguration::validate();
            }
            URL::forceRootUrl(rtrim((string) config('app.url'), '/'));
            URL::forceScheme('https');
        }
        $trustedProxies = config('app.trusted_proxies', []);

        if ($trustedProxies !== []) {
            $this->app['request']->setTrustedProxies(
                $trustedProxies,
                $this->app['request']::HEADER_X_FORWARDED_FOR
                    | $this->app['request']::HEADER_X_FORWARDED_HOST
                    | $this->app['request']::HEADER_X_FORWARDED_PORT
                    | $this->app['request']::HEADER_X_FORWARDED_PROTO
            );
        }

        // M1: Explicitly register Organization policy (auto-discovery also works in modern Laravel)
        Gate::policy(Organization::class, OrganizationPolicy::class);

        // M2: Sport policy
        Gate::policy(Sport::class, SportPolicy::class);

        // M2: SportCategory policy
        Gate::policy(SportCategory::class, SportCategoryPolicy::class);

        // M2: Session policy
        Gate::policy(Session::class, SessionPolicy::class);

        // M2: Tournament policy
        Gate::policy(Tournament::class, TournamentPolicy::class);

        // Event policy (M2 completion)
        Gate::policy(Event::class, EventPolicy::class);

        // M4: Match policy
        Gate::policy(Fixture::class, MatchPolicy::class);

        // M4: Result policy
        Gate::policy(Result::class, ResultPolicy::class);

        // EventParticipant policy
        Gate::policy(EventParticipant::class, EventParticipantPolicy::class);

        // Role policy
        Gate::policy(Role::class, RolePolicy::class);

        // Dashboard / activity log / exports / reports / settings / dean verification
        Gate::define('view-dashboard', [DashboardPolicy::class, 'viewAny']);
        Gate::define('view-activity-logs', [ActivityLogPolicy::class, 'viewAny']);
        Gate::define('export-data', [ExportPolicy::class, 'viewAny']);
        Gate::define('view-reports', [ReportingPolicy::class, 'viewAny']);
        Gate::define('view-participation-confirmations', [ParticipationConfirmationPolicy::class, 'viewAny']);
        Gate::define('view-rankings', [RankingPolicy::class, 'viewAny']);
        Gate::define('view-settings', [SettingPolicy::class, 'viewAny']);
        Gate::define('update-settings', [SettingPolicy::class, 'update']);
        Gate::define('view-dean-dashboard', [DeanVerificationPolicy::class, 'viewAny']);
        Gate::define('verify-registration', [DeanVerificationPolicy::class, 'verify']);
    }
}
