<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function index(Request $request)
    {
        $tournaments = Tournament::with('session')->paginate(15);

        return TournamentResource::collection($tournaments);
    }

    public function show(Tournament $tournament)
    {
        $tournament->load('session');

        return new TournamentResource($tournament);
    }
}
