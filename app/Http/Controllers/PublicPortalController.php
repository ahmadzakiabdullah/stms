<?php

namespace App\Http\Controllers;

use App\Services\PublicPortalService;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicPortalController extends Controller
{
    public function __invoke(PublicPortalService $service): Response
    {
        return Inertia::render('Public/Index', $service->data());
    }

    public function contact(PublicPortalService $service): Response
    {
        return Inertia::render('Public/Contact', $service->data());
    }

    public function directory(string $section, PublicPortalService $service): Response
    {
        abort_unless(in_array($section, ['sports', 'faculties', 'venues'], true), 404);

        return Inertia::render('Public/Directory', [...$service->data(null), 'section' => $section]);
    }

    public function schedule(PublicPortalService $service): Response
    {
        $data = $service->data(null);
        $data['completed'] = $data['results'];

        return Inertia::render('Public/Schedule', $data);
    }

    public function info(string $section, PublicPortalService $service): Response
    {
        abort_unless(in_array($section, ['news', 'downloads', 'faq', 'about'], true), 404);

        return Inertia::render('Public/Info', [...$service->data(), 'section' => $section]);
    }

    public function sitemap(): HttpResponse
    {
        $urls = [
            ['location' => route('public.index'), 'priority' => '1.0'],
            ['location' => route('public.sports'), 'priority' => '0.7'],
            ['location' => route('public.schedule'), 'priority' => '0.7'],
            ['location' => route('public.faculties'), 'priority' => '0.6'],
            ['location' => route('public.venues'), 'priority' => '0.5'],
            ['location' => route('public.about'), 'priority' => '0.4'],
            ['location' => route('public.contact'), 'priority' => '0.6'],
        ];

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
