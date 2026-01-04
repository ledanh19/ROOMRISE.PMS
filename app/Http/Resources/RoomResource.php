<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'room_units' => $this->roomUnits,
            'max_people' => $this->adults,
            'adults' => $this->adults,
            'children' => $this->children,
            'property_id' => $this->property_id,
            'property_name' => optional($this->property)->name,
        ];
    }
}
