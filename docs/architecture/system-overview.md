# System Overview

Dokumen ini memberikan gambaran keseluruhan peringkat tinggi mengenai seni bina aplikasi Sistem Pengurusan Kejohanan Sukan (STMS). Ia bertujuan untuk menjadi titik permulaan bagi pembangun baharu untuk memahami komponen utama, corak reka bentuk, dan aliran data dalam sistem.

## 1. Pengenalan

STMS adalah aplikasi web yang direka untuk menguruskan kejohanan sukan. Ia menyokong pelbagai organisasi (multi-tenancy) dan menyediakan fungsi untuk menguruskan sukan, kejohanan, acara, peserta, pendaftaran, jadual perlawanan, dan keputusan.

Aplikasi ini dibina sebagai "Modern Monolith" menggunakan framework Laravel untuk backend dan React (melalui Inertia.js) untuk frontend.

## 2. Timbunan Teknologi (Tech Stack)

- **Backend:** PHP 8.3, Laravel 13
- **Frontend:** React, TypeScript, Inertia.js, Tailwind CSS, shadcn/ui
- **Pangkalan Data:** MySQL 8
- **Cache:** Pangkalan Data (sedia untuk Redis)
- **Queue:** Pangkalan Data (sedia untuk Redis)
- **Server:** Nginx + PHP-FPM
- **Pakej Utama Laravel:**
  - `spatie/laravel-permission`: Untuk pengurusan peranan (roles) dan kebenaran (permissions).
  - `barryvdh/laravel-dompdf`: Untuk penjanaan eksport PDF.
  - `maatwebsite/excel`: Untuk penjanaan eksport Excel.

## 3. Gaya Seni Bina

Aplikasi ini mengikut seni bina **Monolitik Berasaskan Servis** dengan antaramuka pengguna yang diganding rapat (tightly-coupled SPA).

- **Backend (Laravel):** Bertanggungjawab untuk semua logik bisnes, pengesahan (authentication), kebenaran (authorization), dan interaksi pangkalan data.
- **Frontend (Inertia.js + React):** Bertanggungjawab untuk memaparkan antaramuka pengguna. Inertia.js bertindak sebagai "gam" yang membolehkan kita membina SPA menggunakan komponen React tanpa perlu membina dan mengurus API yang berasingan. Controller Laravel merender komponen React secara terus.

## 4. Corak Reka Bentuk Utama

Aplikasi ini sangat bergantung pada beberapa corak reka bentuk untuk memastikan kod yang bersih, teratur, dan boleh diselenggara.

1.  **Model-View-Controller (MVC):** Corak asas yang disediakan oleh Laravel.
    - **Model:** Definisi data dan logik Eloquent (`app/Models`).
    - **View:** Komponen React (`resources/js/Pages`).
    - **Controller:** Mengendalikan permintaan HTTP dan merender 'view' (`app/Http/Controllers`).

2.  **Service Layer Pattern:** Logik bisnes yang kompleks dan interaksi pangkalan data dienkapsulasi dalam kelas `Service` (`app/Services`). Ini memastikan `Controller` dan `Action` kekal ringkas.

3.  **Action Pattern:** Untuk operasi yang mempunyai satu tanggungjawab (single-responsibility), kelas `Action` digunakan (`app/Actions`). Ia sering dipanggil dari `Controller` dan bertindak sebagai penyelaras antara `FormRequest` dan `Service`.

4.  **Form Request Classes:** Digunakan untuk pengesahan data permintaan (validation) dan kebenaran (authorization). Ini memisahkan logik pengesahan daripada `Controller`.

5.  **Policy Classes:** Digunakan untuk logik kebenaran yang lebih terperinci pada peringkat model (`app/Policies`).

6.  **Multi-Tenancy (Single Database):** Aplikasi ini menggunakan pendekatan multi-tenancy dengan satu pangkalan data. Setiap rekod utama (seperti `tournaments`, `participants`, dll.) mempunyai lajur `organization_id`. Data diasingkan menggunakan *global scopes* atau *query scopes* (`BelongsToOrganization` trait) untuk memastikan satu organisasi tidak dapat melihat data organisasi lain.

## 5. Aliran Permintaan (Request Flow)

Aliran permintaan HTTP yang tipikal adalah seperti berikut:

1.  **Browser** menghantar permintaan (cth: POST ke `/tournaments`).
2.  **Web Server (Nginx)** menghantar permintaan ke `public/index.php`.
3.  **Router Laravel (`routes/web.php`)** memadankan URL dengan kaedah `Controller` (cth: `TournamentController@store`).
4.  **Middleware** dijalankan (cth: `auth`, `verified`).
5.  **Form Request (`StoreTournamentRequest`)** diselesaikan oleh *service container*:
    - Kaedah `authorize()` dijalankan untuk menyemak kebenaran.
    - Kaedah `rules()` dijalankan untuk mengesahkan data.
6.  **Controller (`TournamentController@store`)** menerima `FormRequest` yang sah.
7.  **Controller** memanggil kelas **Action (`CreateTournament`)**.
8.  **Action** memanggil kaedah pada kelas **Service (`TournamentService@createWithSports`)**.
9.  **Service** melaksanakan logik bisnes, berinteraksi dengan **Model Eloquent** untuk menyimpan data ke pangkalan data dalam satu transaksi.
10. **Service** merekodkan log (`Log::info`).
11. **Controller** menerima respons dan mengembalikan paparan Inertia (`Inertia::render(...)` atau `Redirect`).
12. **Inertia** menghantar respons JSON kepada frontend.
13. **Frontend (React)** menerima props dan merender komponen halaman yang sepadan.

## 6. Struktur Direktori Utama

```
/
├── app/
│   ├── Actions/        # Kelas tindakan sekali guna
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/   # Kelas Form Request
│   ├── Models/         # Model Eloquent
│   ├── Policies/       # Kelas polisi kebenaran
│   ├── Providers/
│   └── Services/       # Kelas servis logik bisnes
├── database/
│   ├── factories/
│   └── migrations/
├── docs/               # Semua dokumentasi projek
│   ├── architecture/
│   ├── database/
│   └── design-system/
├── resources/
│   ├── js/
│   │   ├── Components/ # Komponen React boleh guna semula
│   │   ├── Layouts/    # Komponen susun atur utama
│   │   └── Pages/      # Komponen halaman penuh (dirender oleh Inertia)
│   └── css/
├── routes/
│   └── web.php         # Definisi laluan web
└── tests/
    ├── Feature/        # Ujian integrasi
    └── Unit/           # Ujian unit
```