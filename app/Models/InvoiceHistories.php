<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceHistories extends Model
{
    protected $fillable = [
        'invoice_id',
        'booking_id',
        'total_amount'
    ];
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }
}
