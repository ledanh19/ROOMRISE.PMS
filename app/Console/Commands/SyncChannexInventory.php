<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Services\ChannexService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncChannexInventory extends Command
{
    protected $signature = 'channex:sync-inventory {property_id}';
    protected $description = 'Syncs inventory (availability and rates) from Channex for a given property for the next 365 days.';

    protected $channexService;

    public function __construct(ChannexService $channexService)
    {
        parent::__construct();
        $this->channexService = $channexService;
    }

    public function handle()
    {
        $propertyId = $this->argument('property_id');
        $property = Property::find($propertyId);

        if (!$property) {
            $this->error("Property with local ID {$propertyId} not found.");
            return 1;
        }

        if (!$property->external_id) {
            $this->error("Property '{$property->name}' does not have a Channex ID (external_id).");
            return 1;
        }

        $this->info("Starting inventory sync for '{$property->name}'...");

        $localRooms = Room::where('property_id', $property->id)->whereNotNull('external_id')->get()->keyBy('external_id');
        $localRatePlans = RatePlan::where('property_id', $property->id)->whereNotNull('external_id')->get()->keyBy('external_id');

        $startDate = Carbon::now();
        $endDate = $startDate->clone()->addDays(365);
        $channexPropertyId = $property->external_id;

        $dateChunks = $startDate->toPeriod($endDate, '30 days');

        try {
            foreach ($dateChunks as $chunkStartDate) {
                $chunkEndDate = $chunkStartDate->clone()->addDays(29)->min($endDate);
                $this->info("Syncing from {$chunkStartDate->toDateString()} to {$chunkEndDate->toDateString()}...");

                // Sync Availability
                $availabilityData = $this->channexService->getAvailability($channexPropertyId, $chunkStartDate->toDateString(), $chunkEndDate->toDateString());
                foreach ($availabilityData as $channexRoomTypeId => $dates) {
                    if (isset($localRooms[$channexRoomTypeId])) {
                        $localRoom = $localRooms[$channexRoomTypeId];
                        foreach ($dates as $date => $availability) {
                            Inventory::updateOrCreate(
                                [
                                    'property_id' => $property->id,
                                    'date' => $date,
                                    'room_type_id' => $localRoom->id,
                                    'rate_plan_id' => null,
                                ],
                                [
                                    'availability' => $availability,
                                ]
                            );
                        }
                    }
                }

                // Sync Rates
                $ratesData = $this->channexService->getRates($channexPropertyId, $chunkStartDate->toDateString(), $chunkEndDate->toDateString());
                foreach ($ratesData as $channexRatePlanId => $dates) {
                    if (isset($localRatePlans[$channexRatePlanId])) {
                        $localRatePlan = $localRatePlans[$channexRatePlanId];
                        foreach ($dates as $date => $rateData) {
                            Inventory::updateOrCreate(
                                [
                                    'property_id' => $property->id,
                                    'date' => $date,
                                    'room_type_id' => $localRatePlan->room_id,
                                    'rate_plan_id' => $localRatePlan->id,
                                ],
                                [
                                    'rate' => $rateData['rate'],
                                ]
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("An error occurred during sync: " . $e->getMessage());
            Log::error($e);
            return 1;
        }

        $this->info("Successfully synced inventory for '{$property->name}'.");
        return 0;
    }
}
