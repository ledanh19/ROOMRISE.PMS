<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Property;
use App\Models\Inventory;

class InventoryRandomSeeder extends Seeder
{
    /**
     * Generate a random date within the next 500 days.
     */
    private function randomDateInNext500Days()
    {
        $start = strtotime('today');
        $end = strtotime('+500 days');
        $randomTimestamp = rand($start, $end);
        return date('Y-m-d', $randomTimestamp);
    }

    /**
     * Run the database seeds.
     */
    public function run()
    {
        // ID property bạn muốn seed (thay bằng id thực tế)
        $propertyId = 4;

        $property = Property::with([
            'rooms.ratePlans.ratePlanOTAs'
        ])->findOrFail($propertyId);

        foreach ($property->rooms as $room) {
            // Tạo bản ghi Inventory cho room_availability (không có rate_plan_id, rate_plan_ota_id)
            for ($i = 0; $i < 100; $i++) { // Tạo 10 ngày random cho mỗi room
                $date = $this->randomDateInNext500Days();
                Inventory::updateOrCreate(
                    [
                        'property_id'      => $property->id,
                        'room_type_id'     => $room->id,
                        'rate_plan_id'     => null,
                        'rate_plan_ota_id' => null,
                        'date'             => $date,
                    ],
                    [
                        'availability'     => rand(0, 5),
                        'rate'             => null,
                        'min_stay_arrival' => null,
                        'max_stay'         => null,
                        'closed_to_arrival' => null,
                        'closed_to_departure' => null,
                        'stop_sell'        => null,
                    ]
                );
            }

            // Tạo bản ghi Inventory cho từng rate_plan và rate_plan_ota
            foreach ($room->ratePlans as $ratePlan) {
                foreach ($ratePlan->ratePlanOTAs as $ratePlanOTA) {
                    for ($i = 0; $i < 100; $i++) { // Tạo 10 ngày random cho mỗi tổ hợp
                        $date = $this->randomDateInNext500Days();
                        Inventory::updateOrCreate(
                            [
                                'property_id'      => $property->id,
                                'room_type_id'     => $room->id,
                                'rate_plan_id'     => $ratePlan->id,
                                'rate_plan_ota_id' => $ratePlanOTA->id,
                                'date'             => $date,
                            ],
                            [
                                'availability'     => rand(0, 10),
                                'rate'             => rand(5, 50) * 10,
                                'min_stay_arrival' => rand(1, 5),
                                'max_stay'         => rand(1, 10),
                                'closed_to_arrival' => (bool)rand(0, 1),
                                'closed_to_departure' => (bool)rand(0, 1),
                                'stop_sell'        => (bool)rand(0, 1),
                            ]
                        );
                    }
                }
            }
        }
    }
}
