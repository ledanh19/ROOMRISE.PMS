<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'name',
        'property_id',
        'unit',
        'quantity',
        'max_people',
        'adults',
        'children',
        'external_id',
    ];

    public function roomUnits()
    {
        return $this->hasMany(RoomUnit::class);
    }

    public function ratePlans()
    {
        return $this->hasMany(RatePlan::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }


    /**
     * Get the local rate of room type (Room) for a specific date with a specific rate plan
     * Only get the local rate (rate_plan_ota_id must be null)
     */
    public function getLocalRateForDate($date, $ratePlanId)
    {
        // Get the local rate from inventory (rate_plan_ota_id must be null)
        $inventory = \App\Models\Inventory::where('room_type_id', $this->id)
            ->where('rate_plan_id', $ratePlanId)
            ->whereNull('rate_plan_ota_id')
            ->where('date', $date)
            ->first();

        if ($inventory && $inventory->rate !== null) {
            return (float) $inventory->rate;
        }

        // If no override, get the default rate from rate plan
        $ratePlan = $this->ratePlans()->find($ratePlanId);
        if ($ratePlan) {
            return (float) $ratePlan->price;
        }

        // If not found, return null or 0 as desired
        return null;
    }
}
