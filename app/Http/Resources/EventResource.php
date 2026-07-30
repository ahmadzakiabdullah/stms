<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'format' => $this->format,
            'registration_deadline' => $this->registration_deadline,
            'tournament' => new TournamentResource($this->whenLoaded('tournament')),
            'sport' => [
                'id' => $this->whenLoaded('sport', fn () => $this->sport->id),
                'name' => $this->whenLoaded('sport', fn () => $this->sport->name),
            ],
            'category' => [
                'id' => $this->whenLoaded('category', fn () => $this->category->id),
                'name' => $this->whenLoaded('category', fn () => $this->category->name),
            ],
        ];
    }
}
