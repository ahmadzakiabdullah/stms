# Error Handling

Laravel validation, model binding dan exception rendering menyediakan baseline. Form Requests mengembalikan error input; Service/Action boleh membaling domain/validation exception; controller mengubah hasil itu kepada redirect, Inertia errors atau status HTTP yang sesuai.

Kod semasa tidak mempunyai satu hierarki custom exception yang meliputi semua domain dan tidak semua service dibalut `try/catch`. Jangan tangkap exception hanya untuk log dan rethrow tanpa nilai tambahan—biarkan handler pusat merekod unhandled exception dan elakkan duplicate logs.

Production mesti menggunakan `APP_DEBUG=false`, respons generik tanpa stack trace/SQL/secret, dan structured logging dengan request/tenant context. Conflict draw/fixture perlu mempunyai kontrak status serta mesej stabil; dua ujian semasa gagal kerana kontrak `DrawService` dan controller tidak lagi sepadan.
