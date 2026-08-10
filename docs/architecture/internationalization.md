# Internationalization (i18n)

Internationalization is implemented for the SAF portal with **English (`en`) as default** and **Bahasa Malaysia (`ms`) as the selectable alternate locale**.

## Current Implementation

- Backend locale defaults and supported locales are configured in `config/app.php`.
- Locale selection is persisted in session through `POST /locale`.
- Request-time locale application is handled by `App\Http\Middleware\SetLocale` (loaded conditionally to stay safe during partial deployments).
- Locale metadata is shared to Inertia via `HandleInertiaRequests` (`locale`, `locales`).
- Frontend translation lookup is centralized in `resources/js/lib/i18n.ts`.
- Locale switching remounts the active Inertia page so all mounted components consume the new shared locale immediately.
- Shared ormatDate, ormatDateTime, ormatNumber, and localeTag helpers keep display formatting aligned with n-MY and ms-MY.
- Static 	('...') references are audited against both dictionaries; user-authored names and event-specific content are not machine-translated.
- A reusable switcher exists in `resources/js/components/LocaleSwitcher.tsx` and is surfaced in guest/authenticated layouts.

## Coverage

The current EN/BM coverage includes:

- Authentication screens (login, register, password, verification).
- Shared navigation/layout labels.
- Dashboard, Settings, Notifications.
- Events, Matches, Results (key headings/actions/filter text).

Coverage for all remaining pages is incremental and should continue page-by-page.

## Notes

- CSP remains report-only in production by default; translation-related requests are same-origin and covered by current policy.
- RTL language support is not implemented and would require dedicated Tailwind/CSS updates.
