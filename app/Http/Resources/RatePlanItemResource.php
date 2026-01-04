<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatePlanItemResource extends JsonResource
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
            'room_id' => $this->room_id,
            'title' => $this->title,
            'meal_type' => $this->meal_type,
            'currency' => $this->currency,
            'max_people' => $this->adults,
            'sell_mode' => $this->sell_mode,
            'rate_mode' => $this->rate_mode,
            'price' => $this->price,
            'primary_occupancy' => $this->primary_occupancy,
            'default_rate' => $this->price,
            'children_fee' => $this->children_fee,
            'infant_fee' => $this->infant_fee,
            'min_stay_arrival' => $this->min_stay_arrival,
            'min_stay_through' => $this->min_stay_through,
            'max_stay' => $this->max_stay,
            'closed_to_arrival' => $this->closed_to_arrival,
            'closed_to_departure' => $this->closed_to_departure,
            'stop_sell' => $this->stop_sell,
            'auto_rate_settings' => $this->auto_rate_settings,
            'occupancy_options' => $this->getOccupancyOptions(),
            'booking_sources' => $this->ratePlanOTAs->map(function ($ratePlanOTA) {
                return [
                    'id' => $ratePlanOTA->bookingSource->id,
                    'name' => $ratePlanOTA->bookingSource->name,
                    'price_percentage' => $ratePlanOTA->bookingSource->price_percentage,
                    'external_id' => $ratePlanOTA->external_id,
                ];
            }),
        ];
    }

    private function getOccupancyOptions()
    {
        $firstRatePlanOTA = $this->ratePlanOTAs->first();

        $options = [];
        if ($firstRatePlanOTA && $firstRatePlanOTA->occupancyOptions) {
            $options = $firstRatePlanOTA->occupancyOptions->map(function ($occupancyOption) {
                return [
                    'occupancy' => $occupancyOption->occupancy,
                    'rate' => $occupancyOption->rate,
                    'is_primary' => $occupancyOption->is_primary,
                ];
            })->sortBy('occupancy')->values()->toArray();
        }

        \Illuminate\Support\Facades\Log::info('RatePlanItemResource occupancy_options:', $options);

        return $options;
    }
}
