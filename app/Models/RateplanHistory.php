<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatePlanHistory extends Model
{
    use HasFactory;

    protected $table = 'rateplan_history';

    protected $fillable = [
        'rate_plan_id',
        'effective_date',
        'primary_occupancy',
        'rate',
        'children_fee',
        'infant_fee',
        'min_stay_arrival',
        'min_stay_through',
        'max_stay',
        'closed_to_arrival',
        'closed_to_departure',
        'stop_sell',
        'metadata',
        'occupancy_options',
    ];

    protected $casts = [
        'effective_date' => 'date:Y-m-d',
        'rate' => 'decimal:2',
        'max_stay' => 'array',
        'min_stay_arrival' => 'array',
        'min_stay_through' => 'array',
        'closed_to_arrival' => 'array',
        'closed_to_departure' => 'array',
        'stop_sell' => 'array',
        'auto_rate_settings' => 'array',
        'metadata' => 'array',
        'occupancy_options'   => 'array',
    ];

    public function ratePlan()
    {
        return $this->belongsTo(RatePlan::class);
    }

    /**
     * Lấy giá trị mặc định cho một ngày cụ thể
     */
    public static function getDefaultValueForDate($ratePlanId, $date, $field)
    {
        return static::where('rate_plan_id', $ratePlanId)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->value($field);
    }

    /**
     * Lấy toàn bộ giá trị mặc định cho một ngày
     */
    public static function getDefaultValuesForDate($ratePlanId, $date)
    {
        return static::where('rate_plan_id', $ratePlanId)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    /**
     * Lấy occupancy_options cho một ngày cụ thể
     */
    public static function getOccupancyOptionsForDate($ratePlanId, $date)
    {
        $history = static::where('rate_plan_id', $ratePlanId)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        if (
            $history &&
            $history->metadata &&
            isset($history->metadata['rate_mode']) &&
            $history->metadata['rate_mode'] === 'manual' &&
            $history->occupancy_options
        ) {
            return $history->occupancy_options;
        }

        return null;
    }

    /**
     * Lấy giá cho một occupancy cụ thể tại một ngày
     */
    public static function getOccupancyRateForDate($ratePlanId, $date, $occupancy)
    {
        $occupancyOptions = static::getOccupancyOptionsForDate($ratePlanId, $date);

        if ($occupancyOptions) {
            $option = collect($occupancyOptions)->firstWhere('occupancy', $occupancy);
            return $option ? $option['rate'] : null;
        }

        return null;
    }
}
