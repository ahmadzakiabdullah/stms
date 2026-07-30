<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Fixture;
use App\Services\AIAssistantService;
use Illuminate\Http\Request;

class AIAssistantController extends Controller
{
    public function __construct(protected AIAssistantService $aiService) {}

    public function optimizeSchedule(Request $request, Event $event)
    {
        $result = $this->aiService->optimizeSchedule($event);

        return response()->json($result);
    }

    public function predictMatch(Request $request, Fixture $match)
    {
        $prediction = $this->aiService->predictWinner($match);

        return response()->json(['prediction' => $prediction]);
    }
}
