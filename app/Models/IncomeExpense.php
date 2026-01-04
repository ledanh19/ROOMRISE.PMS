<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomeExpense extends Model
{
    protected $fillable = [
        'booking_id',
        'settlement_id',
        'type',
        'category',
        'subcategory',
        'payment_method',
        'amount',
        'date',
        'staff_name',
        'note',
        'created_by',
        'payment_status',
        'payment_source',
        'payment_object',
        'file',
        'business_type',
        'source_business_type',
        'source_business_code',
        'room_payment_method',
        'partner_group_id'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function partnerBookings()
    {
        return $this->belongsToMany(
            Booking::class,
            'booking_income_expenses',
            'income_expense_id',
            'booking_id'
        );
    }

    public function settlement()
    {
        return $this->belongsTo(Settlement::class, 'settlement_id');
    }
}
