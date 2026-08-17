# Security Policy

## Supported Versions

Pada 17 Ogos 2026 repositori belum mempunyai Git release tag. Tiada siri versi yang boleh didakwa sebagai supported sehingga release pertama diluluskan dan tag diterbitkan. Deployment semasa hendaklah dianggap pre-release/operational MVP.

## Reporting a Vulnerability

Jangan buka isu awam atau masukkan exploit, credential, data peribadi atau URL bertoken dalam tiket umum. Laporkan secara private kepada pemilik sistem/ICT UTeM melalui saluran keselamatan institusi yang diluluskan. Alamat khusus belum dikonfigurasi dalam repositori; maintainer mesti menambah contact sebenar sebelum release.

Sertakan:

- ringkasan dan impak;
- langkah reproduksi minimum;
- affected route/role/tenant tanpa data sensitif;
- bukti yang telah disanitasi;
- cadangan mitigasi jika ada.

Jangan menguji destructive exploit, mengeksfiltrasi data tenant atau menjalankan load test production tanpa kebenaran bertulis.

## Current Security Status

Dependency audits semasa bersih pada tahap yang diuji, tetapi release masih `NO-GO`. Audit 17 Ogos menemui jurang read authorization (`viewAny`), CSP production masih report-only, pemilihan favicon tetamu tidak explicit tenant, DB least privilege belum dibuktikan, dan email verification dimatikan dalam runtime yang diaudit.

Lihat [security overview](docs/security/overview.md) dan [audit penuh](docs/audits/2026-08-17-full-project-and-production-audit.md). Jangan menyalin vulnerability detail ke changelog awam sebelum mitigasi tersedia.
