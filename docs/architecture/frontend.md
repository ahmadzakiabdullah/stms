# Frontend Architecture

> **Current update — 21 August 2026:** Public UI includes `/athletes` and athlete profiles, shared responsive match cards with participant-grouped scorers, and a score editor with keyboard/accessibility-friendly increment controls.

Frontend menggunakan React 18, Inertia React 2, TypeScript, Tailwind CSS 3, Vite 8 dan komponen shadcn/ui/Radix. Repositori mempunyai 48 Inertia page files (12 November 2026). Semua page adalah TSX; beberapa shared UI/layout compatibility files masih `.jsx`, jadi migrasi TypeScript belum menyeluruh.

## Struktur

- `resources/js/Pages/` — page modules mengikut domain.
- `resources/js/components/` — komponen aplikasi dan public portal (nama direktori huruf kecil; penting pada filesystem case-sensitive).
- `resources/js/components/ui/` — primitive shadcn/Radix.
- `resources/js/Layouts/` — authenticated, guest dan public layouts.
- `resources/js/types/` — kontrak TypeScript bersama.

Form baharu/yang dimigrasi menggunakan React Hook Form dan Zod; form lama masih menggunakan Inertia `useForm`. TanStack Table tersedia sebagai pilihan projek tetapi belum digunakan dalam source semasa. Jangan tambah UI framework lain.

## Public Portal

Route `/` merender `Public/Index`; `/portal` dan `/index.php` redirect 301 ke `/`. Public pages merangkumi `/sports`, `/faculties`, `/venues`, `/athletes` dan `/athletes/{id}`, manakala `/schedule` ialah satu-satunya halaman jadual/keputusan (tab All/Live/Upcoming/Completed + filter). `/matches`, `/results` dan `/live` redirect 301 ke `/schedule`. `/news`, `/downloads`, `/faq`, `/about` dan `/contact-us` ialah halaman maklumat tambahan. Fungsi pengurusan dalaman Matches, Results dan Sports berada di `/manage/matches`, `/results/manage` dan `/manage/sports`. Portal menggunakan cache server-side jangka pendek dan refresh Inertia. Alamat, e-mel, telefon serta pautan media sosial pada Contact dibaca daripada tetapan tenant yang boleh disunting; backend hanya mendedahkan e-mel, nombor telefon dan URL HTTP(S) yang sah. `/athletes` menjalankan carian, penapis sport/kategori/fakulti, susunan dan A–Z di server serta memaginasi 24 atlet atau 12 pasukan setiap halaman supaya hanya satu halaman kad berada dalam DOM.

## Build dan Aksesibiliti

Quality gate frontend: typecheck, Vite production build, bundle budget, Playwright smoke dan axe. Vite 8 (Rolldown) memecahkan runtime kongsi melalui `build.rolldownOptions.output.codeSplitting.groups` dalam `vite.config.js` kepada chunk `react-vendor` (~140 KB), `inertia-vendor` (~239 KB) dan `i18n` (~157 KB, `resources/js/lib/i18n.ts`); semua chunk JS mesti kekal ≤400 KB dan CSS ≤120 KB (`npm run build:budget`). Playwright/axe tempatan pernah lulus 8/8 desktop/mobile; bukti CI sejarah (CI #112, commit `4b04c46`) tidak membuktikan kod semasa—jalankan semula gate pada SHA release candidate.

Fontsource membundel font yang digunakan dan Blade tidak lagi memuat Bunny Fonts. Guest menerima manifest Ziggy terhad dan global Vite prefetch telah dibuang. Dark-mode CSS tersedia, tetapi theme-toggle pengguna tidak ditemui; dokumentasi tidak boleh mendakwa kawalan itu sudah dilaksanakan.
