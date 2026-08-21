# Design UI/UX — Sistem Keputusan SUKMA 2026

## 1. Konsep Design

- Gaya: moden, rasmi dan berorientasikan data
- Fokus utama: jadual, keputusan, acara langsung dan pungutan pingat
- Bahasa: Bahasa Malaysia
- Platform: Desktop, tablet dan mobile

## 2. Struktur Halaman Utama

```text
┌──────────────────────────────────────────────┐
│ LOGO       UTAMA  JADUAL  ATLET  PINGAT  BM  │
├──────────────────────────────────────────────┤
│ SUKMA XXII SELANGOR 2026                     │
│ SISTEM KEPUTUSAN                              │
│                                              │
│ [ LIHAT JADUAL & KEPUTUSAN PENUH ]           │
├──────────────────────────────────────────────┤
│ KEPUTUSAN TERKINI                            │
│ [Semua Sukan ▼]                              │
│ Tiada keputusan tersedia                     │
├──────────────────────┬───────────────────────┤
│ ACARA LANGSUNG       │ ACARA PINGAT HARI INI │
│ Tiada acara live     │ Tiada acara pingat    │
├──────────────────────┴───────────────────────┤
│ PENERANGAN SUKMA 2026                         │
├──────────────────────────────────────────────┤
│ FOOTER                                        │
└──────────────────────────────────────────────┘
```

## 3. Hierarki Visual

| Elemen | Spesifikasi |
|---|---|
| H1 | 36px, bold |
| H2 | 24px, semibold |
| Body | 16px, regular |
| Caption | 12–14px |
| Primary button | Warna utama, teks putih |
| Secondary button | Border warna utama |
| Card | Radius 12px, shadow ringan |
| Spacing | Sistem 8px: 8, 16, 24, 32px |

## 4. Warna Cadangan

| Warna | Kegunaan |
|---|---|
| Biru gelap | Header, navigasi dan teks utama |
| Merah / oren | Status live dan CTA penting |
| Emas | Pingat emas |
| Kelabu cerah | Latar kad dan section |
| Putih | Latar utama |
| Hijau | Status selesai / berjaya |

## 5. Komponen UI

### Button

```text
Primary:
[ LIHAT JADUAL & KEPUTUSAN PENUH ]

Secondary:
[ Lihat Butiran ]

Live:
[ ● LIVE ]
```

Keadaan:

- Default
- Hover
- Focus
- Active
- Disabled
- Loading

### Kad Keputusan

```text
Bola Keranjang 3x3
Separuh Akhir

Selangor       78
Johor          65

Tamat · 20 Ogos 2026
[ Lihat Butiran ]
```

### Kad Acara Live

```text
● LIVE

Badminton
Perseorangan Lelaki

Venue: Kompleks Sukan Selangor
Mula: 8:00 malam

[ Tonton / Lihat Butiran ]
```

### Jadual Pingat

```text
┌────┬────────────┬──────┬──────┬──────┬──────┐
│ #  │ Kontinjen  │ Emas │Perak │Gangsa│Jumlah│
├────┼────────────┼──────┼──────┼──────┼──────┤
│ 1  │ Selangor   │  12  │  8   │  10   │  30  │
└────┴────────────┴──────┴──────┴──────┴──────┘
```

## 6. Responsive Design

### Desktop

- Navigasi penuh.
- Dashboard dua atau tiga kolum.
- Jadual menggunakan table grid.
- Sidebar filter tersedia.

### Tablet

- Dashboard dua kolum.
- Jadual boleh discroll secara mendatar.
- Kad disusun dua kolum.

### Mobile

```text
[☰] LOGO                         [BM]

SUKMA XXII SELANGOR 2026

[ LIHAT JADUAL ]

KEPUTUSAN TERKINI
[ Semua Sukan ▼ ]

ACARA LANGSUNG

ACARA PINGAT HARI INI
```

## 7. User Flow

```text
Home
 ↓
Pilih Jadual
 ↓
Pilih Tarikh
 ↓
Pilih Disiplin
 ↓
Pilih Acara
 ↓
Lihat Keputusan / Butiran
```

## 8. UX State

### Loading

```text
Memuatkan keputusan...
[ Skeleton card ]
```

### Empty State

```text
Tiada keputusan tersedia pada masa ini.

Sila semak semula semasa pertandingan berlangsung.
```

### Error State

```text
Maklumat tidak dapat dimuatkan.

[ Cuba Lagi ]
```

## 9. Aksesibiliti

- Semua button mempunyai label yang jelas.
- Semua elemen boleh digunakan dengan keyboard.
- Status `LIVE` tidak bergantung kepada warna sahaja.
- Table mempunyai header yang betul.
- Kontras teks dan latar memenuhi standard aksesibiliti.
- Gunakan `aria-live` untuk keputusan masa nyata.

## 10. Kriteria Design

- Pengguna boleh mencapai jadual dalam maksimum dua klik.
- Status acara mudah dikenal pasti.
- Susun atur kekal jelas pada mobile.
- Data kosong mempunyai mesej penerangan.
- Semua komponen mempunyai hover, focus dan loading state.