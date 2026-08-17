# Contributing to STMS

## Sebelum Mengubah Kod

Baca dalam urutan: `CLAUDE.md`, `AGENTS.md`, `CURRENT_STATE.md`, `ROADMAP.md`, `TODOS.md`, ADR, architecture dan database docs. Ikut Current Focus dalam `TODOS.md`; jangan memulakan fasa masa hadapan tanpa kelulusan.

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

Semua gate mesti lulus pada commit PR yang sama. Pada 17 Ogos 2026 baseline semasa masih mempunyai satu PHPUnit failure, satu PHPUnit error dan enam fail Pint, jadi jangan menggunakan keputusan lama sebagai bukti hijau.
