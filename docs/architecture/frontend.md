# Frontend Architecture

Dokumen ini menerangkan seni bina, timbunan teknologi, dan amalan terbaik untuk pembangunan antaramuka pengguna (frontend) aplikasi STMS.

## 1. Gambaran Keseluruhan & Falsafah

Frontend STMS dibina sebagai **Single-Page Application (SPA) yang diganding rapat (tightly-coupled)** menggunakan **Inertia.js**. Falsafah ini membolehkan kita membina UI yang moden dan reaktif menggunakan React, tanpa perlu membina dan menyelenggara API REST/GraphQL yang berasingan. Backend Laravel berkomunikasi secara terus dengan komponen React.

## 2. Timbunan Teknologi (Tech Stack)

-   **Framework:** React 18+
-   **Bahasa:** TypeScript
-   **"Gam" Backend-Frontend:** Inertia.js
-   **Styling:** Tailwind CSS
-   **Perpustakaan Komponen (Base):** shadcn/ui
-   **Bundler:** Vite

## 3. Struktur Direktori Utama (`resources/js`)

```
resources/js/
├── app.tsx               # Titik masuk utama aplikasi frontend
├── bootstrap.js          # Konfigurasi awal (cth: axios)
├── Components/           # Komponen React boleh guna semula (UI & domain)
│   ├── ui/               # Komponen shadcn/ui (Button, Input, dll.)
│   └── ...               # Komponen aplikasi (cth: TournamentCard.tsx)
├── Layouts/              # Komponen susun atur halaman (cth: AuthenticatedLayout.tsx)
├── lib/                  # Fungsi utiliti (cth: utils.ts untuk cn())
├── Pages/                # Komponen peringkat atasan yang mewakili satu halaman penuh
│   ├── Auth/             # Halaman pengesahan (Login, Register)
│   ├── Tournaments/
│   │   ├── Index.tsx     # Halaman senarai kejohanan
│   │   └── Show.tsx      # Halaman butiran kejohanan
│   └── Dashboard.tsx
└── types/                # Definisi jenis TypeScript global (cth: index.d.ts)
```

## 4. Komponen

### 4.1. Halaman (`Pages/`)

Setiap fail dalam direktori `Pages/` mewakili satu halaman dalam aplikasi. Controller Laravel akan merender komponen ini menggunakan `Inertia::render('Folder/PageName', $props)`. Komponen halaman bertanggungjawab untuk menyusun atur komponen-komponen yang lebih kecil.

### 4.2. Komponen Boleh Guna Semula (`Components/`)

-   **Komponen UI (`Components/ui/`):** Ini adalah komponen asas yang dijana oleh `shadcn/ui` seperti `Button`, `Card`, `Input`, dll. Ia tidak mempunyai logik bisnes dan boleh digunakan di mana-mana sahaja.
-   **Komponen Domain:** Ini adalah komponen yang lebih kompleks dan spesifik kepada domain aplikasi, seperti `TournamentList`, `FixtureItem`, dll. Ia mungkin mengandungi logik UI dan menguruskan *state* tempatan.

### 4.3. Susun Atur (`Layouts/`)

Komponen susun atur menyediakan struktur halaman yang konsisten (cth: sidebar, header, footer). Komponen Halaman (`Pages/`) akan "dibungkus" di dalam komponen susun atur ini. Ini dicapai melalui *persistent layouts* Inertia.

## 5. Pengurusan State (State Management)

Pendekatan pengurusan *state* kita adalah minimalis, hasil daripada penggunaan Inertia.js.

-   **Props dari Backend:** Sumber utama *state* untuk setiap halaman datang terus dari *controller* Laravel sebagai *props*. Tidak perlu *fetch* data secara manual pada *page load*.
-   **`useForm` Hook (Inertia):** Untuk menguruskan *state* borang (input, ralat pengesahan, status *submitting*), kita menggunakan *hook* `useForm` yang disediakan oleh Inertia. Ia menyepadukan dengan baik dengan sistem pengesahan Laravel.
-   **`useState` & `useReducer` (React):** Untuk *state* UI yang bersifat tempatan dan sementara (cth: status *dropdown* terbuka/tertutup, *tab* aktif), gunakan *hook* standard React.

## 6. Styling

Styling dikendalikan sepenuhnya oleh **Tailwind CSS**.

-   **Kelas Utiliti:** Gunakan kelas utiliti Tailwind secara terus dalam JSX.
-   **`cn` Utility:** Untuk menggabungkan kelas secara bersyarat, gunakan fungsi `cn` dari `lib/utils.ts`. Ini amat berguna apabila menggabungkan gaya lalai komponen dengan gaya yang dipas (passed) melalui `props`.
-   **Tema:** Warna, fon, dan pembolehubah reka bentuk lain dikonfigurasi dalam `tailwind.config.js` untuk memastikan konsistensi dengan sistem reka bentuk.

## 7. Jenis & Keselamatan Jenis (Type Safety)

-   **TypeScript:** Semua kod *frontend* baharu mesti ditulis dalam TypeScript (`.tsx`).
-   **Definisi Props:** Gunakan `interface` atau `type` TypeScript untuk mendefinisikan *props* bagi setiap komponen.
-   **Jenis Dikongsi (Shared Types):** Inertia menyediakan cara untuk menjana definisi jenis TypeScript dari *props* yang dihantar oleh Laravel. Ini memastikan konsistensi jenis antara *backend* dan *frontend*. Fail `types/index.d.ts` boleh digunakan untuk jenis global.

## 8. Amalan Terbaik

1.  **Fail `.jsx` ke `.tsx`:** Semua fail `.jsx` sedia ada perlu dipindahkan ke `.tsx` secara berperingkat (Rujuk Tugasan 2.2).
2.  **Komponen Kecil & Fokus:** Pecahkan UI kepada komponen yang lebih kecil dan boleh diguna semula.
3.  **Elakkan State Global:** Cuba elakkan penggunaan perpustakaan pengurusan *state* global (seperti Redux atau Zustand) melainkan jika benar-benar perlu. Seni bina Inertia mengurangkan keperluan untuknya.
4.  **Aksesibiliti (a11y):** Pastikan komponen yang dibina adalah mudah diakses, menggunakan atribut ARIA yang betul dan elemen HTML semantik. Komponen `shadcn/ui` menyediakan asas yang baik untuk ini.

## 9. Quality Gate

`npm run typecheck` menjalankan `tsc --noEmit` dan mesti lulus dalam CI sebelum build production. Komponen JSX lama berada di belakang compatibility declarations; halaman aplikasi dan payload Inertia baharu hendaklah terus menggunakan jenis TypeScript yang nyata.
# Build Quality Gates

The project standardizes on Tailwind CSS 3. Dialog and sheet state animations are defined in `tailwind.config.js`; Tailwind 4-only stylesheets are not imported. This avoids mixed compiler semantics and produces a warning-free build.

After `npm run build`, run `npm run build:budget`. Default uncompressed limits are 400 KB per JavaScript chunk and 100 KB per CSS asset; override them only through reviewed CI environment variables.
