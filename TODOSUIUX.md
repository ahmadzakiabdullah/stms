# TODO UI/UX — Public Portal SAF UTeM

Dokumen ini ialah pelan tindakan susulan audit UI/UX pada public portal SAF UTeM:

- `/`
- `/sports-programme`
- `/medal-tally`
- `/schedules`
- `/results`
- `/contact-us`

## Status Audit

- Kesihatan UI/UX keseluruhan: **Baik, perlu penambahbaikan**
- Responsive UX: **Baik secara struktur**
- Accessibility: **Perlu penambahbaikan**
- Keadaan kandungan awam semasa audit: **Data pertandingan belum bermula / masih kosong**

## Status Pelaksanaan

Setakat 12 Ogos 2026, pembaikan berikut telah dilaksanakan dalam public portal:

- Empty state utama kini menggunakan status pertandingan yang lebih jelas.
- Progress bar tidak dipaparkan apabila jumlah fixture masih sifar.
- Navigasi mobile kini merangkumi Sports Programme.
- Touch target mobile utama ditetapkan kepada minimum 44px tinggi.
- Navigasi public page mempunyai `aria-current` dan `aria-label`.
- Empty state mempunyai semantik `role="status"`.
- Contact Us mempunyai CTA ke Schedule dan Portal Home.
- Terjemahan English/Bahasa Malaysia ditambah untuk status baharu.

Masih belum selesai:

- UI refresh failure dan status `Refreshing…` yang lebih jelas.
- Audit axe/WCAG penuh untuk semua enam route.
- Ujian visual sebenar pada mobile, tablet, dan desktop.
- Contact details seperti telefon, e-mel, waktu operasi, dan peta.

## Prinsip Pelaksanaan

- Kekalkan React + Inertia.js + TypeScript + Tailwind CSS + shadcn/ui.
- Jangan tambah UI framework baharu.
- Gunakan komponen sedia ada sebelum mencipta komponen baharu.
- Semua kandungan kosong mesti menjelaskan sebab dan tindakan seterusnya.
- Semua kawalan sentuh mesti sekurang-kurangnya 44×44px.
- Pastikan makna status tidak bergantung kepada warna sahaja.
- Semua perubahan mesti diuji pada mobile, tablet, desktop, keyboard, dan axe/WCAG.

---

## P0 — Kritikal Sebelum Portal Dipromosikan

### UIX-P0-01 — Perbaiki empty state dan status pertandingan

**Masalah:** `0%`, `0/0 matches`, dan halaman kosong boleh kelihatan seperti sistem gagal.

**Tindakan:**

- Bezakan status `Belum bermula`, `Sedang berlangsung`, `Selesai`, dan `Data sedang dikemas kini`.
- Paparkan tarikh pertandingan dengan jelas.
- Gunakan mesej seperti: “Pertandingan belum bermula. Jadual rasmi akan diterbitkan sebelum tarikh pertandingan.”
- Jangan jadikan progress `0%` sebagai mesej utama sebelum fixture wujud.
- Tambah CTA ke Sports Programme, Schedule, dan Contact.

**Kriteria penerimaan:**

- Pengguna boleh faham keadaan portal tanpa meneka.
- Empty state menjelaskan sebab, masa kemas kini, dan tindakan seterusnya.
- Tiada paparan kosong tanpa penerangan pada enam route awam.

### UIX-P0-02 — Status refresh dan freshness data

**Masalah:** Auto-refresh wujud tetapi status refresh berjaya atau gagal tidak cukup jelas.

**Tindakan:**

- Paparkan `Last updated` secara konsisten.
- Tambah state `Refreshing…` dan `Unable to refresh`.
- Tambah butang `Refresh now` pada halaman data.
- Pastikan kegagalan refresh tidak memadam data yang sedang dipaparkan.

**Kriteria penerimaan:**

- Pengguna tahu bila data terakhir berjaya dimuatkan.
- Pengguna menerima feedback visual selepas menekan refresh.
- Error refresh mempunyai mesej yang membantu dan tidak menyalahkan pengguna.

### UIX-P0-03 — Ujian kandungan awam sebelum go-live pertandingan

**Masalah:** Data awam yang belum diterbitkan boleh menjejaskan kepercayaan pengguna.

**Tindakan:**

- Sahkan tarikh mula/tamat pertandingan.
- Sahkan semua venue, event, fixture, dan participant yang patut diterbitkan.
- Sediakan checklist penerbitan schedule, results, dan medal tally.
- Uji keadaan `belum ada data`, `data separa`, dan `data penuh`.

**Kriteria penerimaan:**

- Pemilik produk meluluskan kandungan sebelum pengumuman portal.
- Semua halaman menunjukkan status yang betul untuk setiap fasa pertandingan.

---

## P1 — Tinggi: Quick Wins (1–3 Hari)

### UIX-P1-01 — Seragamkan label navigasi

- Pilih satu label rasmi: `Sports Programme`, `Medal Tally`, `Schedule`, `Results`, `Contact`.
- Gunakan label yang sama pada desktop, mobile, heading, CTA, dan footer.
- Untuk Bahasa Malaysia, gunakan padanan yang konsisten seperti `Program Sukan`, `Kedudukan Pingat`, `Jadual`, `Keputusan`, `Hubungi`.

### UIX-P1-02 — Active navigation state

- Tambah active underline/background untuk route semasa.
- Tambah `aria-current="page"`.
- Pastikan active state turut wujud pada navigasi mobile.

### UIX-P1-03 — Lengkapkan navigasi mobile

- Pastikan Sports Programme boleh dicapai dari mobile.
- Nilai semula horizontal scroll navigation.
- Jika dikekalkan, tambah petunjuk visual bahawa menu boleh diswipe.
- Pertimbangkan hamburger menu untuk senarai penuh enam destinasi.

### UIX-P1-04 — Empty state dengan CTA

- Results: CTA ke Schedule dan Contact.
- Medal Tally: CTA ke Sports Programme dan Schedule.
- Schedule: CTA ke Sports Programme dan Contact.
- Sports Programme: mesej apabila program belum diterbitkan.

### UIX-P1-05 — Contact Us yang boleh diambil tindakan

- Tambah telefon clickable.
- Tambah e-mel clickable.
- Tambah pautan peta/lokasi.
- Tambah waktu operasi dan pautan UTeM Sports Centre jika tersedia.
- Pastikan alamat boleh disalin dengan mudah.

### UIX-P1-06 — Accessibility touch targets dan focus

- Semak semua link, button, select, language switcher, dan Login.
- Pastikan minimum 44×44px.
- Pastikan focus ring jelas pada keyboard.
- Pastikan disabled state masih boleh difahami tanpa warna sahaja.

---

## P1 — Tinggi: Ujian dan Pengesahan

### UIX-P1-07 — Audit WCAG 2.1 AA semua public route

- Jalankan axe pada enam route.
- Semak heading hierarchy.
- Semak accessible name semua ikon dan kawalan.
- Semak alt text logo organisasi dan logo kontingen.
- Semak kontras teks, link, placeholder, badge, dan status.
- Uji keyboard-only tanpa mouse.

### UIX-P1-08 — Ujian responsive sebenar

Uji sekurang-kurangnya:

- Mobile 320px, 375px, dan 430px.
- Tablet 768px dan 1024px.
- Desktop 1280px dan 1440px.
- Portrait dan landscape.

Semak:

- Tiada clipping atau horizontal scroll tidak disengajakan.
- CTA utama mudah dicapai menggunakan satu tangan.
- Header dan navigation tidak mengambil terlalu banyak ruang.
- Teks tidak terlalu kecil atau terlalu besar.

---

## P2 — Sederhana: Penambahbaikan 1–2 Minggu

### UIX-P2-01 — Medal Tally mobile layout

- Kekalkan table penuh pada desktop.
- Gunakan ranking cards pada mobile.
- Paparkan Rank, Contingent, Gold, dan Total sebagai maklumat utama.
- Letakkan Silver/Bronze dalam detail yang boleh dikembangkan.
- Paparkan jumlah hasil selepas search.

### UIX-P2-02 — Schedule discovery

- Tambah filter `Today`, `Tomorrow`, dan `All`.
- Tambah date selector.
- Paparkan jumlah fixture yang sepadan dengan filter.
- Paparkan filter aktif dan sediakan Reset yang mudah dilihat.
- Pertimbangkan `Add to calendar` apabila jadual rasmi tersedia.

### UIX-P2-03 — Results discovery

- Tambah filter Sport.
- Tambah status `Live`, `Completed`, dan `Upcoming`.
- Tambah sorting `Latest first`.
- Jadikan score lebih dominan pada mobile.

### UIX-P2-04 — Sports Programme discovery

- Tambah search “Cari sukan”.
- Tambah filter jika jumlah sukan meningkat.
- Gunakan accordion untuk event-event di mobile.
- Paparkan jumlah sukan dan acara dengan hierarchy yang jelas.

### UIX-P2-05 — Status TBD yang jelas

- Bezakan `TBD`, `Venue pending`, dan `Schedule pending` secara visual.
- Gunakan teks + icon/status, bukan warna sahaja.
- Pastikan status masih jelas pada grayscale dan high-contrast mode.

---

## P3 — Rendah / Jangka Panjang

### UIX-P3-01 — Live competition experience

- Live score dan live result updates.
- Status perlawanan masa nyata.
- Push notification untuk perubahan jadual.
- Paparan `Live now` pada home page.

### UIX-P3-02 — Calendar dan print support

- `Add to calendar` untuk fixture.
- Print-friendly schedule.
- Print-friendly results.
- Export atau share link untuk fixture tertentu.

### UIX-P3-03 — Analytics CRO

Ukur funnel berikut:

- Home → Schedule
- Home → Results
- Home → Sports Programme
- Schedule → Contact
- Mobile task completion

Pantau:

- CTA click-through rate.
- Search usage.
- Filter completion.
- Empty-state exit rate.
- Route exit rate.
- Masa untuk mencari satu fixture atau keputusan.

### UIX-P3-04 — Kandungan multilingual

- Audit terjemahan English dan Bahasa Malaysia.
- Pastikan label navigasi, empty state, status, tarikh, dan mesej error mempunyai terjemahan lengkap.
- Uji panjang teks Bahasa Malaysia pada mobile supaya tidak menyebabkan clipping.

---

## P4 — Full UI/UX Redesign

Workstream ini ialah redesign menyeluruh public portal, bukan sekadar pembaikan kecil. Ia perlu dilaksanakan selepas P0 dan P1 stabil supaya perubahan visual tidak menutup isu kandungan atau accessibility.

### UIX-P4-01 — Audit dan tetapkan design direction

- Tetapkan positioning portal: sumber rasmi untuk jadual, keputusan, program sukan, dan kedudukan pingat.
- Sahkan persona utama: atlet/peserta, pegawai fakulti, penyokong, urusetia, dan pengunjung awam.
- Tetapkan primary user tasks dan success metrics.
- Sediakan moodboard dan visual direction yang konsisten dengan identiti UTeM/SAF.
- Sahkan penggunaan emerald, slate, amber, dan status colors.

**Deliverable:** Design brief, visual direction, persona ringkas, dan senarai user task utama.

### UIX-P4-02 — Refactor design tokens dan komponen UI

- Wujudkan tokens untuk warna, spacing, radius, shadow, typography, focus ring, dan status.
- Seragamkan button variants, link states, badge, card, input, select, table, empty state, dan alert.
- Gunakan komponen reusable untuk public header, mobile navigation, footer, page hero, status card, filter bar, data card, dan empty state.
- Kurangkan JSX satu baris yang terlalu panjang supaya komponen mudah diaudit dan disenggara.
- Pastikan semua komponen menggunakan shadcn/ui, Radix, Tailwind, dan Lucide yang diluluskan.

**Deliverable:** Public design system mini dan katalog komponen reusable.

### UIX-P4-03 — Redesign information architecture

- Tetapkan navigasi utama:
  - Home
  - Sports Programme
  - Schedule
  - Results
  - Medal Tally
  - Contact
- Bezakan primary CTA, secondary CTA, dan utility action.
- Tambah breadcrumb pada halaman dalaman jika diperlukan.
- Tambah page title, description, last updated, dan status context yang konsisten.
- Pastikan desktop dan mobile menggunakan struktur navigasi yang sama.

**Deliverable:** Sitemap, navigation model, page hierarchy, dan user-flow diagram.

### UIX-P4-04 — Redesign public home page

- Hero yang menerangkan event, tarikh, status, dan nilai portal dalam satu pandangan.
- Primary CTA: `View Schedule`.
- Secondary CTA: `View Results` dan `Sports Programme`.
- Status panel dengan empat keadaan:
  - Before competition
  - Live competition
  - Completed competition
  - Data unavailable/error
- Quick access cards dengan active/hover/focus/disabled states.
- Section khas untuk next fixture, latest result, dan medal standings.
- Footer dengan navigation, contact, attribution, dan last updated context.

**Responsive requirement:**

- Mobile: single-column hero, full-width CTA, compact status card, sticky access to Schedule/Results.
- Tablet: two-column status and content blocks.
- Desktop: structured hero grid dengan clear visual priority.

### UIX-P4-05 — Redesign Sports Programme

- Search sukan.
- Filter kategori jika diperlukan.
- Sport cards dengan jumlah event dan event list yang mudah discan.
- Accordion pada mobile.
- Optional detail view untuk sport/event jika kandungan bertambah.
- Empty, loading, dan error states.

### UIX-P4-06 — Redesign Schedule

- Filter bar yang jelas untuk sport, venue, group, tarikh, dan status.
- Date navigation: Today, Tomorrow, dan All Dates.
- Grouping mengikut tarikh dengan sticky date header.
- Fixture card yang membezakan masa, venue, event, stage, dan status.
- `TBD` state yang jelas.
- Add to calendar dan print-friendly view sebagai enhancement.

### UIX-P4-07 — Redesign Results dan Medal Tally

**Results:**

- Status Live/Completed.
- Score-first layout.
- Filter sport dan date.
- Latest result highlighted.
- Empty state dengan pautan ke Schedule.

**Medal Tally:**

- Top-three podium treatment.
- Full ranking table pada desktop.
- Ranking cards pada mobile.
- Search contingent.
- Clear ranking rule explanation.
- Medal colors yang kekal jelas dalam grayscale/high contrast.

### UIX-P4-08 — Redesign Contact Us

- Contact cards untuk e-mel, telefon, lokasi, dan waktu operasi.
- Peta atau external map link.
- FAQ ringkas.
- Primary CTA untuk pertanyaan rasmi.
- Address copy action.
- Paparan fallback apabila contact settings belum lengkap.

### UIX-P4-09 — Accessibility-first redesign

- Sasaran WCAG 2.1 AA.
- Kontras teks minimum yang sesuai.
- Focus-visible untuk semua interactive elements.
- Keyboard navigation penuh.
- Screen reader labels dan landmark semantics.
- Reduced motion support.
- Touch target minimum 44×44px.
- Jangan gunakan warna sebagai satu-satunya petunjuk status.
- Uji dengan zoom 200% dan text enlargement.

### UIX-P4-10 — Responsive visual QA

Uji reka bentuk pada:

- 320px, 375px, 430px mobile.
- 768px, 1024px tablet.
- 1280px, 1440px desktop.
- Portrait dan landscape.
- Data kosong, data separa, dan data penuh.
- Nama event panjang, venue panjang, dan logo yang tiada.

### UIX-P4-11 — Prototype, implementation, dan migration

- Sediakan wireframe low-fidelity.
- Sediakan high-fidelity desktop/mobile prototype.
- Validasi prototype dengan wakil pengguna.
- Pecahkan implementation kepada komponen dan route secara berperingkat.
- Kekalkan route awam sedia ada untuk mengelakkan link rosak.
- Sediakan rollback point sebelum deployment.

### UIX-P4-12 — Redesign release gate

- Semua enam route menggunakan design language yang sama.
- Tiada duplicate navigation pattern yang mengelirukan.
- Semua primary task boleh diselesaikan dalam maksimum tiga interaksi utama.
- Empty/loading/error states lengkap.
- Accessibility audit lulus tanpa isu kritikal.
- Responsive visual review diluluskan pada semua breakpoint.
- Performance budget tidak merosot selepas penambahan visual.
- Product owner meluluskan prototype dan hasil production QA.

---

## Urutan Pelaksanaan Disyorkan

1. Laksanakan UIX-P0-01 hingga UIX-P0-03.
2. Laksanakan UIX-P1-01 hingga UIX-P1-06.
3. Jalankan UIX-P1-07 dan UIX-P1-08 sebagai release gate.
4. Laksanakan UIX-P2-01 hingga UIX-P2-05.
5. Tambah analytics CRO sebelum membuat keputusan reka bentuk seterusnya.
6. Masukkan ciri live, calendar, print, dan multilingual sebagai backlog jangka panjang.

## Release Gate Public Portal

Public portal tidak dianggap siap untuk promosi rasmi sehingga semua syarat berikut dipenuhi:

- Empty state menjelaskan status sebenar pertandingan.
- Schedule, Results, dan Medal Tally menunjukkan timestamp kemas kini.
- Semua enam route boleh dicapai dari desktop dan mobile.
- Active navigation state wujud.
- Semua touch target utama sekurang-kurangnya 44×44px.
- Tiada isu kritikal axe/WCAG.
- Keyboard navigation berfungsi.
- Tiada horizontal scroll yang tidak disengajakan.
- Contact Us mempunyai sekurang-kurangnya satu saluran hubungan langsung.
- Ujian mobile dan desktop lulus pada data kosong dan data penuh.
