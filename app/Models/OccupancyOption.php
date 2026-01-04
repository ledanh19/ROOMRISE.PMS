<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OccupancyOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate_plan_ota_id',
        'external_id',
        'occupancy',
        'is_primary',
        'rate',
    ];

    protected $casts = [
        'rate' => 'float',
        'is_primary' => 'boolean',
    ];

    public function ratePlanOTA()
    {
        return $this->belongsTo(RatePlanOTA::class, 'rate_plan_ota_id', 'id');
    }
}
