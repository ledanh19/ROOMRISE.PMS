<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRoom extends Model
{
    protected $fillable = [
        'booking_id',
        'property_id',
        'room_id',
        'room_unit_id',
        'rate_plan_id',
        'room_price_at_booking',
        'check_in_date',
        'check_out_date',
        'check_in_time',
        'check_out_time',
        'total',
        'discount',
        'note',
        'room_status',
        'nights',
        'price_date'

    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
    
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function roomUnit()
    {
        return $this->belongsTo(RoomUnit::class);
    }
    public function ratePlan()
    {
        return $this->belongsTo(RatePlan::class);
    }
    public function primaryOccupancyOption()
    {
        return $this->hasOneThrough(
            \App\Models\OccupancyOption::class,
            \App\Models\RatePlan::class,
            'id',               // RatePlan.id
            'rate_plan_id',     // OccupancyOption.rate_plan_id
            'rate_plan_id',     // BookingRoom.rate_plan_id
            'id'                // RatePlan.id
        )->where('is_primary', true);
    }
}
