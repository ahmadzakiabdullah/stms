# Frontend Architecture

Frontend menggunakan React 18, Inertia React 2, TypeScript, Tailwind CSS 3, Vite 8 dan komponen shadcn/ui/Radix. Repositori mempunyai 38 Inertia page files. Semua page adalah TSX; beberapa shared UI/layout compatibility files masih `.jsx`, jadi migrasi TypeScript belum menyeluruh.

## Struktur

- `resources/js/Pages/` — page modules mengikut domain.
- `resources/js/Components/` — komponen aplikasi dan public portal.
- `resources/js/Components/ui/` — primitive shadcn/Radix.
- `resources/js/Layouts/` — authenticated, guest dan public layouts.
- `resources/js/types/` — kontrak TypeScript bersama.

Form baharu/yang dimigrasi menggunakan React Hook Form dan Zod; form lama masih menggunakan Inertia `useForm`. TanStack Table tersedia sebagai pilihan projek tetapi belum digunakan dalam source semasa. Jangan tambah UI framework lain.

## Public Portal

Route `/` dan alias `/portal` merender `Public/Index`; `/contact-us` ialah page awam tambahan. Medal tally, programme, schedule dan ringkasan result berada sebagai seksyen/data pada landing page, bukan route standalone. Portal menggunakan cache server-side jangka pendek dan refresh Inertia. Alamat, e-mel, telefon serta pautan media sosial pada Contact dibaca daripada tetapan tenant yang boleh disunting; backend hanya mendedahkan e-mel, nombor telefon dan URL HTTP(S) yang sah.

## Build dan Aksesibiliti

Quality gate frontend: typecheck, Vite production build, bundle budget, Playwright smoke dan axe. Build semasa lulus dengan JS terbesar kira-kira 351 KB dan CSS kira-kira 92 KB di bawah budget 400/100 KB. Playwright/axe tempatan lulus 8/8 desktop/mobile; connected CI selepas commit masih diperlukan.

Fontsource membundel font yang digunakan dan Blade tidak lagi memuat Bunny Fonts. Guest menerima manifest Ziggy terhad dan global Vite prefetch telah dibuang. Dark-mode CSS tersedia, tetapi theme-toggle pengguna tidak ditemui; dokumentasi tidak boleh mendakwa kawalan itu sudah dilaksanakan.
