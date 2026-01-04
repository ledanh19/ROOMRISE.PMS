<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Services\ChannexService;
use Illuminate\Console\Command;

class SyncChannexProperties extends Command
{
    protected $signature = 'sync:channex-properties';
    protected $description = 'Sync properties and room types from Channex';

    public function handle(ChannexService $channex)
    {
        $this->info("Starting sync from Channex...");

        try {
            $this->syncProperties($channex);
            $this->syncRoomTypes($channex);
            $this->syncRatePlans($channex);
        } catch (\Exception $e) {
            logger($e);
            logger()->error("Channex Sync Failed", ['error' => $e->getMessage()]);
            $this->error("Error: " . $e->getMessage());
        }
    }

    protected function syncProperties(ChannexService $channex): void
    {
        $this->info("Syncing properties...");

        $properties = $channex->getAllProperties();

        foreach ($properties as $item) {
            $attr = $item['attributes'];

            Property::updateOrCreate(
                ['external_id' => $item['id']],
                [
                    'name'          => $attr['title'] ?? 'Unnamed',
                    'type'          => $attr['property_type'] ?? null,
                    'city'          => $attr['city'] ?? null,
                    'address'       => $attr['address'] ?? null,
                    'country'       => $attr['country'] ?? null,
                    'phone'         => $attr['phone'] ?? null,
                    'email'         => $attr['email'] ?? null,
                    'source'        => 'channex',
                    'currency'      => $attr['currency'] ?? null,
                    'is_active'     => $attr['is_active'] ?? true,
                    'website'       => $attr['website'] ?? null,
                    'category'      => $attr['property_category'] ?? null,
                    'max_room_types' => $attr['max_count_of_room_types'] ?? null,
                    'max_rooms'     => $attr['max_count_of_room_types'] ?? null, // initially set equal to max_room_types 
                    'max_count_of_rate_plans' => $attr['max_count_of_rate_plans'] ?? null,
                    'drop_code'     => null,
                    'key'           => null,
                    'owner_id'      => 1, // relationship['users'][0]['id']
                ]
            );
        }

        $this->info("Synced " . count($properties) . " properties.");
    }

    protected function syncRoomTypes(ChannexService $channex): void
    {
        $this->info("Syncing room types...");

        $roomTypes = $channex->getAllRoomTypes();

        foreach ($roomTypes as $roomType) {
            $attr = $roomType['attributes'];
            $propertyExternalId = $roomType['relationships']['property']['data']['id'];

            $property = Property::where('external_id', $propertyExternalId)->first();

            if (!$property) {
                $this->warn("⚠️ Skipping: property not found with external_id: $propertyExternalId");
                continue;
            }

            $room = Room::updateOrCreate(
                ['external_id' => $roomType['id']],
                [
                    'name'        => $attr['title'],
                    'unit'        => 'room', // todo: remove this field
                    'property_id' => $property->id,
                    'quantity'    => $attr['count_of_rooms'],
                    'adults'      => $attr['occ_adults'],
                    'children'    => $attr['occ_children'],
                    'max_people'  => 0, // todo: set or remove this field
                ]
            );

            if ($room->wasRecentlyCreated) {
                for ($i = 1; $i <= ($attr['count_of_rooms'] ?? 1); $i++) {
                    $room->roomUnits()->create([
                        'name' => (string) $i,
                    ]);
                }
            }
        }

        $this->info("Synced " . count($roomTypes) . " room types.");
    }

    protected function syncRatePlans(ChannexService $channex): void
    {
        $this->info("Starting sync of rate plans from Channex...");

        try {
            $ratePlans = $channex->getAllRatePlans(); // Assumes your service returns all rate plans

            foreach ($ratePlans as $plan) {
                $attr = $plan['attributes'];
                $roomExternalId = $plan['relationships']['room_type']['data']['id'] ?? null;

                $room = Room::where('external_id', $roomExternalId)->first();
                if (!$room) {
                    $this->warn("Skipping rate plan {$plan['id']} - Room not found for external_id: $roomExternalId");
                    continue;
                }

                $ratePlan = RatePlan::updateOrCreate(
                    ['external_id' => $plan['id']],
                    [
                        'property_id' => $room->property_id,
                        'room_id'     => $room->id,
                        'title'       => $attr['title'] ?? 'Untitled',
                        'currency'    => $attr['currency'] ?? 'USD',
                        'sell_mode'   => $attr['sell_mode'] ?? 'per_room',
                        'rate_mode'   => $attr['rate_mode'] ?? 'manual',
                        'meal_type'   => $attr['meal_type'] ?? 'none',
                    ]
                );

                $existingOptionIds = $ratePlan->occupancyOptions()->pluck('external_id')->toArray();
                $incomingOptionIds = collect($attr['options'] ?? [])->pluck('id')->toArray();

                // remove if not exist
                $ratePlan->occupancyOptions()
                    ->whereNotIn('external_id', $incomingOptionIds)
                    ->delete();

                //update or create new occupancy option
                foreach ($attr['options'] ?? [] as $occupancyOption) {
                    $ratePlan->occupancyOptions()->updateOrCreate(
                        ['external_id' => $occupancyOption['id']],
                        [
                            'occupancy'   => $occupancyOption['occupancy'],
                            'is_primary'  => $occupancyOption['is_primary'] ?? false,
                            'rate'        => $occupancyOption['rate'] ?? 0,
                        ]
                    );
                }
            }

            $this->info("Synced " . count($ratePlans) . " rate plans.");
        } catch (\Exception $e) {
            logger()->error("Failed to sync rate plans", ['error' => $e->getMessage()]);
            $this->error("Error: " . $e->getMessage());
        }
    }
}
