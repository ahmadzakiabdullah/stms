# Logging

STMS menggunakan saluran Laravel dalam `config/logging.php`. Saluran sebenar, tahap log dan retention ditentukan oleh environment; jangan menganggap semua deployment menggunakan daily file atau external aggregator.

Kod merekodkan exception dan beberapa operasi service/action, tetapi bukan setiap CRUD mempunyai struktur atau medan yang seragam. `spatie/laravel-activitylog` melengkapkan application log untuk tindakan terpilih.

## Standard Yang Diperlukan

- Sertakan correlation/request ID, actor ID, `organization_id`, action dan subject ID apabila relevan.
- Jangan log password, token, cookie, nombor pengenalan atau payload penuh.
- E-mel dan nombor telefon ialah PII; redact/hash atau dokumentasikan tujuan dan retention.
- Elakkan stack trace/exception mentah dalam respons pengguna.
- Hadkan akses log, gunakan rotation/retention, dan uji penghantaran ke pemantauan luar sebelum release.

Production audit 17 Ogos tidak menemui bukti Sentry/APM aktif. Integrasi luar kekal cadangan sehingga konfigurasi dan alert delivery dibuktikan.

## Audit Activity Metadata

Custom `App\Models\Activity` records a standard `audit` property for every activity when the values are available:

- `actor_id` — the causer user key;
- `action` — the activity event;
- `subject_id` and `subject_type` — the affected model;
- `organization_id` — resolved from the subject, causer or request tenant context;
- `correlation_id` — the current request ID for HTTP activity.

This metadata complements the native `causer_*`, `subject_*` and `event` columns and keeps existing activity queries compatible. It is not a compliance ledger: command/queue activity must still establish tenant context explicitly, and missing context is retained as missing rather than guessed.

## Retention and Access

- Activity records use the configured `activitylog.clean_after_days` value (currently 365 days) and should be pruned with `php artisan activitylog:clean` from a supervised scheduler or approved maintenance runbook.
- Application log retention is controlled by the selected Laravel channel (`LOG_CHANNEL`, `LOG_DAILY_DAYS` and the deployment log collector); the repository does not claim a production retention period until the operator records it.
- Activity log UI access is protected by the `view-activity-logs` gate. Super-admins may filter across organizations; other users are restricted to activity caused by users in their own organization.
- Production operators must document log storage location, retention period, export/deletion procedure, access owner and review cadence before release approval.
