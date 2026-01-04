<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettlementBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'settlement_id',
        'booking_id',
        'net_estimate',
        'payout_received',
        'difference_amount',
    ];

    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
