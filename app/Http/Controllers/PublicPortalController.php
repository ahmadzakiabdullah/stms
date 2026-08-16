<?php

namespace App\Http\Controllers;

use App\Services\PublicPortalService;
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
}
