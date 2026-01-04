<?php

namespace App\Helpers;

use App\Models\RateplanHistory;

class InventoryHelper
{
    /**
     * Lấy giá trị inventory với fallback về rateplan_history
     */
    public static function getInventoryValue($inventory, $field, $defaultValue = null)
    {
        // Nếu có giá trị trong inventory, trả về
        if ($inventory->$field !== null) {
            return $inventory->$field;
        }

        // Nếu không có, lấy từ rateplan_history
        $historyValue = RateplanHistory::getDefaultValueForDate(
            $inventory->rate_plan_id,
            $inventory->date,
            $field
        );

        return $historyValue ?? $defaultValue;
    }

    /**
     * Lấy occupancy_options cho inventory
     */
    public static function getInventoryOccupancyOptions($inventory)
    {
        return RateplanHistory::getOccupancyOptionsForDate(
            $inventory->rate_plan_id,
            $inventory->date
        );
    }

    /**
     * Lấy giá cho một occupancy cụ thể
     */
    public static function getOccupancyRate($inventory, $occupancy)
    {
        // Nếu có override trong inventory, trả về
        if ($inventory->rate !== null) {
            return $inventory->rate;
        }

        // Lấy từ rateplan_history
        return RateplanHistory::getOccupancyRateForDate(
            $inventory->rate_plan_id,
            $inventory->date,
            $occupancy
        );
    }

    /**
     * Lấy tất cả giá trị cho inventory với fallback
     */
    public static function getInventoryWithFallback($inventory)
    {
        $defaultValues = RateplanHistory::getDefaultValuesForDate(
            $inventory->rate_plan_id,
            $inventory->date
        );

        if ($defaultValues) {
            $inventory->rate = $inventory->rate ?? $defaultValues->rate;
            $inventory->min_stay_arrival = $inventory->min_stay_arrival ?? $defaultValues->min_stay_arrival;
            $inventory->min_stay_through = $inventory->min_stay_through ?? $defaultValues->min_stay_through;
            $inventory->max_stay = $inventory->max_stay ?? $defaultValues->max_stay;
            $inventory->closed_to_arrival = $inventory->closed_to_arrival ?? $defaultValues->closed_to_arrival;
            $inventory->closed_to_departure = $inventory->closed_to_departure ?? $defaultValues->closed_to_departure;
            $inventory->stop_sell = $inventory->stop_sell ?? $defaultValues->stop_sell;

            // Lấy occupancy_options nếu rate_mode = manual
            if (
                $defaultValues->metadata &&
                isset($defaultValues->metadata['rate_mode']) &&
                $defaultValues->metadata['rate_mode'] === 'manual' &&
                $defaultValues->occupancy_options
            ) {
                $inventory->occupancy_options = $defaultValues->occupancy_options;
            }
        }

        return $inventory;
    }
}
