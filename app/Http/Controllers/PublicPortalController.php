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

    public function sitemap(): HttpResponse
    {
        $urls = [
            ['location' => route('public.index'), 'priority' => '1.0'],
            ['location' => route('public.contact'), 'priority' => '0.6'],
        ];

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
