<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Models\Booking;
use App\Models\BookingCustomer;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RatePlanOTA;
use App\Models\Room;
use App\Models\RoomUnit;
use App\Services\ChannexService;
use App\Services\PancakeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChannexWebhookController extends ApiController
{
    public function handleWebhook(Request $request)
    {
        $event = $request->input('event');
        $payload = $request->input('payload');
        $propertyID = $request->input('property_id');

        switch ($event) {
            case 'booking':
                logger("Received booking revision_id webhook: " . $payload['revision_id']);
                $this->handleGenericBooking($payload, $propertyID);
                break;
            case 'new_channel':
                logger("Fetch feature booking when connected to OTA on property: " . $payload['property_name']);
                $this->handleActiveChannel($payload);
                break;

            default:
                Log::info("Unhandled event received from Channex", [
                    'event' => $event,
                    // 'payload' => $payload,
                ]);
        }


        return response()->json(['status' => 'ok']);
    }

    private function handleGenericBooking($payload, $propertyId)
    {
        $revisionId = $payload['revision_id'] ?? null;
        if (!$revisionId) {
            Log::warning('Missing revision_id in booking payload', compact('payload'));
            return;
        }

        try {
            $data = app(ChannexService::class)->getBookingRevisionDetail($revisionId);
            $rooms = $data['attributes']['rooms'] ?? [];
            $customerData = $data['attributes']['customer'] ?? [];

            // Check if property exists in database, if not ignore this booking
            $property = Property::where('external_id', $data['attributes']['property_id'])->first();
            if (!$property) {
                Log::info('Property not found in database, ignoring booking', [
                    'property_external_id' => $data['attributes']['property_id'],
                    'booking_id' => $data['attributes']['booking_id'] ?? null
                ]);
                return;
            }

            $email = $customerData['mail'] ?? null;

            if ($email) {
                $customer = Customer::firstOrCreate(
                    ['email' => $email],
                    [
                        'full_name' => ($customerData['name'] ?? '') . ' ' . ($customerData['surname'] ?? ''),
                        'phone' => $customerData['phone'] ?? '',
                        'address' => $customerData['address'] ?? '',
                        'country' => $customerData['country'] ?? '',
                        'city' => $customerData['city'] ?? '',
                        'type' => 'OTA',
                        'partner_group_id' => $property->partner_group_id ?? null,
                    ]
                );
            } else {
                $customer = Customer::create([
                    'full_name' => ($customerData['name'] ?? '') . ' ' . ($customerData['surname'] ?? ''),
                    'phone' => $customerData['phone'] ?? '',
                    'address' => $customerData['address'] ?? '',
                    'country' => $customerData['country'] ?? '',
                    'city' => $customerData['city'] ?? '',
                    'type' => 'OTA',
                    'partner_group_id' => $property->partner_group_id ?? null,
                ]);
            }

            $statusMap = [
                'new' => 'Mới',
                'modified' => 'Yêu cầu',
                'cancelled' => 'Hủy',
            ];

            $rawStatus = $data['attributes']['status'] ?? null;

            $checkIn = $data['attributes']['arrival_date'];
            $checkOut = $data['attributes']['departure_date'];

            // Create or update booking           
            $rawStatus = $data['attributes']['status'] ?? '';

            // if ($rawStatus === 'cancelled') {
            //     return;
            // }

            $isOtaCollect = $data['attributes']['payment_collect'] === 'ota';
            $payment_type = $isOtaCollect ? 'OTA Collect' : 'Hotel Collect';
            $room_payment_method = $isOtaCollect ? 'Thu bởi OTA' : 'Thu tại KS';
            $ota_fee = $isOtaCollect ? ($data['attributes']['ota_commission'] ?? 0) : null;
            $net_estimate = $isOtaCollect ? ($data['attributes']['amount'] - $ota_fee) : null;
            $ota_fee_percent = ($isOtaCollect && $data['attributes']['amount'] > 0)
                ? ($ota_fee / $data['attributes']['amount']) * 100
                : null;

            $isImported = isset($data['attributes']['meta']['is_imported']) && $data['attributes']['meta']['is_imported'];
            $otaName = $data['attributes']['ota_name'];
            if ($isImported) {
                $amount = 0;
            } else {
                if ($otaName === 'Expedia') {
                    $amount = $isOtaCollect ? app(ChannexService::class)->getRemittanceAmount($data['attributes']['notes']) : ($data['attributes']['amount'] ?? 0);
                } else {
                    $amount = $data['attributes']['amount'] ?? 0;
                }
            }

            $booking = Booking::updateOrCreate(
                ['external_id' => $data['attributes']['booking_id']],
                [
                    'ota_name' => $otaName,
                    'ota_reservation_code' => $data['attributes']['ota_reservation_code'] ?? null,
                    'status' => $statusMap[$rawStatus] ?? 'Không xác định',
                    'property_id' => $property->id,
                    'customer_id' => $customer->id,
                    'room_price_at_booking' => 0,
                    'check_in_date' => $data['attributes']['arrival_date'],
                    'check_out_date' => $data['attributes']['departure_date'],
                    'check_in_time' => $property->checkin_from_time ?? '00:00:00',
                    'check_out_time' => $property->checkout_to_time ?? '00:00:00',
                    'payment_type' => $payment_type,
                    'total_amount' => $amount,
                    'customer_payment_amount' => $amount,
                    'remaining' => $amount,
                    'net_estimate' => $amount,
                    'payout_received' => $amount,
                    'room_payment_method' => $room_payment_method,
                    'ota_fee' => 0,
                    'ota_fee_percent' => 0,
                    'adults' => $data['attributes']['occupancy']['adults'] ?? 0,
                    'children' => $data['attributes']['occupancy']['children'] ?? 0,
                    'newborn' => $data['attributes']['occupancy']['infants'] ?? 0,
                    'reconciliation_status' => ($isOtaCollect && $amount > 0) ? 'Khớp' : 'Chờ payout',
                    'difference_amount' => ($isOtaCollect && $amount > 0) ? 0 : null,
                    'payment_status' => 'Chưa thanh toán',
                    'is_imported' => $isImported,
                    'meta' => $data['attributes']['meta'] ?? [],
                    'currency' => $data['attributes']['currency'] ?? '',
                    'channel_id' => $data['attributes']['channel_id'] ?? '',
                    'notes' => $data['attributes']['notes'] ?? '',
                    'payment_collect' => $data['attributes']['payment_collect'] ?? '',
                    'payment_type_original' => $payment_type,
                    'rooms' => $rooms,
                    'raw_message' => json_encode($data['attributes']) ?? '',
                ]
            );

            BookingCustomer::where('booking_id', $booking->id)->delete();
            BookingCustomer::create([
                'booking_id' => $booking->id,
                'customer_id' => $customer->id,
            ]);

            // Xóa tất cả BookingRoom cũ của booking này
            BookingRoom::where('booking_id', $booking->id)->get()->each->delete();

            // Tạo lại BookingRoom mới dựa trên dữ liệu hiện tại
            foreach ($rooms as $room) {
                $roomId = Room::where('external_id', $room['room_type_id'])->value('id');

                if (!$roomId) {
                    Log::error('Room not found in database', [
                        'booking_id' => $data['attributes']['booking_id'] ?? null,
                        'room_type_id' => $room['room_type_id'],
                        'property_id' => $property->id
                    ]);
                    continue;
                }

                // Lấy rate_plan_id nội bộ từ external_id trả về từ Channex
                $ratePlanId = null;
                if (isset($room['rate_plan_id'])) {
                    $ratePlanOTA = RatePlanOTA::where('external_id', $room['rate_plan_id'])->first();
                    $ratePlanId = $ratePlanOTA && $ratePlanOTA->ratePlan ? $ratePlanOTA->ratePlan->id : null;
                }

                // Tìm room unit trống cho loại phòng này
                $roomUnitIds = RoomUnit::where('room_id', $roomId)->pluck('id');

                $conflictedRoomUnitIds = BookingRoom::whereIn('room_unit_id', $roomUnitIds)
                    ->where('booking_id', '!=', $booking->id) // Loại trừ booking hiện tại
                    ->where(function ($query) use ($checkIn, $checkOut) {
                        $query->where('check_in_date', '<', $checkOut)
                            ->where('check_out_date', '>', $checkIn);
                    })
                    ->pluck('room_unit_id');

                $availableRoomUnitId = $roomUnitIds
                    ->diff($conflictedRoomUnitIds)
                    ->shuffle()
                    ->first();

                // Tạo BookingRoom mới
                // $nights = isset($room['days']) ? count($room['days']) : 0;
                $nights = isset($room['days']) ? count((array) $room['days']) : 0;

                $priceDate = collect($room['days'] ?? [])
                    ->map(function ($price, $date) {
                        return $date . ':' . $price;
                    })
                    ->implode(',');

                BookingRoom::create([
                    'booking_id' => $booking->id,
                    'room_id' => $roomId,
                    'room_unit_id' => $availableRoomUnitId,
                    'property_id' => $property->id,
                    'room_price_at_booking' => $room['amount'],
                    'check_in_date' => $data['attributes']['arrival_date'],
                    'check_out_date' => $data['attributes']['departure_date'],
                    'check_in_time' => $property->checkin_from_time ?? '00:00:00',
                    'check_out_time' => $property->checkout_to_time ?? '00:00:00',
                    'rate_plan_id' => $ratePlanId,
                    'night' => $nights,
                    'price_date' => $priceDate,
                    'total' => $room['amount'],
                    'room_status' => 'Chưa nhận phòng',
                ]);
            }

            // Sync availability
            // $roomIds = $booking->bookingRooms->pluck('room_id')->unique();
            // foreach ($roomIds as $roomId) {
            //     $room = Room::find($roomId);
            //     $property = $room->property;
            //     if ($property && $property->is_sync_enabled && $room->external_id) {
            //         app(ChannexService::class)->recalculateAndSyncAvailabilityForRoom(
            //             $room,
            //             $booking->check_in_date,
            //             $booking->check_out_date
            //         );
            //     }
            // }

            try {
                // Chỉ cập nhật booking từ property của partner id lưu trong .env
                if (config('services.pancake.partner_id') && $property->partner_group_id == config('services.pancake.partner_id')) {
                    app(PancakeService::class)->syncBooking($booking);
                }
            } catch (\Throwable $th) {
                logger($th);
            }

            app(ChannexService::class)->acknowledgeBookingRevision($revisionId);
        } catch (\Exception $e) {
            logger($e);
            Log::error("Exception when handling booking webhook", ['error' => $e->getMessage()]);
        }
    }

    private function handleActiveChannel($payload)
    {
        $propertyId = $payload['property_id'];

        $property = Property::find($propertyId);
        if (!$property) {
            Log::info('Property not found in database, ignoring new channel', [
                'property_id' => $propertyId,
            ]);
            return;
        }

        $bookings = app(ChannexService::class)->getFutureBookingsByProperty($propertyId);
        foreach ($bookings as $booking) {
            app(ChannexService::class)->handleBooking($booking, $booking['attributes']['revision_id']);
        }
    }
}
