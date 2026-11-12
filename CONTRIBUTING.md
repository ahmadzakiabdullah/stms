# Contributing to STMS

## Sebelum Mengubah Kod

Baca dalam urutan: `CLAUDE.md`, `AGENTS.md`, `CURRENT_STATE.md`, `ROADMAP.md`, `TODOS.md`, ADR, architecture dan database docs. Ikut keutamaan seksyen **P0 — Release blockers production** dalam `TODOS.md`; jangan memulakan fasa masa hadapan tanpa kelulusan.

## Workflow

Branch lalai yang diperhatikan ialah `master`. Tiada bukti bahawa `main`/`develop` digunakan, jadi jangan cipta workflow berasaskan kedua-duanya tanpa keputusan maintainer. Gunakan branch fokus seperti `feature/...`, `fix/...` atau `docs/...`, kemudian PR ke branch yang ditetapkan oleh maintainer.

Commit mesti kecil dan jelas, contohnya:

```text
feat: add tenant-scoped event action
fix: authorize participant index
docs: reconcile ranking implementation status
```

## Standard Wajib

- Laravel + Service/Action + Form Request + Policy/Gate.
- Semua data tenant diskop `organization_id`; uji akses cross-tenant dan read/list.
- UUID untuk entity domain baharu; soft delete apabila sesuai.
- React + Inertia + TypeScript + Tailwind + shadcn/ui sahaja.
- Jangan hardcode nama sukan atau rules/ranking baharu.
- Kemas kini tests, `TODOS.md`, `CHANGELOG.md` dan dokumen seni bina yang berkaitan.

## Quality Gate

```bash
php vendor/bin/pint --test
php artisan test
npm run check:inventory
npm run check:tenant-bypasses
npm run typecheck
npm run build
npm run build:budget
composer audit --locked --no-interaction
npm audit --audit-level=high
```

Semua gate mesti lulus pada commit PR yang sama. Verifikasi terakhir (12 November 2026, SQLite in-memory): PHPUnit 592 ujian dengan 8 kegagalan sedia ada yang juga berlaku pada `master` — lihat `CURRENT_STATE.md` dan `TODOS.md`; Pint, TypeScript, build/budget dan tenant-bypass lulus. Jangan menggunakan keputusan lama sebagai bukti hijau; jalankan semula gate pada commit PR anda.
