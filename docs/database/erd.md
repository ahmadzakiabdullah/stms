# Entity Relationship Descriptions (ERD)

Dokumen ini menerangkan hubungan antara entiti-entiti data utama dalam aplikasi STMS. Ia berfungsi sebagai pelengkap kepada `schema.md` dengan menjelaskan logik di sebalik kunci asing (foreign keys) dan kardinaliti (cardinality) hubungan.

## Hubungan Teras (Core Relationships)

### 1. Organization (Penyewa / Tenant)

-   **Organization** adalah entiti akar untuk seni bina multi-tenancy.
-   Sebuah **Organization** mempunyai banyak **Users**.
-   Sebuah **Organization** mempunyai banyak **Tournaments**.
-   Sebuah **Organization** mempunyai banyak **Sports**.
-   Sebuah **Organization** mempunyai banyak **Participants**.

### 2. User

-   Seorang **User** tergolong dalam **satu** **Organization** sahaja.
    -   `users.organization_id` → `organizations.id` (One-to-Many, Inversed)

### 3. Tournament (Kejohanan)

-   Sebuah **Tournament** tergolong dalam **satu** **Organization** sahaja.
    -   `tournaments.organization_id` → `organizations.id` (One-to-Many, Inversed)
-   Sebuah **Tournament** mempunyai banyak **Events**.
    -   `events.tournament_id` → `tournaments.id` (Many-to-One)
-   Sebuah **Tournament** mempunyai dan tergolong dalam banyak **Sports**.
    -   Hubungan ini diuruskan melalui jadual pivot `tournament_sport`.

### 4. Sport (Sukan)

-   Sebuah **Sport** tergolong dalam **satu** **Organization** sahaja.
    -   `sports.organization_id` → `organizations.id` (One-to-Many, Inversed)
-   Sebuah **Sport** mempunyai dan tergolong dalam banyak **Tournaments**.
    -   Hubungan ini diuruskan melalui jadual pivot `tournament_sport`.

### 5. Event (Acara)

-   **Event** mewakili satu pertandingan spesifik dalam sesebuah kejohanan (cth: "Perseorangan Lelaki Bawah 18 Tahun").
-   Sebuah **Event** tergolong dalam **satu** **Tournament**.
    -   `events.tournament_id` → `tournaments.id` (Many-to-One)
-   Sebuah **Event** tergolong dalam **satu** **Sport**.
    -   `events.sport_id` → `sports.id` (Many-to-One)
-   Sebuah **Event** mempunyai banyak **EventParticipants** (pendaftaran untuk acara ini).
-   Sebuah **Event** mempunyai banyak **Fixtures** (perlawanan).

### 6. Participant (Peserta)

-   **Participant** boleh mewakili seorang individu atau satu pasukan.
-   Seorang **Participant** tergolong dalam **satu** **Organization** sahaja.
    -   `participants.organization_id` → `organizations.id` (One-to-Many, Inversed)
-   Seorang **Participant** boleh menyertai banyak **Events**.
    -   Hubungan ini diuruskan melalui jadual `event_participants`.

### 7. EventParticipant (Pendaftaran)

-   Model ini bertindak sebagai penghubung antara **Event** dan **Participant**, mewakili satu pendaftaran.
-   Sebuah **EventParticipant** tergolong dalam **satu** **Event**.
    -   `event_participants.event_id` → `events.id` (Many-to-One)
-   Sebuah **EventParticipant** tergolong dalam **satu** **Participant**.
    -   `event_participants.participant_id` → `participants.id` (Many-to-One)

### 8. Fixture (Perlawanan)

-   **Fixture** (jadual `matches`) mewakili satu perlawanan yang dijadualkan.
-   Sebuah **Fixture** tergolong dalam **satu** **Event**.
    -   `matches.event_id` → `events.id` (Many-to-One)
-   Sebuah **Fixture** mempunyai **satu** **Result** (boleh jadi `null` jika belum dimainkan).
    -   `matches.result_id` → `results.id` (One-to-One, Nullable)
-   Sebuah **Fixture** melibatkan dua **Participants** (`participant1_id` dan `participant2_id`).