# Monitoring

## Endpoint dan Command

- `GET /up` ialah liveness Laravel asas.
- `GET /health` memeriksa database, cache, queue dan ruang disk. Endpoint ini dilindungi token dan sengaja memberi 404 tanpa token.
- `php artisan stms:health-check` menyediakan semakan operasi yang sama untuk scheduler/CLI.

Semasa audit 17 Ogos, health command berstatus `ok`: DB/cache/queue/disk boleh dicapai, 32 queued jobs dan 0 failed jobs. Ambang backlog 100 menyebabkan status kekal `ok`; angka ini bukan bukti worker memproses job pada kadar yang mencukupi.

## Belum Dibuktikan

Sentry/APM, alert routing, external uptime monitor, dashboard latency, slow-query alert, queue-age alert dan on-call response tidak dibuktikan aktif. Dokumen atau dependency cadangan bukan bukti operasi.

Minimum production monitoring hendaklah merangkumi availability, error rate, request latency, DB/cache latency, queue depth dan oldest-job age, failed jobs, disk, backup freshness, certificate expiry dan CSP reports. Setiap alert mesti mempunyai pemilik dan runbook.
