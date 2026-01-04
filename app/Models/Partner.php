<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'email',
        'phone',
        'type',
        'commission',
        'payment_method',
        'internal_code',
        'status',
        'address',
        'city',
        'country',
        'internal_note',
        'partner_group_id',
        // 'total_revenue',
        // 'total_commission',
        // 'total_processed',
        // 'net_debt',
        // 'date_update',
        // 'status_debt',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
    public function partnerGroup()
    {
        return $this->belongsTo(PartnerGroup::class);
    }
}
