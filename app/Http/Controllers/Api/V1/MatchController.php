<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MatchResource;
use App\Models\Fixture;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Fixture::with(['event', 'competitor1.participant', 'competitor2.participant', 'result']);

        if ($request->has('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $matches = $query->orderBy('scheduled_at', 'asc')->paginate(15);

        return MatchResource::collection($matches);
    }

    public function show(Fixture $match)
    {
        $match->load(['event', 'competitor1.participant', 'competitor2.participant', 'result']);

        return new MatchResource($match);
    }
}
