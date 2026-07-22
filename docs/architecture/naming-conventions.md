# Database Naming Conventions

Dokumen ini menetapkan konvensyen penamaan yang mesti dipatuhi untuk semua elemen pangkalan data dalam aplikasi STMS. Tujuannya adalah untuk memastikan konsistensi, kebolehbacaan, dan penyelenggaraan yang mudah.

## 1. Umum

-   **Bahasa:** Gunakan Bahasa Inggeris.
-   **Kes:** Gunakan `snake_case` untuk semua nama jadual, lajur, dan indeks. (cth: `event_participants`, `start_date`).

## 2. Jadual (Tables)

-   **Nama:** Gunakan bentuk jamak (plural) dan `snake_case`.
    -   **Contoh Baik:** `tournaments`, `event_participants`, `users`.
    -   **Contoh Buruk:** `Tournament`, `event_participant`, `tbl_users`.

## 3. Lajur (Columns)

-   **Nama:** Gunakan `snake_case`. Nama harus deskriptif tetapi ringkas.
-   **Primary Key:** Sentiasa gunakan nama `id`. Dalam aplikasi ini, jenisnya adalah `UUID`.
-   **Foreign Key:** Gunakan nama model tunggal diikuti dengan `_id`.
    -   **Contoh:** Lajur dalam jadual `events` yang merujuk kepada `tournaments` harus dinamakan `tournament_id`.
-   **Timestamps:** Gunakan nama standard Laravel: `created_at` dan `updated_at`.
-   **Soft Deletes:** Gunakan nama standard Laravel: `deleted_at`.
-   **Boolean:** Gunakan awalan `is_` atau `has_`.
    -   **Contoh:** `is_active`, `is_public`, `has_results`.

## 4. Hubungan (Relationships)

### Jadual Pivot (Pivot Tables)

-   **Nama:** Gabungan nama kedua-dua model dalam bentuk tunggal (singular), disusun mengikut abjad, dan dipisahkan oleh `_`.
    -   **Contoh:** Hubungan antara `tournaments` dan `sports` menggunakan jadual pivot bernama `sport_tournament`.
    -   **Contoh Buruk:** `tournaments_sports`, `rel_sport_tournament`.

### Kaedah Relasi (Eloquent)

-   **Nama:** Gunakan `camelCase`.
-   **One-to-Many / Many-to-One:** Gunakan nama model yang berkaitan. Jika ia adalah hubungan `hasMany`, gunakan bentuk jamak. Jika ia adalah `belongsTo`, gunakan bentuk tunggal.
    -   **Contoh:** Dalam model `Tournament`, `public function events(): HasMany`. Dalam model `Event`, `public function tournament(): BelongsTo`.
-   **Many-to-Many:** Gunakan bentuk jamak bagi nama model yang berkaitan.
    -   **Contoh:** Dalam model `Tournament`, `public function sports(): BelongsToMany`.

## 5. Indeks (Indexes)

-   **Nama:** Gunakan nama yang deskriptif dan standard.
-   **Format:** `{type}_{table}_{columns}`.
    -   `{type}`: `ix` untuk indeks biasa, `ux` untuk indeks unik, `fk` untuk kunci asing.
    -   `{table}`: Nama jadual.
    -   `{columns}`: Nama lajur yang diindeks, dipisahkan oleh `_`.

    -   **Contoh Indeks Biasa:** `ix_matches_event_id` (indeks pada lajur `event_id` dalam jadual `matches`).
    -   **Contoh Indeks Unik:** `ux_sports_organization_id_slug` (indeks unik pada lajur `organization_id` dan `slug` dalam jadual `sports`).

## 6. Migrasi (Migrations)

-   **Nama Fail:** Gunakan format yang dijana oleh Laravel. Nama harus menerangkan tindakan yang dilakukan.
    -   **Contoh:** `2026_06_25_000001_create_users_table.php`.
    -   **Contoh:** `2026_06_26_000002_add_ranking_strategy_to_tournaments_table.php`.