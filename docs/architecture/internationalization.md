# Internationalization (i18n)

Internationalization is **planned but not yet implemented**. Laravel's built-in localization system is available and configured in `config/app.php` with `locale` set to `en` by default. The `resources/lang/` (or `lang/` in Laravel 11+) directory structure is in place for language files.

When i18n is activated, all user-facing strings in Blade templates and Inertia page components should use the `__()` helper or `@lang` directive for server-rendered content. For React frontend components, the `react-i18next` or a similar library should be introduced to load translation JSON files. Translation strings should be organized by domain (navigation, tournaments, matches, participants, validation).

The database schema includes a `locale` column on the `users` table and the `organizations` table, enabling per-user and per-tenant locale preferences. Right-to-left (RTL) language support will require additional CSS work with Tailwind's RTL utilities. Implementation of i18n is deferred to a future milestone.
