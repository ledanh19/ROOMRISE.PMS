<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'external_id',
        'ota_name',
        'ota_reservation_code',
        'status',
        'status_2',
        'property_id',
        'room_id',
        'room_unit_id',
        'room_price_at_booking',
        'check_in_date',
        'check_out_date',
        'check_in_time',
        'check_out_time',
        'customer_id',
        'payment_type',
        'total_amount',
        'paid',
        'remaining',
        'payment_status',
        'ota_fee_percent',
        'ota_fee',
        'net_estimate',
        'payout_received',
        'difference_amount',
        'ota_channel',
        'reconciliation_status',
        'note',
        'settlement_id',
        'payment_method',
        'room_payment_method',
        'commission_fee',
        'adults',
        'children',
        'newborn',
        'payment_content',
        'customer_payment_amount',
        'income_expense_id',
        'pancake_id',
        'is_imported',
        'meta',
        'currency',
        'channel_id',
        'notes',
        'payment_collect',
        'payment_type_original',
        'rooms',
        'occupancy',
        'raw_message'
    ];

    protected $appends = ['booking_code'];

    protected $casts = [
        'is_imported' => 'boolean',
        'total_amount' => 'float',
        'meta' => 'array',
        'rooms' => 'array',
        'occupancy' => 'array',
    ];

    public function getBookingCodeAttribute()
    {
        return $this->ota_reservation_code ?: $this->id;
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentHistories()
    {
        return $this->hasMany(PaymentHistory::class);
    }

    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }

    public function bookingRooms()
    {
        return $this->hasMany(BookingRoom::class);
    }

    public function bookingCustomers()
    {
        return $this->belongsToMany(
            Customer::class,
            'booking_customers',
            'booking_id',
            'customer_id'
        )->withTimestamps();
    }

    public function incomeExpenses()
    {
        return $this->hasMany(IncomeExpense::class, 'booking_id');
    }

    public function partnerIncomeExpenses()
    {
        return $this->belongsToMany(IncomeExpense::class, 'booking_income_expenses', 'booking_id', 'income_expense_id')
            ->withPivot('amount', 'type');
    }

    public function totalPaid()
    {
        return $this->incomeExpenses->sum('amount');
    }

    public function messageHistories(): HasMany
    {
        return $this->hasMany(MessageHistory::class, 'booking_id');
    }
}
