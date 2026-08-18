# Monitoring

## Endpoint dan Command

- `GET /up` ialah liveness Laravel asas.
- `GET /health` memeriksa database, cache, queue dan ruang disk. Endpoint ini dilindungi token dan sengaja memberi 404 tanpa token.
- `php artisan stms:health-check` menyediakan semakan operasi yang sama untuk scheduler/CLI.
- `php artisan stms:release-preflight --json` memeriksa prerequisite release secara tidak merosakkan, termasuk DB/Redis connectivity, backup freshness dan konfigurasi monitoring.

Semasa audit asal 17 Ogos, health command berstatus `ok` walaupun mempunyai 32 queued jobs. Backlog itu kemudian diproses kepada 0 pending/0 failed dan health kekal lulus. Satu drain berjaya masih bukan bukti worker/scheduler diselia secara berterusan.

## Belum Dibuktikan

Sentry/APM, alert routing, external uptime monitor, dashboard latency, slow-query alert, queue-age alert dan on-call response tidak dibuktikan aktif. Dokumen atau dependency cadangan bukan bukti operasi.

Minimum production monitoring hendaklah merangkumi availability, error rate, request latency, DB/cache latency, queue depth dan oldest-job age, failed jobs, disk, backup freshness, certificate expiry dan CSP reports. Setiap alert mesti mempunyai pemilik dan runbook.
