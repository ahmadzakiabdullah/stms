<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'ranking_strategy' => $this->ranking_strategy,
            'session' => [
                'id' => $this->whenLoaded('session', fn () => $this->session->id),
                'name' => $this->whenLoaded('session', fn () => $this->session->name),
            ],
        ];
    }
}
