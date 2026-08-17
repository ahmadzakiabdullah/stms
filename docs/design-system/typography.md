# Typography

`resources/css/app.css` membundel variable fonts melalui Fontsource. Stack authenticated menggunakan Geist sebagai sans utama; public portal turut menggunakan Plus Jakarta Sans dan font paparan yang diimport oleh source semasa.

Semua font portal kini self-hosted; `app.blade.php` tidak lagi memuat Bunny Fonts. CSP production boleh mengehadkan `style-src`/`font-src` kepada sumber sendiri selepas cutover dan smoke test.

Gunakan utility Tailwind untuk skala dan line-height, dengan hierarki konsisten: satu page title, section heading berurutan, body sekurang-kurangnya mudah dibaca pada mobile, dan muted text yang masih memenuhi kontras WCAG. Jangan bergantung pada jenis font/uppercase sahaja untuk menyampaikan status.
