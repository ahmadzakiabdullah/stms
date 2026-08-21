# TODO UI/UX — Public Portal SAF UTeM

> **Baseline sejarah — digantikan.** Snapshot 17 Ogos 2026 sebelum halaman awam berasingan ditambah. Keputusan route standalone diselesaikan pada 19 Ogos 2026: `/matches`, `/sports`, `/schedule`, `/results`, `/faculties`, `/venues`, `/live`, `/news`, `/downloads`, `/faq` dan `/about` kini wujud sebagai halaman awam, dengan pengurusan dalaman di `/manage/matches` dan `/manage/sports`. Keadaan semasa: [`CURRENT_STATE.md`](CURRENT_STATE.md), [`TODOS.md`](TODOS.md) dan [`docs/README.md`](docs/README.md).

> Baseline 17 Ogos 2026 untuk `https://saf.utem.edu.my/`.

## Skop Semasa

Portal public ialah satu landing page Inertia pada `/` (alias `/portal`) dengan seksyen programme, medal tally, schedule dan result. Hanya `/contact-us` wujud sebagai page public berasingan. `/sports-programme`, `/medal-tally` dan `/schedules` memberi 404; GET `/results` tidak wujud sebagai page dan bertembung dengan POST authenticated result route.

Production memaparkan SAF 2026, 23 sports dengan events, 30 events, 8 faculties, 12 upcoming matches, 0 completed matches/results/medal rows. Contact e-mel, telefon dan alamat kosong.

## P0 — Sebelum Promosi Awam

- [ ] Lengkapkan e-mel, telefon, alamat, waktu operasi dan pemilik kandungan contact.
- [x] Tambah meta description, canonical, tenant-explicit favicon dan sitemap.
- [x] Alih Bunny Fonts dan lulus CSP-compatible local browser test; production enforcing menunggu deploy.
- [x] Refresh/failure state mengekalkan data dan mengumumkan status melalui live region.
- [x] Playwright + axe lulus desktop/mobile serta keyboard pada root-hosted isolated environment; post-deploy run masih wajib.
- [ ] Putuskan sama ada seksyen landing mencukupi atau route standalone benar-benar diperlukan; jangan pautkan URL yang belum wujud.

## P1 — Kejelasan Pertandingan

- [x] Empty state membezakan pertandingan belum bermula daripada kegagalan data.
- [x] Progress tidak mendominasi ketika fixture/result kosong.
- [x] Navigation public mempunyai label/active semantics dan touch target utama.
- [x] Paparkan `Last updated`, `Refreshing…`, kegagalan refresh dan tindakan refresh secara konsisten.
- [ ] Terangkan bila jadual/result/medal akan tersedia dan siapa yang mengesahkannya.
- [ ] Uji content overflow, nama sport/fakulti panjang, timezone Asia/Kuala_Lumpur dan format dwibahasa.

## P2 — Konsistensi dan Prestasi

- [ ] Selaraskan public theme enam warna dengan token design system dan semak kontras setiap kombinasi tenant. Public home audit menemui dan membetulkan badge kategori `text-red-600` kepada `text-red-700` untuk WCAG contrast.
- [x] Buang global Vite prefetch dan hadkan guest Ziggy manifest.
- [x] Logo mempunyai saiz, fallback, alt handling dan inverse variant.
- [ ] Ukur Core Web Vitals sebenar; audit 8 GET sequential (median 237 ms) hanyalah smoke timing, bukan load test.

## Kriteria Selesai

Tiada broken link, contact lengkap, metadata asas sah, axe tiada critical/serious issue yang tidak diterima, keyboard journey lulus, CSP enforcing tanpa violation penting, dan bukti disimpan pada SHA release yang sama.
