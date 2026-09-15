# Internationalization (i18n)

STMS ships a **server-driven, two-language** interface: **English (`en`)** and **Bahasa Melayu (`ms`)**. Translation dictionaries are the Laravel `lang/*.json` files, shared with the React frontend through Inertia shared props. There is no `react-i18next` dependency.

## Locales

- Available locales are declared in `config/locales.php`:
  - `en` => `English`
  - `ms` => `Bahasa Melayu`
- `config/app.php` uses `'locale' => env('APP_LOCALE', 'ms')` and `'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en')`. The SAF deployment defaults to Malay with English fallback.
- Locale is selected per session (stored in `session('locale')`), not per user or per organization. There is currently **no `locale` column** on `users` or `organizations`.

## Dictionaries

- `lang/en.json` and `lang/ms.json` hold the dictionaries. The key is the **English source string**; the value is the translation. `en.json` keeps the key as its own value (identity), `ms.json` holds the Malay translation.
- Both files must stay in sync: the same set of keys in both. As of this update both contain **750 keys** with no key mismatches and no empty Malay values.
- When a key is missing, the frontend renders the English key itself, so the UI degrades gracefully instead of showing blank text.

## Frontend runtime

- `resources/js/lib/i18n.tsx` provides `LanguageProvider`, `useI18n()`, and `useT()`.
- `t(key, params?)` supports `{{name}}` interpolation and optional plurals via `key_one` / `key_other` when `params.count` is a number.
- `resources/js/components/LanguageSwitcher.tsx` renders the language menu (only when more than one locale is available). It is mounted in `AuthenticatedLayout`, `GuestLayout`, `Welcome`, and the public Live Scores page.
- Changing language posts to `language.update`; the controller stores the locale in the session and redirects back.

## Backend plumbing

- `app/Http/Controllers/LanguageController.php` validates the requested locale against `config('locales.available_locales')` (404 on unknown locale), then persists it in the session.
- `app/Http/Middleware/HandleInertiaRequests.php` shares three props with every Inertia response:
  - `locale` — the active locale.
  - `availableLocales` — from `config/locales.php`.
  - `translations` — the parsed `lang/{locale}.json` dictionary.

## Scope and current limitations

- All 36 Inertia pages render user-facing text through `t()`. Zod validation messages and a few technical/example values remain in English by design.
- Some demo/placeholder values (example slugs such as `badminton`, `sukma-xxi`) are kept as literal examples.
- Right-to-left (RTL) language support is **not** implemented; it would require additional Tailwind RTL utilities.

## Adding a new string

1. Use the English source string as the key: `t('Your new label')`.
2. Add `"Your new label"` to `lang/en.json` with the same value (identity).
3. Add the Malay translation to `lang/ms.json`.
4. Keep both files' key sets identical; missing keys silently fall back to English.
