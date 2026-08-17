# File Storage

Laravel filesystem digunakan untuk logo peserta, favicon/branding dan fail aplikasi lain. Pelaksanaan semasa menggunakan disk `public` tempatan dengan symlink/route penyampaian yang sesuai kepada deployment IIS.

## Peraturan

- Simpan hanya path relatif dalam pangkalan data.
- Sahkan MIME, extension dan saiz; jangan percaya nama fail pengguna.
- Gunakan nama rawak/UUID dan semak authorization untuk upload, replace dan delete.
- Logo peserta menyokong `logo_path` dan pilihan `inverse_logo_path` untuk permukaan gelap.
- Aset tenant mesti dibaca dan diubah dalam konteks organisasi yang betul.
- Jangan padam fail lama sehingga update DB berjaya; sediakan cleanup terukur untuk orphan.

Konfigurasi `public` semasa adalah local, jadi “cloud-ready” belum dibuktikan hanya dengan menukar environment variable. Migrasi ke object storage memerlukan konfigurasi disk, URL/signing, CORS, lifecycle, backup dan ujian route penyampaian fail.
