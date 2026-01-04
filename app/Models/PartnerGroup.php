<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerGroup extends Model
{
    protected $fillable = [
        'name',
        'partner_admin_id'
    ];

    public function partnerAdmin()
    {
        return $this->belongsTo(User::class, 'partner_admin_id');
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'partner_group_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'partner_group_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'partner_group_id');
    }
}
