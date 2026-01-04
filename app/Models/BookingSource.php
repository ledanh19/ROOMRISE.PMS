<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSource extends Model
{
    protected $fillable = ['name', 'is_default', 'price_percentage'];

    protected $casts = [
        'price_percentage' => 'float',
    ];

    public function ratePlanOTAs()
    {
        return $this->hasMany(RatePlanOTA::class);
    }

    public function ratePlans()
    {
        return $this->hasManyThrough(RatePlan::class, RatePlanOTA::class);
    }

    public function properties()
    {
        return $this->belongsToMany(Property::class, 'booking_source_property');
    }
}
