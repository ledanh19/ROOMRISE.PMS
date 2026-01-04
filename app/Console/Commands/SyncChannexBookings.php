<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomUnit;
use App\Services\ChannexService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncChannexBookings extends Command
{
    protected $signature = 'sync:channex-bookings';
    protected $description = 'Sync bookings from Channex via booking revision feed';

    public function handle(ChannexService $channex)
    {
        $this->info("Starting booking sync from Channex...");

        try {
            $revisions = $channex->getBookingRevisionsFeed();

            if (empty($revisions)) {
                $this->info("No new booking revisions found.");
                return;
            }

            $ackIds = [];

            logger($revisions);

            // foreach ($revisions as $revision) {
            //     $revisionId = $revision['id'] ?? null;

            //     if (!$revisionId) {
            //         $this->warn("Missing revision ID");
            //         continue;
            //     }

            //     try {
            //         $this->handleBookingRevision($channex, $revisionId);
            //         $ackIds[] = $revisionId;
            //     } catch (\Exception $e) {
            //         Log::error("Failed to process booking revision $revisionId", ['error' => $e->getMessage()]);
            //         $this->error("Error with revision $revisionId: " . $e->getMessage());
            //     }
            // }

            // if (!empty($ackIds)) {
            //     $channex->acknowledgeBookingRevisions($ackIds);
            //     $this->info("Acknowledged " . count($ackIds) . " booking revisions.");
            // }

        } catch (\Exception $e) {
            Log::error("Booking sync failed", ['error' => $e->getMessage()]);
            $this->error("Booking sync failed: " . $e->getMessage());
        }
    }

    protected function handleBookingRevision(ChannexService $channex, string $revisionId)
    {
        $data = $channex->getBookingRevisionDetail($revisionId);
        $attr = $data['attributes'] ?? [];

        $roomData = $attr['rooms'][0] ?? [];
        $customerData = $attr['customer'] ?? [];

        $customer = Customer::firstOrCreate(
            ['email' => $customerData['mail']],
            [
                'full_name' => trim(($customerData['name'] ?? '') . ' ' . ($customerData['surname'] ?? '')),
                'phone' => $customerData['phone'] ?? '',
                'address' => $customerData['address'] ?? '',
                'country' => $customerData['country'] ?? '',
                'city' => $customerData['city'] ?? '',
                'type' => 'OTA'
            ]
        );

        $statusMap = [
            'new' => 'Mới',
            'modified' => 'Yêu cầu',
            'cancelled' => 'Hủy',
        ];
        $rawStatus = $attr['status'] ?? null;

        $roomId = Room::where('external_id', $roomData['room_type_id'])->value('id');
        $propertyId = Property::where('external_id', $attr['property_id'])->value('id');

        $availableRoomUnitId = null;
        if ($rawStatus === 'new') {
            $roomUnitIds = RoomUnit::where('room_id', $roomId)->pluck('id');

            $conflictedRoomUnitIds = Booking::whereIn('room_unit_id', $roomUnitIds)
                ->where(function ($query) use ($attr) {
                    $query->where('check_in_date', '<', $attr['departure_date'])
                        ->where('check_out_date', '>', $attr['arrival_date']);
                })
                ->pluck('room_unit_id');

            $availableRoomUnitId = $roomUnitIds->diff($conflictedRoomUnitIds)->random(null);
        }

        Booking::updateOrCreate(
            ['external_id' => $attr['booking_id']],
            [
                'ota_name' => $attr['ota_name'],
                'status' => $statusMap[$rawStatus] ?? 'Không xác định',
                'property_id' => $propertyId,
                'room_id' => $roomId,
                'room_unit_id' => $availableRoomUnitId,
                'room_price_at_booking' => $roomData['amount'] ?? 0,
                'check_in_date' => $attr['arrival_date'],
                'check_out_date' => $attr['departure_date'],
                'check_in_time' => $attr['arrival_hour'] ?? '00:00:00',
                'check_out_time' => '00:00:00',
                'customer_id' => $customer->id,
                'payment_type' => $attr['payment_type'] ?? null,
                'total_amount' => $attr['amount'] ?? 0,
                'paid' => 0,
                'remaining' => $attr['amount'] ?? 0,
                'payment_status' => 'Unpaid',
            ]
        );
    }
}
