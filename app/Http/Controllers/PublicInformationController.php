<?php

namespace App\Http\Controllers;

use App\Services\PublicPortalService;
use Inertia\Inertia;
use Inertia\Response;

class PublicInformationController extends Controller
{
    public function __invoke(string $page, PublicPortalService $service): Response
    {
        abort_unless(in_array($page, ['medal-tally', 'sports', 'schedules', 'results', 'contact-us'], true), 404);

        return Inertia::render('Public/Information', [
            ...$service->data(limit: null),
            'page' => $page,
        ]);
    }
}
