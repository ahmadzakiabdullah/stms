<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ config('app.description') }}">
        <link rel="canonical" href="{{ url()->current() }}">

        @php
            $brandingOrganizationId = auth()->user()?->organization_id;

            if (! $brandingOrganizationId && config('app.public_org_slug')) {
                $brandingOrganizationId = \App\Models\Organization::query()
                    ->where('slug', config('app.public_org_slug'))
                    ->where('is_active', true)
                    ->value('id');
            }

            $favicon = $brandingOrganizationId
                ? \App\Models\Setting::query()
                    ->where('organization_id', $brandingOrganizationId)
                    ->where('key', 'favicon_url')
                    ->value('value')
                : null;
        @endphp
        <link rel="icon" href="{{ $favicon ?: asset('favicon.ico') }}">
        <title inertia>{{ config('app.name', 'STMS Portal') }}</title>

        <!-- Scripts -->
        {{--
            Inertia login redirects can transition from the guest page to an
            authenticated page without a full document reload. Keep the
            complete Ziggy map available so the route helper does not retain
            the guest-only map after that transition; authorization remains
            enforced by Laravel middleware and policies.
        --}}
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
