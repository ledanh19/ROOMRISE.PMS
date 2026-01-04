<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatePlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'room_id' => $this->id,
            'max_people' => $this->adults,
            'currency' => $this->property->currency,
            'name' => $this->name,
            'ratePlans' => RatePlanItemResource::collection($this->ratePlans),
        ];
    }
}
