# Security Overview

> Status disahkan pada 17 Ogos 2026. Dokumen ini menerangkan pelaksanaan sebenar, bukan jaminan pematuhan.

## Kawalan Yang Wujud

- Laravel authentication, CSRF, Form Requests dan password hashing.
- Spatie roles/permissions dengan 6 peranan aktif dan 42 permissions dalam pangkalan data yang diaudit.
- Policy classes dan `Gate::authorize()` pada operasi mutasi serta index/read sensitif.
- `TenantContext` serta global scope `BelongsToOrganization` untuk kebanyakan model tenant.
- Rate limiting pada operasi sensitif termasuk draw, result, match dan export.
- Header HSTS, `nosniff`, `SAMEORIGIN`, referrer policy dan permissions policy pada production HTTPS.
- Dependency audit Composer dan npm semasa tidak menemui advisory/vulnerability pada tahap yang diuji.
- Pemeriksaan statik tenant-bypass berasaskan allowlist lulus.

## Risiko Semasa

1. Production masih menghantar CSP report-only walaupun repository kini self-hosted dan CSP-ready.
2. Akaun pangkalan data runtime kelihatan boleh melihat metadata skema lain pada server; least privilege perlu disahkan dan dikecilkan.
3. Email verification masih dimatikan sehingga real mail/reset-password flow tersedia.
4. Activity log ialah jejak operasi terpilih, bukan ledger audit immutable atau bukti compliance.

## Production Baseline

Runtime yang diperiksa menggunakan `APP_DEBUG=false`, tetapi cache dan queue adalah `database`, session ialah `file`, email verification dimatikan dan CSP report-only dihidupkan. Ini tidak sepadan dengan sasaran `.env.production.example` yang mengutamakan Redis, secure session, verification dan CSP enforcing.

`GET /health` dilindungi token dan mengembalikan 404 tanpa token. Gunakan `GET /up` hanya sebagai liveness asas; jangan dedahkan token health dalam URL, log atau dokumentasi awam.

## Release Security Gate

- Kekalkan direct-URL role matrix dan cross-tenant payload tests sebagai regression gate.
- Jadikan CSP enforcing melalui deployment runbook dan browser smoke test.
- Sahkan DB least privilege, secure session, mail provider, backup/restore dan secret rotation.
- Pastikan Pint, PHPUnit, build, dependency audit dan CI lulus pada SHA release yang sama.

Lihat [laporan audit semasa](../audits/2026-08-17-full-project-and-production-audit.md) dan [polisi pelaporan](../../SECURITY.md).
