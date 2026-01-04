<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'date',
        'room_type_id',
        'rate_plan_id',
        'rate_plan_ota_id',
        'occupancy_option_id',
        'rate',
        'availability',
        'min_stay_arrival',
        'min_stay_through',
        'max_stay',
        'closed_to_arrival',
        'closed_to_departure',
        'stop_sell',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'rate' => 'decimal:2',
        'availability' => 'integer',
        'min_stay_arrival' => 'integer',
        'min_stay_through' => 'integer',
        'max_stay' => 'integer',
        'closed_to_arrival' => 'boolean',
        'closed_to_departure' => 'boolean',
        'stop_sell' => 'boolean',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function room_type()
    {
        return $this->belongsTo(Room::class, 'room_type_id');
    }

    public function ratePlan()
    {
        return $this->belongsTo(RatePlan::class);
    }

    public function ratePlanOTA()
    {
        return $this->belongsTo(RatePlanOTA::class);
    }

    public function occupancyOption()
    {
        return $this->belongsTo(OccupancyOption::class);
    }
}
