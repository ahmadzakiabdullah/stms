# Multi-Tenancy Architecture

Dokumen ini menerangkan pendekatan dan pelaksanaan seni bina multi-tenancy dalam aplikasi STMS.

## 1. Pendekatan yang Dipilih: Pangkalan Data Tunggal, Skema Dikongsi

Aplikasi STMS menggunakan model multi-tenancy **Pangkalan Data Tunggal, Skema Dikongsi (Single Database, Shared Schema)**. Ini bermakna semua data untuk semua organisasi ("tenant") disimpan dalam satu pangkalan data yang sama. Pengasingan data dicapai pada peringkat aplikasi.

### Sebab Pemilihan

- **Kos-Efektif:** Menggunakan satu pangkalan data adalah lebih murah untuk dihoskan dan diselenggara berbanding pangkalan data berasingan untuk setiap tenant.
- **Penyelenggaraan Mudah:** Perubahan skema (migrations) hanya perlu dijalankan sekali sahaja.
- **Pengurusan Mudah:** Proses sandaran (backup) dan pemulihan (recovery) adalah lebih ringkas.

### Kelemahan & Mitigasi

- **Risiko Kebocoran Data:** Terdapat risiko data satu tenant terdedah kepada tenant lain jika terdapat kesilapan dalam kod.
  - **Mitigasi:** Penggunaan Global Scopes, Policies, dan ujian yang ketat untuk menguatkuasakan pengasingan data.
- **Noisy Neighbor Problem:** Satu tenant yang sangat aktif boleh menjejaskan prestasi tenant lain.
  - **Mitigasi:** Pemantauan prestasi dan pengoptimuman query. Boleh diuruskan dengan strategi caching pada masa hadapan.

## 2. Pelaksanaan Teknikal

Pengasingan data dikuatkuasakan melalui beberapa lapisan dalam aplikasi.

### 2.1. Lajur `organization_id`

Setiap jadual yang mengandungi data spesifik-tenant **mesti** mempunyai lajur `organization_id` (UUID, Foreign Key ke jadual `organizations`). Ini adalah pengenal pasti tenant untuk setiap baris data.

Contoh jadual: `tournaments`, `participants`, `sports`, `events`, `users`, dll.

### 2.2. Trait `BelongsToOrganization`

Satu trait Eloquent, `App\Models\Concerns\BelongsToOrganization`, digunakan pada semua model spesifik-tenant. Trait ini bertanggungjawab untuk:

1.  **Menerapkan Global Scope:** Secara automatik menambah klausa `WHERE organization_id = ?` pada semua query Eloquent. Ini adalah mekanisme utama untuk pengasingan data secara automatik.
2.  **Menyediakan skop eksplisit:** Trait menyediakan `forOrganization()` dan `withoutOrganizationScope()` untuk operasi yang telah diberi kuasa. `organization_id` semasa penciptaan ditetapkan secara eksplisit oleh Service/Action; trait tidak mengisi medan ini secara automatik.
3.  **Menyediakan Relasi:** Mendefinisikan relasi `belongsTo(Organization::class)`.

### 2.3. Policies

Walaupun Global Scope menghalang *pembacaan* data tenant lain, ia tidak menghalang *penulisan* jika pengguna dapat meneka ID rekod. Oleh itu, **Policies (`app/Policies`)** adalah lapisan keselamatan kedua yang kritikal.

Setiap kaedah dalam Policy (cth: `update`, `delete`, `view`) **mesti** mengesahkan bahawa organisasi pengguna sepadan dengan `organization_id` pada rekod yang ingin diakses.

**Contoh (dalam `TournamentPolicy@update`):**
```php
public function update(User $user, Tournament $tournament): bool
{
    // Pengguna hanya boleh mengemas kini kejohanan milik organisasinya sendiri.
    return $user->organization_id === $tournament->organization_id;
}
```

### 2.4. Pengesahan (Validation)

Dalam `Form Requests`, peraturan pengesahan seperti `exists` atau `unique` perlu diskopkan kepada organisasi semasa untuk mengelakkan kebocoran maklumat.

**Contoh:**
```php
Rule::unique('sports', 'slug')->where(function ($query) {
    return $query->where('organization_id', auth()->user()->organization_id);
})
```

## 3. Amalan Terbaik untuk Pembangun

1.  **Sentiasa Guna Trait:** Semua model baharu yang menyimpan data tenant mesti menggunakan trait `BelongsToOrganization`.
2.  **Kuatkuasakan Policy:** Jangan sekali-kali melangkau semakan kebenaran dalam Policy. Setiap tindakan mesti disahkan.
3.  **Elakkan Raw Query:** Elakkan menggunakan `DB::raw()` atau query mentah yang lain kerana ia melangkau Global Scope Eloquent. Jika perlu, pastikan anda menambah klausa `WHERE organization_id = ?` secara manual.
4.  **Ujian Adalah Wajib:** Tulis ujian *feature* yang spesifik untuk mengesahkan bahawa seorang pengguna dari `Organization A` tidak boleh melihat, mencipta, mengemas kini, atau memadam data milik `Organization B`.
