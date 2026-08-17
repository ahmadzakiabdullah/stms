# Pelan Pengukuhan STMS

> Dikemas kini 17 Ogos 2026 selepas remediation. Checklist aktif dan authority boundary berada di [`TODOS.md`](TODOS.md).

## Selesai dalam Repository

- [x] Policy/read authorization untuk index sensitif dan six-role direct-URL matrix.
- [x] Draw move behavior, scheduled fixture regeneration dan regression coverage.
- [x] HTTP/Inertia tenant-payload assertions sebenar.
- [x] Tenant-explicit guest branding, self-hosted fonts, SEO/sitemap dan guest Ziggy filtering.
- [x] Ranking contract/registry dengan session/tournament data rules.
- [x] Production configuration validation yang fail-closed untuk Redis, mail, verification, timezone, secure session dan CSP.
- [x] Queue backlog runtime dikosongkan kepada 0 pending/0 failed.
- [x] Guzzle/PSR-7 advisory ditutup.
- [x] PHPUnit 434/434, Pint, TypeScript, build, budget, dependency audits dan Playwright/axe 8/8 hijau.
- [x] Remediation dipecah kepada commit logik yang boleh disemak.
- [x] Connected CI #105 lulus pada commit `5bb86a8` untuk semua enam job; action rasmi v7 menutup amaran runtime Node.js 20.
- [x] Product owner mengesahkan tarikh, satu tournament, 30 acara, 8 kontinjen dan single-page IA; data operasi kekal boleh dikemas kini.
- [x] Contact rasmi Pusat Sukan disimpan sebagai tetapan tenant-editable dan dilindungi oleh validasi serta output filtering.

## Baki Release Operations

- [ ] Operator menyediakan real mail transport dan reset-password evidence.
- [ ] Deploy Redis cache/queue/session, Malaysia timezone, verification dan CSP enforcement melalui release runbook.
- [ ] DBA mengehadkan principal kepada schema STMS dan merekod grants.
- [ ] Off-host backup, deploy smoke dan release tag.

## Reliability Selepas Release

- [ ] Refactor baki query orchestration controller secara behavior-preserving.
- [ ] Authenticated multi-worker staging k6.
- [ ] External uptime/log alerts dengan operator receipt.
- [ ] Off-host isolated restore dengan RPO/RTO.
- [ ] PCOV baseline dan ratcheting threshold.

Capability masa depan seperti accreditation, live scoring, API, mobile dan AI kekal di luar milestone semasa.
