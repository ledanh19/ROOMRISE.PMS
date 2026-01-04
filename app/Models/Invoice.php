<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'customer_id',
        'partner_id',
        'total_amount',
        'created_by',
        'note',
        'issued_date'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function histories()
    {
        return $this->hasMany(InvoiceHistories::class);
    }
}
