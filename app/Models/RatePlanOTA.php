<?php

namespace App\Models;

use App\Helpers\MoneyHelper;
use Illuminate\Database\Eloquent\Model;

class RatePlanOTA extends Model
{
    protected $table = 'rate_plan_booking_source';

    protected $fillable = [
        'rate_plan_id',
        'booking_source_id',
        'external_id',
    ];

    public function getFormattedBasePriceAttribute()
    {
        // Lấy giá gốc từ rate plan
        $basePrice = $this->ratePlan ? $this->ratePlan->price : null;

        // Nếu không có giá gốc thì trả về 0
        if ($basePrice === null) {
            return 0;
        }

        // Lấy phần trăm điều chỉnh từ booking source
        $percent = $this->bookingSource && isset($this->bookingSource->price_percentage)
            ? $this->bookingSource->price_percentage
            : 0;

        // Tính giá sau khi điều chỉnh
        $calculatedPrice = round($basePrice * (1 + $percent / 100));

        return MoneyHelper::formatCurrency($calculatedPrice, $this->ratePlan->currency);
    }

    public function ratePlan()
    {
        return $this->belongsTo(RatePlan::class);
    }

    public function bookingSource()
    {
        return $this->belongsTo(BookingSource::class);
    }

    public function occupancyOptions()
    {
        return $this->hasMany(OccupancyOption::class, 'rate_plan_ota_id', 'id');
    }
}
