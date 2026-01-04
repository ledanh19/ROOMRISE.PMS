<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RatePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'property_id',
        'title',
        'price',
        'meal_type',
        'currency',
        'sell_mode',
        'rate_mode',
        'primary_occupancy',
        'children_fee',
        'infant_fee',
        'max_stay',
        'min_stay_arrival',
        'min_stay_through',
        'closed_to_arrival',
        'closed_to_departure',
        'stop_sell',
        'auto_rate_settings',
        'inherit_rate',
        'inherit_closed_to_arrival',
        'inherit_closed_to_departure',
        'inherit_stop_sell',
        'inherit_min_stay_arrival',
        'inherit_min_stay_through',
        'inherit_max_stay',
        'inherit_max_sell',
        'inherit_max_availability',
        'inherit_availability_offset',
    ];

    protected $casts = [
        'price' => 'float',
        'children_fee' => 'float',
        'infant_fee' => 'float',
        'primary_occupancy' => 'integer',
        'max_stay' => 'array',
        'min_stay_arrival' => 'array',
        'min_stay_through' => 'array',
        'closed_to_arrival' => 'array',
        'closed_to_departure' => 'array',
        'stop_sell' => 'array',
        'auto_rate_settings' => 'array',
        'inherit_rate' => 'boolean',
        'inherit_closed_to_arrival' => 'boolean',
        'inherit_closed_to_departure' => 'boolean',
        'inherit_stop_sell' => 'boolean',
        'inherit_min_stay_arrival' => 'boolean',
        'inherit_min_stay_through' => 'boolean',
        'inherit_max_stay' => 'boolean',
        'inherit_max_sell' => 'boolean',
        'inherit_max_availability' => 'boolean',
        'inherit_availability_offset' => 'boolean',
    ];

    // RatePlan no longer has occupancy options directly
    // They are now managed through RatePlanOTA

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function ratePlanOTAs()
    {
        return $this->hasMany(RatePlanOTA::class);
    }

    public function bookingSources()
    {
        return $this->hasManyThrough(BookingSource::class, RatePlanOTA::class);
    }
}
