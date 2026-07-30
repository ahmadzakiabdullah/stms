<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use Inertia\Inertia;
use Inertia\Response;

class MatchShowController extends Controller
{
    public function show(Fixture $match): Response
    {
        $match->load(['event', 'competitor1.participant', 'competitor2.participant', 'result']);

        return Inertia::render('Matches/Show', [
            'match' => $match,
        ]);
    }
}
