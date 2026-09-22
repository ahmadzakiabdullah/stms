# Typography

`resources/css/app.css` membundel font melalui Fontsource. Stack authenticated dan public body/UI, termasuk heading utama, menggunakan Geist supaya teks kekal jelas; Barlow Condensed 700/800 hanya digunakan untuk nombor paparan dan aksen display yang memerlukan penekanan sukan.

Semua font portal kini self-hosted; `app.blade.php` tidak lagi memuat Bunny Fonts. CSP production boleh mengehadkan `style-src`/`font-src` kepada sumber sendiri selepas cutover dan smoke test.

Gunakan utility Tailwind untuk skala dan line-height, dengan hierarki konsisten: satu page title, section heading berurutan, body sekurang-kurangnya mudah dibaca pada mobile, dan muted text yang masih memenuhi kontras WCAG. Jangan bergantung pada jenis font/uppercase sahaja untuk menyampaikan status.
