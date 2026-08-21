# Susun Atur UI/UX — Sistem Keputusan SUKMA Selangor 2026

## 1. Objektif Portal

Portal ini berfungsi sebagai sistem rasmi untuk:

- melihat jadual pertandingan;
- menyemak keputusan terkini;
- mengikuti acara secara langsung;
- melihat pungutan pingat;
- mencari atlet, pasukan dan kontinjen;
- melihat lokasi pertandingan.

## 2. Pengguna Sasaran

| Pengguna | Keperluan utama |
|---|---|
| Penonton | Jadual, keputusan dan acara langsung |
| Atlet | Maklumat pertandingan dan keputusan individu |
| Pegawai kontinjen | Data atlet, pasukan dan pungutan pingat |
| Media | Keputusan, statistik dan status acara |
| Penganjur | Paparan data pertandingan secara masa nyata |

## 3. Seni Bina Maklumat

```text
Utama
├── Ringkasan Keputusan
├── Keputusan Terkini
├── Acara Langsung
└── Acara Pingat Hari Ini

Jadual
├── Paparan mengikut hari
├── Paparan mengikut disiplin
└── Butiran pertandingan

Atlet
├── Atlet
├── Pasukan
├── Kontinjen
└── Carian atlet

Pingat
├── Kedudukan pingat
├── Semua disiplin
├── Butiran pingat
└── Multi-medali

Lokasi
└── Senarai venue pertandingan

Bahasa
├── Bahasa Malaysia
└── English
```

## 4. Susun Atur Global

### 4.1 Event Banner

Paparkan maklumat acara di bahagian paling atas:

```text
SUKMA XXII SELANGOR 2026
15–24 Ogos 2026

PARA SUKMA 2026
5–14 September 2026
```

Fungsi:

- mengesahkan identiti portal;
- memaparkan tarikh rasmi;
- membantu pengguna membezakan SUKMA dan Para SUKMA.

### 4.2 Header

Kandungan header:

- logo SUKMA Selangor;
- nama sistem keputusan;
- penukar bahasa BM / EN;
- navigasi utama.

Navigasi utama:

```text
UTAMA | JADUAL | ATLET | PINGAT | LOKASI
```

Pada mobile:

- gunakan hamburger menu;
- kekalkan logo dan penukar bahasa;
- jadikan navigasi boleh ditatal secara mendatar jika perlu.

### 4.3 Footer

Kandungan footer:

- penerangan ringkas SUKMA 2026;
- pautan utama;
- maklumat hak cipta;
- versi sistem;
- logo atau identiti rasmi SUKMA.

## 5. Halaman Utama

### 5.1 Hero / Dashboard Header

Paparkan:

```text
SUKMA XXII SELANGOR 2026
SISTEM KEPUTUSAN

[LIHAT JADUAL & KEPUTUSAN PENUH]
```

CTA utama perlu membawa pengguna terus ke halaman jadual atau keputusan penuh.

### 5.2 Keputusan Terkini

Paparkan ringkasan keputusan terkini:

```text
KEPUTUSAN TERKINI

[Semua Sukan]

Tiada keputusan selesai tersedia pada masa ini.

Sila semak semula semasa pertandingan langsung
untuk kemas kini masa nyata.
```

Komponen yang diperlukan:

- dropdown semua sukan;
- kad atau senarai keputusan;
- status selesai;
- tarikh dan masa keputusan;
- pautan ke butiran acara.

Keadaan UI:

- loading;
- data tersedia;
- tiada keputusan;
- ralat sambungan.

### 5.3 Acara Langsung

Paparkan acara yang sedang berlangsung:

```text
ACARA LANGSUNG

[LIVE]

Tiada acara langsung pada masa ini.

Sila semak semula semasa waktu pertandingan
dijadualkan untuk kemas kini langsung masa nyata.

[Ke Butiran Acara]
```

Apabila acara tersedia:

```text
[LIVE] Nama Acara
Disiplin · Venue
Masa mula

[Ke Butiran Acara]
```

Gunakan warna dan label yang jelas untuk menunjukkan status `LIVE`.

### 5.4 Acara Pingat Hari Ini

Paparkan acara yang menawarkan pingat pada hari semasa:

```text
ACARA PINGAT HARI INI

Tiada acara pingat dijadualkan untuk hari ini.

Semak jadual penuh untuk acara pingat akan datang.
```

Jika tersedia:

```text
Bola Keranjang 3x3
13 Ogos 2026
Venue · Masa pertandingan

[Lihat Jadual]
```

## 6. Halaman Jadual

### 6.1 Tajuk Halaman

```text
JADUAL PERTANDINGAN
```

### 6.2 Paparan Kalendar

Paparkan jadual dalam bentuk timeline atau grid:

| Disiplin | 15 Sab | 16 Ahd | 17 Isn | 18 Sel | 19 Rab |
|---|---:|---:|---:|---:|---:|
| Upacara | ● |  |  |  |  |
| Akuatik | ● | ● |  |  |  |
| Badminton |  | ● | ● |  |  |

Keperluan UX:

- scroll mendatar untuk tarikh yang banyak;
- tarikh semasa ditonjolkan;
- acara langsung mempunyai indikator LIVE;
- klik pada acara membuka halaman butiran.

### 6.3 Penapis Jadual

Sediakan penapis:

- Semua hari;
- tarikh tertentu;
- disiplin;
- venue;
- acara pingat;
- acara langsung.

Pada mobile, penapis boleh dipaparkan sebagai bottom sheet atau dropdown.

### 6.4 Kad Acara

Setiap acara perlu memaparkan:

```text
Nama acara
Disiplin
Tarikh · Masa
Venue
Status

[Lihat Butiran]
```

Keadaan status:

- Akan datang;
- Sedang berlangsung;
- Tamat;
- Ditunda;
- Dibatalkan.

## 7. Halaman Atlet

### 7.1 Tab Kategori

Gunakan tiga tab utama:

```text
[Atlet] [Pasukan] [Kontinjen]
```

### 7.2 Carian dan Penapis

Komponen:

```text
[Cari atlet...]

[Semua Disiplin]
[Semua Kontinjen]
[A → Z]
```

Fungsi:

- carian berdasarkan nama;
- tapis berdasarkan disiplin;
- tapis berdasarkan kontinjen;
- susun mengikut abjad.

### 7.3 Kad Atlet

```text
Foto atlet
Nama atlet
Kontinjen
Disiplin
Bilangan acara

[Lihat Profil]
```

### 7.4 Empty State

```text
Tiada atlet ditemui.

Cuba ubah penapis atau kata carian anda.
```

## 8. Halaman Pingat

### 8.1 Tajuk dan Timestamp

```text
KEDUDUKAN PINGAT

Setakat 12 Ogos 2026, 09:05 PTG
Local Time
```

Paparkan timestamp bagi meningkatkan kepercayaan terhadap data langsung.

### 8.2 Penapis Pingat

Sediakan:

- Semua disiplin;
- Lihat Pingat;
- Lihat Butiran;
- Multi-medali.

### 8.3 Jadual Kedudukan

| Kedudukan | Kontinjen | Emas | Perak | Gangsa | Jumlah |
|---:|---|---:|---:|---:|---:|
| 1 | Selangor | 0 | 0 | 0 | 0 |
| 2 | Johor | 0 | 0 | 0 | 0 |

Gunakan ikon atau warna konsisten untuk emas, perak dan gangsa.

### 8.4 Pungutan Pingat Akan Datang

```text
PUNGUTAN PINGAT: AKAN DATANG

Pingat pertama: Bola Keranjang 3X3
13 Ogos 2026

Kembali pada 13 Ogos untuk melihat
kedudukan pungutan pingat terkini.
```

## 9. Halaman Lokasi

Paparkan:

- nama venue;
- alamat;
- disiplin yang berlangsung;
- peta;
- pautan arah perjalanan;
- tarikh dan masa acara.

Contoh kad:

```text
Nama Venue
Alamat lokasi
Disiplin: Badminton
Acara seterusnya: 16 Ogos 2026

[Lihat Peta] [Lihat Jadual]
```

## 10. Sistem Komponen

| Komponen | Keadaan |
|---|---|
| Navigation | Default, active, hover, mobile open |
| Event card | Upcoming, live, completed, postponed |
| Result card | Loading, completed, empty, error |
| Medal table | Loading, updated, empty |
| Athlete card | Default, hover, no image |
| Filter | Closed, open, selected, reset |
| Tab | Active, inactive, focus |
| Search | Empty, typing, results, no results |
| Language switcher | BM active, EN active |

## 11. Responsif

### Desktop

- Navigasi mendatar penuh.
- Jadual menggunakan grid.
- Dashboard menggunakan beberapa kad dalam satu baris.
- Jadual pingat menggunakan jadual penuh.
- Sidebar boleh digunakan untuk filter.

### Tablet

- Dashboard menggunakan dua kolum.
- Jadual boleh discroll mendatar.
- Penapis disusun dalam dua baris.
- Kad atlet menggunakan dua atau tiga kolum.

### Mobile

- Header ringkas dengan hamburger menu.
- Dashboard menggunakan satu kolum.
- Jadual menggunakan horizontal scroll.
- Jadual pingat menggunakan scroll mendatar atau kad kontinjen.
- Filter menggunakan dropdown atau bottom sheet.
- CTA dan butang sekurang-kurangnya 44×44px.

## 12. Prinsip UX

- Utamakan data paling penting: live, keputusan dan jadual.
- Paparkan masa kemas kini bagi semua data dinamik.
- Bezakan dengan jelas acara akan datang, live dan tamat.
- Gunakan empty state yang menerangkan tindakan seterusnya.
- Kekalkan Bahasa Malaysia secara konsisten pada seluruh UI.
- Pastikan pautan ke butiran acara sentiasa mudah dicapai.
- Elakkan jadual yang terlalu padat pada skrin kecil.
- Sediakan loading state untuk mencegah paparan kosong yang mengelirukan.

## 13. Aksesibiliti

- Semua fungsi boleh digunakan melalui papan kekunci.
- Sediakan label untuk ikon, butang dan filter.
- Gunakan `aria-live` untuk kemas kini keputusan langsung.
- Gunakan `aria-selected` untuk tab.
- Gunakan `aria-expanded` untuk menu dan dropdown.
- Jangan bergantung kepada warna sahaja untuk status pertandingan.
- Pastikan teks mempunyai kontras yang mencukupi.
- Sediakan alt text untuk logo, ikon dan foto atlet.
- Pastikan jadual mempunyai header yang betul untuk pembaca skrin.

## 14. Aliran Pengguna Utama

```text
Pengguna tiba di portal
        ↓
Pilih tugasan
  ├─ Semak keputusan terkini
  ├─ Lihat acara langsung
  ├─ Cari jadual pertandingan
  ├─ Cari atlet
  ├─ Semak pungutan pingat
  └─ Cari lokasi venue
        ↓
Lihat butiran acara
        ↓
Kembali ke dashboard atau halaman berkaitan
```

## 15. Kriteria Penerimaan

- Pengguna boleh mengakses jadual dan keputusan dalam maksimum dua klik.
- Status live sentiasa mudah dikenal pasti.
- Data keputusan mempunyai timestamp kemas kini.
- Jadual boleh digunakan pada desktop dan mobile.
- Carian atlet memaparkan hasil atau empty state yang jelas.
- Jadual pingat mempunyai struktur yang boleh dibaca.
- Semua tab, filter dan dropdown mempunyai keadaan fokus.
- Bahasa Malaysia digunakan secara konsisten.
- Portal menyokong loading, empty dan error state.

## Sumber

- [RS — Sistem Keputusan SUKMA Selangor 2026](https://rs.selangor2026.com/bm)
- [Jadual Pertandingan](https://rs.selangor2026.com/bm/schedule)
- [Atlet](https://rs.selangor2026.com/bm/athletes)
- [Pingat](https://rs.selangor2026.com/bm/medals)