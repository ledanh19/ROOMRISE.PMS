<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'full_name',
        'type',
        'email',
        'phone',
        'id_type',
        'id_number',
        'partner_id',
        'dob',
        'nationality',
        'country',
        'address',
        'city',
        'image',
        'issue_date',
        'partner_group_id'
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function partnerGroup()
    {
        return $this->belongsTo(PartnerGroup::class, 'partner_group_id');
    }
}
