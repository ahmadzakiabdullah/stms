# Audit Logging

STMS menggunakan `spatie/laravel-activitylog` melalui jadual `activity_log`, UI authenticated dan rekod eksplisit untuk sebahagian operasi seperti draw. Pangkalan data yang diaudit mempunyai 882 rekod activity log.

Soft delete mengekalkan sejarah pada banyak model domain, tetapi bukan semuanya. `Setting`, `SquadMember` dan `DrawVersion` tidak menggunakan `SoftDeletes`; `DrawVersion` pula berfungsi sebagai snapshot versi draw. Jadual `settings` menggunakan primary key integer sebagai pengecualian sejarah kepada konvensyen UUID.

Application logging dan activity logging mempunyai tujuan berbeza:

- application log: diagnosis runtime, exception dan operasi tertentu;
- activity log: actor/subject/context untuk tindakan yang sengaja diaudit;
- soft delete: retention rekod, bukan bukti actor atau ledger immutable;
- draw version: snapshot domain untuk sejarah/rollback draw.

Liputan tidak seragam pada semua CRUD, retention dan immutability pangkalan data tidak dikuatkuasakan, dan sebahagian log service mengandungi e-mel. Oleh itu kemudahan semasa ialah audit trail operasi, bukan ledger compliance. Tetapkan klasifikasi PII, retention, akses, redaction dan eksport sebelum menggunakannya untuk tujuan pematuhan.
