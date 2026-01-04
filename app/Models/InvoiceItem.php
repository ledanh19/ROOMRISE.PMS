<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'property_id',
        'room_id',
        'room_unit_id',
        'payment_type',
        'check_in_date',
        'check_out_date',
        'price',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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
}
