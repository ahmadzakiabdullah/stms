# Multi-Tenancy Architecture

STMS menggunakan satu pangkalan data dengan skema dikongsi. `organization_id` ialah sempadan tenant bagi data domain, dan pengasingan berlaku pada lapisan aplikasi.

## Mekanisme Semasa

- `TenantContext` menyimpan organisasi untuk lifecycle request/job.
- Trait `BelongsToOrganization` menambah global scope dan menyediakan `forOrganization()` serta bypass eksplisit.
- Service/Action mesti menetapkan `organization_id`; trait tidak mengisinya secara automatik.
- Policy/Gate mengawal capability, termasuk akses kepada rekod yang sudah tenant-scoped.
- Form Request dan relationship query mesti mengehadkan `exists`/`unique` kepada organisasi yang sama.
- Queue job tenant menggunakan middleware untuk menetapkan dan membersihkan konteks.
- Skrip `check:tenant-bypasses` mengesan bypass baharu di luar allowlist.

## Pengecualian Penting

`Organization` ialah akar tenant dan tidak menggunakan global scope. `User` juga mempunyai pengendalian skop tersendiri. Kedua-duanya memerlukan authorization dan query scoping eksplisit. Super-admin/batch/command hanya boleh bypass melalui API yang disengajakan serta diaudit.

Portal awam tidak boleh bergantung pada “tenant pertama”. Ia memilih `PUBLIC_ORG_SLUG` dahulu, kemudian `PUBLIC_SESSION_SLUG` dalam organisasi itu. Query aset branding tetamu juga mesti menggunakan tenant yang sama; query favicon Blade semasa belum memenuhi syarat ini.

## Jurang Disahkan

Global scope bukan pengganti `viewAny`. Beberapa index controller, termasuk organisasi, pengguna dan peserta, tidak memanggil capability read yang sepatutnya. Ini ialah blocker release walaupun sebahagian query masih mengehadkan organisasi.

Ujian cross-tenant perlu membuat request sebenar dan memeriksa status serta payload. Ujian yang hanya mencipta dua organisasi dan membandingkan ID tidak membuktikan isolation.

## Peraturan Pembangunan

1. Semua jadual domain tenant baharu mempunyai FK UUID `organization_id` dan indeks yang sesuai.
2. Gunakan Eloquent scoped; raw query mesti mengandungi syarat tenant eksplisit dan ujian regresi.
3. Authorize setiap list/read/mutation/export, bukan sekadar menyembunyikan pautan UI.
4. Parent dan child mesti berasal daripada organisasi yang sama.
5. Set/clear `TenantContext` dalam `finally` bagi lifecycle berterusan.
6. Tambah ujian A→B, B→A, tanpa tenant, super-admin dan queue untuk perubahan berkaitan tenant.

Lihat [ADR-002](../adr/ADR-002-multi-tenant-design.md), [ADR-009](../adr/ADR-009-tenant-context-lifecycle.md) dan [audit semasa](../audits/2026-08-17-full-project-and-production-audit.md).
