<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'name',
        'type',
        'city',
        'address',
        'country',
        'phone',
        'email',
        'source',
        'drop_code',
        'key',
        'max_room_types',
        'max_rooms',
        'owner_id',
        'external_id',
        'currency',
        'checkin_from_time',
        'checkout_to_time',
        'is_active',
        'is_sync_enabled',
        'webhook_id',
        'website',
        'category',
        'max_count_of_rate_plans',
        'partner_group_id',
        'deposit_amount',
    ];

    protected $casts = [
        'is_sync_enabled' => 'boolean',
        'checkin_from_time' => 'datetime:H:i',
        'checkout_to_time' => 'datetime:H:i',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function partnerGroup()
    {
        return $this->belongsTo(PartnerGroup::class, 'partner_group_id');
    }

    public function bookingSources()
    {
        return $this->belongsToMany(BookingSource::class, 'booking_source_property');
    }

    public function ratePlans()
    {
        return $this->hasMany(RatePlan::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function messageHistories(): HasMany
    {
        return $this->hasMany(MessageHistory::class, 'property_id');
    }
}
