<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'match_number' => $this->match_number,
            'scheduled_at' => $this->scheduled_at,
            'venue' => $this->venue,
            'status' => $this->status,
            'round' => $this->round,
            'event' => new EventResource($this->whenLoaded('event')),
            'competitor_1' => [
                'id' => $this->whenLoaded('competitor1', fn () => $this->competitor1->id),
                'name' => $this->whenLoaded('competitor1', fn () => $this->competitor1->participant->name ?? 'TBD'),
                'score' => $this->whenLoaded('result', fn () => $this->result->competitor_1_score),
            ],
            'competitor_2' => [
                'id' => $this->whenLoaded('competitor2', fn () => $this->competitor2->id),
                'name' => $this->whenLoaded('competitor2', fn () => $this->competitor2->participant->name ?? 'TBD'),
                'score' => $this->whenLoaded('result', fn () => $this->result->competitor_2_score),
            ],
            'winner_id' => $this->whenLoaded('result', fn () => $this->result->winner_id),
        ];
    }
}
