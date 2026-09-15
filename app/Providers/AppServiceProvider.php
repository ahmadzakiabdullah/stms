<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');
        $this->app['request']->setTrustedProxies(['*'], $this->app['request']::HEADER_X_FORWARDED_FOR | $this->app['request']::HEADER_X_FORWARDED_HOST | $this->app['request']::HEADER_X_FORWARDED_PORT | $this->app['request']::HEADER_X_FORWARDED_PROTO);

        Vite::prefetch(concurrency: 3);

        // M1: Explicitly register Organization policy (auto-discovery also works in modern Laravel)
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Organization::class, \App\Policies\OrganizationPolicy::class);

        // M2: Sport policy
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Sport::class, \App\Policies\SportPolicy::class);

        // M2: SportCategory policy
        \Illuminate\Support\Facades\Gate::policy(\App\Models\SportCategory::class, \App\Policies\SportCategoryPolicy::class);

        // M2: Session policy
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Session::class, \App\Policies\SessionPolicy::class);

        // M2: Tournament policy
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Tournament::class, \App\Policies\TournamentPolicy::class);

        // Event policy (M2 completion)
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Event::class, \App\Policies\EventPolicy::class);

        // M4: Match policy
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Fixture::class, \App\Policies\MatchPolicy::class);

        // M4: Result policy
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Result::class, \App\Policies\ResultPolicy::class);

        // EventParticipant policy
        \Illuminate\Support\Facades\Gate::policy(\App\Models\EventParticipant::class, \App\Policies\EventParticipantPolicy::class);

        // Role policy
        \Illuminate\Support\Facades\Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
    }
}
