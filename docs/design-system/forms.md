# Forms

Standard sasaran ialah React Hook Form + Zod dengan komponen shadcn/ui dan server-side Laravel Form Request. Source semasa masih bercampur: form yang telah dimigrasi menggunakan RHF/Zod, manakala sebahagian page menggunakan Inertia `useForm` secara langsung.

Kedua-dua pola mesti:

- mempunyai label programatik, description dan error yang dikaitkan dengan field;
- memaparkan error 422 daripada server tanpa membuang input;
- disable submit ketika processing dan menghalang double-submit;
- menggunakan schema create/update yang jelas;
- tidak menganggap validasi client sebagai kawalan keselamatan;
- menguji keyboard, focus selepas error dan screen-reader announcement.

Jangan mendakwa semua control atau `DatePicker` tersedia tanpa memeriksa `resources/js/Components/ui/`; gunakan komponen yang benar-benar wujud atau tambah secara terkawal.
