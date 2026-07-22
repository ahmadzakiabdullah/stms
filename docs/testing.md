# Testing Strategy

Dokumen ini menggariskan falsafah, strategi, dan amalan terbaik untuk ujian automatik (automated testing) dalam aplikasi STMS. Matlamat utama ujian adalah untuk memastikan ketepatan kod, mencegah regresi, dan menguatkuasakan keselamatan data, terutamanya dalam konteks multi-tenancy.

## 1. Falsafah Ujian

Kami mengamalkan pendekatan pragmatik yang diilhamkan oleh "Piramid Ujian". Fokus utama adalah pada **Ujian Ciri (Feature Tests)** kerana ia memberikan keyakinan tertinggi bahawa sistem berfungsi seperti yang diharapkan dari perspektif pengguna, sambil masih menggunakan **Ujian Unit (Unit Tests)** untuk logik bisnes yang kompleks dan terpencil.

## 2. Jenis-jenis Ujian

Projek ini dibahagikan kepada dua jenis ujian utama yang terletak dalam direktori `tests/`.

### 2.1. Ujian Unit (`tests/Unit`)

-   **Tujuan:** Untuk menguji satu unit kod (biasanya satu kelas atau kaedah) secara terpencil.
-   **Skop:** Kelas `Service`, kelas `Action`, atau mana-mana kelas utiliti yang mempunyai logik yang kompleks.
-   **Ciri-ciri:**
    -   Sangat pantas untuk dijalankan.
    -   Tidak berinteraksi dengan pangkalan data atau sistem luaran (sebarang interaksi harus di-mock).
    -   Tidak mem-boot aplikasi Laravel sepenuhnya.

**Contoh:** `tests/Unit/TournamentServiceTest.php` menguji logik dalam `TournamentService` tanpa membuat permintaan HTTP.

### 2.2. Ujian Ciri (`tests/Feature`)

-   **Tujuan:** Untuk menguji satu ciri lengkap dari perspektif pengguna, biasanya melalui permintaan HTTP.
-   **Skop:** Aliran kerja pengguna seperti mendaftar, mencipta kejohanan, mengemas kini perlawanan, dan yang paling penting, semakan kebenaran.
-   **Ciri-ciri:**
    -   Mem-boot aplikasi Laravel sepenuhnya.
    -   Berinteraksi dengan pangkalan data ujian. Projek ini kini menggunakan MySQL untuk parity dengan production.
    -   Menggunakan trait `RefreshDatabase` untuk memastikan setiap ujian berjalan pada pangkalan data yang bersih.
    -   Mensimulasikan permintaan HTTP dan memeriksa respons (status, data JSON, redirect).

**Contoh:** `tests/Feature/Policies/TournamentPolicyTest.php` mencipta pengguna dan kejohanan, kemudian membuat permintaan untuk mengesahkan sama ada pengguna boleh atau tidak boleh mengakses kejohanan tersebut.

## 3. Alatan & Persediaan

-   **PHPUnit:** Rangka kerja ujian utama yang digunakan.
-   **Pangkalan Data Ujian:** Menggunakan MySQL secara lalai melalui `phpunit.xml`. Fail itu hanya menyimpan default bukan rahsia; override `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` melalui environment variable tempatan atau CI secrets.
-   **Model Factories:** Digunakan secara meluas untuk mencipta data ujian yang konsisten (`database/factories`).
-   **`RefreshDatabase` Trait:** Mesti digunakan dalam semua Ujian Ciri.
-   **`actingAs($user)`:** Kaedah helper untuk mengelog masuk pengguna bagi tujuan ujian.

## 4. Strategi Ujian untuk Komponen Utama

### 4.1. Policies (`tests/Feature/Policies`)
Ini adalah ujian yang paling kritikal dalam aplikasi. Setiap `Policy` mesti mempunyai fail ujian yang sepadan.
-   **Ujian Positif:** Sahkan bahawa pengguna dengan peranan dan pemilikan yang betul **boleh** melakukan tindakan.
-   **Ujian Negatif:** Sahkan bahawa pengguna tanpa kebenaran **tidak boleh** melakukan tindakan.
-   **Ujian Tetamu (Guest):** Sahkan bahawa pengguna yang tidak log masuk **tidak boleh** melakukan tindakan.
-   **Ujian Cross-Tenant (WAJIB):** Ini adalah ujian paling penting. Cipta dua organisasi (A dan B) dan dua pengguna (satu untuk setiap organisasi). Sahkan bahawa pengguna dari Organisasi A **tidak boleh sama sekali** melihat, mencipta, mengemas kini, atau memadam data milik Organisasi B.

### 4.2. Form Requests (`tests/Feature/Requests`)
-   Uji peraturan pengesahan (validation rules) untuk memastikan data yang tidak sah ditolak.
-   Uji logik kebenaran dalam kaedah `authorize()` dengan membuat permintaan sebagai pengguna yang dibenarkan dan tidak dibenarkan.

### 4.3. Controllers (`tests/Feature`)
-   Uji bahawa laluan (route) yang betul memanggil kaedah `Controller` yang betul.
-   Uji bahawa respons yang betul dikembalikan (cth: status 200, redirect 302).
-   Uji bahawa data yang betul dihantar ke komponen Inertia.

### 4.4. Services (`tests/Unit`)
-   Uji setiap kaedah awam.
-   Fokus pada logik bisnes, pengiraan, atau manipulasi data yang kompleks.

## 5. Cara Menjalankan Ujian

Jalankan arahan berikut dari direktori root projek (pada pemacu tempatan, bukan UNC path).

-   **Jalankan semua ujian:**
    ```bash
    php artisan test
    ```
-   **Nota Windows/UNC path:**
    ```bash
    cmd /c "pushd \\server\share\path && php vendor/bin/phpunit --configuration phpunit.xml & popd"
    ```
    Gunakan mapped drive atau `pushd` untuk mengelakkan isu `proc_open(NUL)` dan cwd UNC.
-   **Jalankan satu fail ujian:**
    ```bash
    php artisan test tests/Feature/Policies/TournamentPolicyTest.php
    ```
-   **Jalankan satu kaedah ujian:**
    ```bash
    php artisan test --filter=test_user_from_another_org_cannot_update_tournament
    ```

## 6. Garis Panduan untuk Pembangun

1.  **Tulis Ujian Dahulu (Jika Boleh):** Amalkan Pembangunan Berpandukan Ujian (TDD) untuk pepijat dan ciri-ciri kecil.
2.  **Setiap Ciri Baharu Mesti Ada Ujian:** Tiada kod baharu akan digabungkan (merged) tanpa ujian yang sepadan.
3.  **Setiap Pembetulan Pepijat Mesti Ada Ujian Regresi:** Tulis ujian yang gagal sebelum pembetulan, dan lulus selepas pembetulan. Ini memastikan pepijat itu tidak akan berulang.
4.  **Pastikan Semua Ujian Lulus:** Sebelum membuat *commit*, jalankan `php artisan test` untuk memastikan anda tidak memecahkan mana-mana bahagian sistem.
