# Design System Overview

STMS menggunakan React 18, Inertia React 2, TypeScript, Tailwind CSS 3 serta komponen shadcn/ui berasaskan Radix. Projek tidak menggunakan MUI, Ant Design, Chakra atau Filament.

Repositori mempunyai 38 Inertia pages dalam `resources/js/Pages/`, semuanya TSX. Beberapa komponen UI/layout bersama masih `.jsx`; TypeScript ialah standard baharu tetapi migrasi compatibility boundary belum lengkap.

Tema warna menggunakan CSS custom properties dan Tailwind dikonfigurasi dengan class-based dark mode. CSS dark theme wujud, tetapi audit tidak menemui theme-toggle pengguna; jangan dokumentasikan pertukaran tema sebagai feature tersedia sehingga control dan persistence dilaksanakan serta diuji.

`resources/js/Components/ui/` mengandungi komponen milik projek. Gunakan primitive sedia ada, focus state jelas, semantic HTML, minimum touch target 44×44 px dan axe/keyboard testing. Public landing mempunyai gaya visual tersendiri tetapi mesti terus menggunakan token/komponen yang boleh diaudit.
