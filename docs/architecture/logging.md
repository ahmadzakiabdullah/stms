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
