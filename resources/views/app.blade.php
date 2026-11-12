<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php
            $pageComponent = $page['component'] ?? '';
            $pageProps = $page['props'] ?? [];
            $competitionName = data_get($pageProps, 'competition.name', config('app.name', 'SAF'));
            $publicSiteName = data_get($pageProps, 'app_name', config('app.name', 'SAF'));
            $locale = app()->getLocale();
            $publicMeta = [
                'Public/Index' => ['en' => [$competitionName.' | Sukan Antara Fakulti UTeM', 'Official schedules, sports programme, results, athletes and venue information for '.$competitionName.'.'], 'ms' => [$competitionName.' | Sukan Antara Fakulti UTeM', 'Jadual rasmi, program sukan, keputusan, atlet dan maklumat venue bagi '.$competitionName.'.']],
                'Public/Schedule' => ['en' => ['Competition Schedule | '.$competitionName, 'Find official SAF fixtures and results by sport, event, venue and date.'], 'ms' => ['Jadual Pertandingan | '.$competitionName, 'Cari jadual perlawanan dan keputusan rasmi SAF mengikut sukan, acara, venue dan tarikh.']],
                'Public/Athletes' => ['en' => ['Athletes & Teams | '.$competitionName, 'Browse participating athletes, teams and official competition profiles.'], 'ms' => ['Atlet & Pasukan | '.$competitionName, 'Semak atlet, pasukan dan profil rasmi peserta pertandingan.']],
                'Public/Athlete' => ['en' => ['Athlete Profile | '.$competitionName, 'View an official athlete profile and competition performances.'], 'ms' => ['Profil Atlet | '.$competitionName, 'Lihat profil rasmi atlet dan prestasi pertandingan.']],
                'Public/Directory' => match (data_get($pageProps, 'section')) {
                    'sports' => ['en' => ['Sports Programme | '.$competitionName, 'Explore official sports, events, quotas and competition documents.'], 'ms' => ['Program Sukan | '.$competitionName, 'Lihat sukan, acara, kuota dan dokumen rasmi pertandingan.']],
                    'faculties' => ['en' => ['Faculties & Contingents | '.$competitionName, 'Meet the faculties and contingents participating in '.$competitionName.'.'], 'ms' => ['Fakulti & Kontinjen | '.$competitionName, 'Kenali fakulti dan kontinjen yang menyertai '.$competitionName.'.']],
                    default => ['en' => ['Competition Venues | '.$competitionName, 'Find official competition locations and venue maps at UTeM.'], 'ms' => ['Venue Pertandingan | '.$competitionName, 'Cari lokasi rasmi pertandingan dan peta venue di UTeM.']],
                },
                'Public/Info' => match (data_get($pageProps, 'section')) {
                    'news' => ['en' => ['Competition News | '.$competitionName, 'Official announcements and updates for '.$competitionName.'.'], 'ms' => ['Berita Pertandingan | '.$competitionName, 'Pengumuman dan maklumat terkini rasmi '.$competitionName.'.']],
                    'downloads' => ['en' => ['Downloads | '.$competitionName, 'Download official competition forms, rules and documents.'], 'ms' => ['Muat Turun | '.$competitionName, 'Muat turun borang, peraturan dan dokumen rasmi pertandingan.']],
                    'faq' => ['en' => ['Frequently Asked Questions | '.$competitionName, 'Answers to common questions about '.$competitionName.'.'], 'ms' => ['Soalan Lazim | '.$competitionName, 'Jawapan kepada soalan lazim mengenai '.$competitionName.'.']],
                    'about' => ['en' => ['About the Competition | '.$competitionName, 'Official information about '.$competitionName.' at UTeM.'], 'ms' => ['Mengenai Pertandingan | '.$competitionName, 'Maklumat rasmi mengenai '.$competitionName.' di UTeM.']],
                    default => ['en' => ['General Information | '.$competitionName, 'Competition dates, eligibility, prizes and official participant information.'], 'ms' => ['Maklumat Am | '.$competitionName, 'Tarikh, kelayakan, hadiah dan maklumat rasmi peserta pertandingan.']],
                },
                'Public/Committee' => ['en' => ['Main Committee | '.$competitionName, 'Meet the organizing committee for Sukan Antara Fakulti UTeM.'], 'ms' => ['Jawatankuasa Induk | '.$competitionName, 'Kenali jawatankuasa pengelola Sukan Antara Fakulti UTeM.']],
                'Public/StudentCommittee' => ['en' => ['Student Committee | '.$competitionName, 'Meet the student organizing committee for '.$competitionName.'.'], 'ms' => ['Jawatankuasa Pelaksana | '.$competitionName, 'Kenali jawatankuasa pelaksana pelajar '.$competitionName.'.']],
                'Public/GameChairpersons' => ['en' => ['Game Chairpersons | '.$competitionName, 'Find the official chairpersons for each SAF sport.'], 'ms' => ['Pengerusi Permainan | '.$competitionName, 'Senarai pengerusi rasmi bagi setiap sukan SAF.']],
                'Public/ImportantDates' => ['en' => ['Important Dates | '.$competitionName, 'View key registration and competition dates for '.$competitionName.'.'], 'ms' => ['Tarikh Penting | '.$competitionName, 'Semak tarikh pendaftaran dan pertandingan utama '.$competitionName.'.']],
                'Public/Contact' => ['en' => ['Contact | '.$competitionName, 'Contact the UTeM Sports Centre for official enquiries about '.$competitionName.'.'], 'ms' => ['Hubungi Kami | '.$competitionName, 'Hubungi Pusat Sukan UTeM untuk pertanyaan rasmi mengenai '.$competitionName.'.']],
            ];
            $meta = $publicMeta[$pageComponent][$locale] ?? $publicMeta[$pageComponent]['en'] ?? null;
            $metaTitle = $meta[0] ?? config('app.name', 'STMS Portal');
            $metaDescription = $meta[1] ?? config('app.description');
            $metaImage = asset('images/banner/banner-saf-20-2026.webp');
        @endphp
        <meta name="description" inertia="description" content="{{ $metaDescription }}">
        <link rel="canonical" inertia="canonical" href="{{ url()->current() }}">
        <meta property="og:type" inertia="og:type" content="website">
        <meta property="og:title" inertia="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" inertia="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" inertia="og:url" content="{{ url()->current() }}">
        <meta property="og:site_name" inertia="og:site_name" content="{{ $publicSiteName }}">
        <meta property="og:locale" inertia="og:locale" content="{{ $locale === 'ms' ? 'ms_MY' : 'en_US' }}">
        <meta property="og:image" inertia="og:image" content="{{ $metaImage }}">
        <meta name="twitter:card" inertia="twitter:card" content="summary_large_image">
        <meta name="twitter:title" inertia="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" inertia="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" inertia="twitter:image" content="{{ $metaImage }}">

        @php
            $brandingOrganizationId = auth()->user()?->organization_id;

            if (! $brandingOrganizationId && config('app.public_org_slug')) {
                $brandingOrganizationId = \App\Models\Organization::query()
                    ->where('slug', config('app.public_org_slug'))
                    ->where('is_active', true)
                    ->value('id');
            }

            $favicon = $brandingOrganizationId
                ? \App\Models\Setting::query()
                    ->where('organization_id', $brandingOrganizationId)
                    ->where('key', 'favicon_url')
                    ->value('value')
                : null;
        @endphp
        <link rel="icon" href="{{ $favicon ?: asset('favicon.ico') }}">
        <title inertia>{{ $metaTitle }}</title>

        <!-- Scripts -->
        {{--
            Inertia login redirects can transition from the guest page to an
            authenticated page without a full document reload. Keep the
            complete Ziggy map available so the route helper does not retain
            the guest-only map after that transition; authorization remains
            enforced by Laravel middleware and policies.
        --}}
        @routes(null, request()->attributes->get('csp_nonce'))
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
