<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'total_booking',
        'total_net_estimate',
        'total_payout',
        'total_difference',
        'status',
        'settlement_date',
        'settlement_officer',
        'payment_method',
        'reconciliation_status',
        'ota_name'
    ];
    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'settlement_bookings');
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function settlementBookings()
    {
        return $this->hasMany(SettlementBooking::class);
    }

    // Quan hệ tới income_expenses (1 settlement có thể có 1 income_expense)
    public function incomeExpense()
    {
        return $this->hasOne(IncomeExpense::class, 'settlement_id');
    }

    // Nếu 1 settlement có thể có nhiều income_expenses
    public function incomeExpenses()
    {
        return $this->hasMany(IncomeExpense::class, 'settlement_id');
    }
}
