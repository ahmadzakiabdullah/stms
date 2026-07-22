# Authorization Architecture

Dokumen ini menerangkan strategi dan pelaksanaan sistem kebenaran (authorization) dalam aplikasi STMS. Sistem ini direka untuk menjadi robust, berlapis, dan mudah dikembangkan, dengan prinsip utama "tolak secara lalai" (deny by default).

## 1. Gambaran Keseluruhan

Sistem kebenaran STMS menggunakan gabungan beberapa mekanisme Laravel dan pakej pihak ketiga untuk mengawal akses pengguna:

1.  **Roles & Permissions (`spatie/laravel-permission`):** Asas untuk mendefinisikan kebolehan pengguna.
2.  **Form Requests:** Titik masuk utama untuk pengesahan kebenaran bagi setiap permintaan HTTP yang mengubah data.
3.  **Policy Classes:** Logik terperinci untuk menentukan sama ada pengguna boleh berinteraksi dengan rekod model tertentu.
4.  **Middleware:** Untuk melindungi laluan (routes) pada peringkat yang lebih luas.

## 2. Komponen Utama

### 2.1. Roles & Permissions (Spatie)

Pakej `spatie/laravel-permission` adalah tulang belakang sistem kebenaran berasaskan peranan (RBAC).

-   **Permissions (Kebenaran):** Merupakan tindakan atomik yang boleh dilakukan dalam sistem (cth: `create-tournament`, `delete-participant`). Semua kebenaran yang ada didefinisikan dan di-seed dalam `database/seeders/RolesAndPermissionsSeeder.php`.
-   **Roles (Peranan):** Adalah koleksi kebenaran yang boleh diberikan kepada pengguna. Peranan utama dalam aplikasi ini ialah:
    -   `super-admin`: Akses tanpa had ke seluruh sistem.
    -   `org-admin`: Akses penuh kepada semua data dalam organisasinya sendiri.
    -   `staff`: Akses terhad kepada data dalam organisasinya, berdasarkan kebenaran spesifik yang diberikan.

Pengguna diberikan peranan, dan peranan tersebut menentukan kebenaran yang mereka miliki.

### 2.2. Form Requests (`app/Http/Requests`)

`FormRequest` adalah **barisan pertahanan pertama** untuk permintaan yang mengubah data (POST, PUT, PATCH, DELETE). Setiap `FormRequest` mempunyai kaedah `authorize()` yang mesti mengembalikan `true` untuk permintaan itu diteruskan.

Kaedah ini dijalankan **sebelum** logik pengesahan (validation) dan sebelum kaedah `Controller` dipanggil. Ia biasanya mengandungi semakan kebenaran peringkat tinggi.

**Contoh (dalam `StoreTournamentRequest`):**
```php
public function authorize(): bool
{
    // Pengguna mesti mempunyai kebenaran 'create-tournament' untuk meneruskan.
    return $this->user()->can('create-tournament');
}
```

### 2.3. Policies (`app/Policies`)

`Policy` menyediakan logik kebenaran yang terperinci untuk model Eloquent tertentu. Ia menjawab soalan seperti, "Bolehkah Pengguna A mengemas kini Rekod B?".

Setiap `Policy` dipetakan kepada `Model` dalam `App\Providers\AuthServiceProvider`.

Ciri-ciri utama `Policy` dalam aplikasi ini:

1.  **Semakan Multi-Tenancy:** Setiap kaedah dalam `Policy` (cth: `update`, `delete`) **wajib** menyemak bahawa `organization_id` pengguna sepadan dengan `organization_id` pada model. Ini adalah lapisan keselamatan kritikal untuk mengelakkan kebocoran data antara organisasi.
2.  **Semakan Kebenaran:** Ia juga boleh menyemak kebenaran spesifik jika perlu.

**Contoh (dalam `TournamentPolicy@update`):**
```php
public function update(User $user, Tournament $tournament): bool
{
    // Semakan 1: Adakah pengguna mempunyai kebenaran umum?
    if (!$user->can('edit-tournament')) {
        return false;
    }

    // Semakan 2 (KRITIKAL): Adakah kejohanan ini milik organisasi pengguna?
    return $user->organization_id === $tournament->organization_id;
}
```

## 3. Aliran Kebenaran Tipikal

Aliran semakan kebenaran untuk tindakan `UPDATE` adalah seperti berikut:

1.  **Middleware `auth`:** Memastikan pengguna telah log masuk.
2.  **FormRequest `authorize()`:** Dipanggil secara automatik. Menyemak jika pengguna mempunyai kebenaran umum (cth: `can('edit-tournament')`). Jika gagal, permintaan ditolak dengan status 403.
3.  **Controller:** Kaedah `Controller` dipanggil. Ia menerima model yang berkaitan melalui *route model binding*.
4.  **Policy:** `Controller` (atau `FormRequest` dalam sesetengah kes) secara eksplisit atau implisit memanggil kaedah `Policy` (cth: `Gate::authorize('update', $tournament)`). `Policy` kemudian menjalankan semakan terperinci, termasuk semakan pemilikan organisasi. Jika gagal, `AuthorizationException` dilemparkan.
5.  **Action/Service:** Jika semua semakan di atas lulus, barulah logik bisnes dilaksanakan.

## 4. Amalan Terbaik untuk Pembangun

1.  **Utamakan `Policy`:** Untuk sebarang logik yang melibatkan model spesifik, letakkan logik kebenaran dalam `Policy`.
2.  **Jangan Lupa Semakan Organisasi:** Setiap kaedah `Policy` yang berurusan dengan model *tenant-specific* mesti mengandungi semakan `$user->organization_id === $model->organization_id`.
3.  **Gunakan `FormRequest`:** Semua laluan (routes) yang mengubah data mesti menggunakan `FormRequest` dengan kaedah `authorize()` yang betul.
4.  **Definisikan Kebenaran Baharu:** Apabila mencipta ciri baharu yang memerlukan kawalan akses, definisikan kebenaran (permission) baharu dalam seeder dan gunakannya dalam kod.
5.  **Tulis Ujian:** Sentiasa tulis ujian *feature* untuk mengesahkan logik kebenaran, terutamanya ujian *cross-tenant* yang cuba mengakses data yang tidak dibenarkan.