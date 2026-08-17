# Internationalization (i18n)

STMS supports English (`en`) and Bahasa Malaysia (`ms`), with English as the configured default/fallback.

## Implementation

- Supported locales and defaults: `config/app.php`.
- Request locale: `SetLocale` middleware.
- Persistence: session plus root-path `app_locale` cookie through `POST /locale`.
- Shared props: `locale` and `locales`.
- Frontend dictionary/helpers: `resources/js/lib/i18n.ts`.
- Locale switcher: guest, public and authenticated layouts.
- Formatters use Malaysian locale tags `en-MY` / `ms-MY`.

Translation coverage is strongest on authentication, shared navigation, dashboard, settings, events, matches, results and the public portal. User-authored domain names/content are intentionally not machine-translated.

## Limitations

- Coverage is not complete on every administrative sentence/error.
- RTL is not implemented.
- Locale endpoint is intentionally CSRF-exempt to recover legacy subfolder cookie-path deployments; it accepts only the configured locale allowlist and changes a non-sensitive preference.
