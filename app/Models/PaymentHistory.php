<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    protected $fillable = [
        'booking_id',
        'paid',
        'payment_method',
        'staff',
        'payment_date',
        'note',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
