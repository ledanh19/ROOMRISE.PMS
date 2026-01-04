<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingIncomeExpense extends Model
{
    protected $fillable = [
        'income_expense_id',
        'booking_id',
        'amount',
        'type'
    ];
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function incomeExpense()
    {
        return $this->belongsTo(IncomeExpense::class);
    }
}
