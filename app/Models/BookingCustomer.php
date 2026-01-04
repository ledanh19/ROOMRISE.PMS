<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingCustomer extends Model
{
    protected $fillable = [
        'booking_id',
        'customer_id',
    ];
}
