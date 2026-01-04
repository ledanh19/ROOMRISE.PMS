<?php

namespace App\Observers;

use App\Models\Booking;
use App\Services\ChannexService;
use Carbon\Carbon;

class BookingObserver
{
    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        $this->updateInventoryForBooking($booking, 'created');
        $this->syncAvailability($booking);
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        // Kiểm tra nếu status thay đổi thành hủy
        if ($booking->wasChanged('status') && in_array($booking->status, ['Hủy', 'Bị Hủy'])) {
            $this->updateInventoryForBooking($booking, 'cancelled');
        } else {
            // Tính toán lại inventory từ đầu cho booking này
            $this->recalculateInventoryForBooking($booking);
        }

        $this->syncAvailability($booking);
    }

    /**
     * Handle the Booking "deleted" event.
     */
    public function deleted(Booking $booking): void
    {
        $this->updateInventoryForBooking($booking, 'deleted');
        $this->syncAvailability($booking);
    }

    /**
     * Cập nhật inventory cho booking
     * 
     * @param Booking $booking
     * @param string $action 'created', 'deleted', 'cancelled'
     */
    protected function updateInventoryForBooking(Booking $booking, string $action): void
    {
        // Không cập nhật inventory cho booking ở quá khứ
        if ($this->isBookingInPast($booking)) {
            return;
        }

        // Load booking rooms
        $booking->load('bookingRooms.room.property');

        // Nhóm booking rooms theo room_id
        $bookingRoomsByRoom = $booking->bookingRooms->groupBy('room_id');

        foreach ($bookingRoomsByRoom as $roomId => $bookingRooms) {
            $room = $bookingRooms->first()->room;
            if (!$room) continue;

            $property = $room->property;
            if (!$property) continue;

            // Tính tổng số phòng được đặt cho room type này
            $totalBookedUnits = $bookingRooms->count();

            // Xác định hệ số cộng/trừ dựa trên action
            $multiplier = $this->getInventoryMultiplier($action);

            // Cập nhật inventory cho từng ngày
            $this->updateInventoryForRoomAndDates(
                $property,
                $room,
                $booking->check_in_date,
                $booking->check_out_date,
                $totalBookedUnits * $multiplier
            );
        }
    }

    /**
     * Tính toán lại inventory từ đầu cho booking
     */
    protected function recalculateInventoryForBooking(Booking $booking): void
    {
        // Không cập nhật inventory cho booking ở quá khứ
        if ($this->isBookingInPast($booking)) {
            return;
        }

        // Load booking rooms
        $booking->load('bookingRooms.room.property');

        // Nhóm booking rooms theo room_id
        $bookingRoomsByRoom = $booking->bookingRooms->groupBy('room_id');

        foreach ($bookingRoomsByRoom as $roomId => $bookingRooms) {
            $room = $bookingRooms->first()->room;
            if (!$room) continue;

            $property = $room->property;
            if (!$property) continue;

            // Tính toán lại inventory cho từng ngày
            $this->recalculateInventoryForRoomAndDates(
                $property,
                $room,
                $booking->check_in_date,
                $booking->check_out_date
            );
        }
    }

    /**
     * Kiểm tra booking có ở quá khứ không
     */
    protected function isBookingInPast(Booking $booking): bool
    {
        $checkoutDate = Carbon::parse($booking->check_out_date);
        return $checkoutDate->isPast();
    }

    /**
     * Xác định hệ số cộng/trừ cho inventory
     */
    protected function getInventoryMultiplier(string $action): int
    {
        switch ($action) {
            case 'created':
                return -1; // Trừ đi số phòng
            case 'deleted':
            case 'cancelled':
                return 1; // Cộng lại số phòng
            default:
                return 0;
        }
    }

    /**
     * Cập nhật inventory cho room và khoảng thời gian
     */
    protected function updateInventoryForRoomAndDates(
        $property,
        $room,
        string $checkInDate,
        string $checkOutDate,
        int $availabilityChange
    ): void {
        $checkIn = Carbon::parse($checkInDate);
        $checkOut = Carbon::parse($checkOutDate);

        // Xử lý trường hợp checkin/checkout cùng ngày
        if ($checkIn->isSameDay($checkOut)) {
            $checkOut = $checkIn->copy()->addDay();
        }

        // Tính số đêm
        $nights = $checkIn->diffInDays($checkOut);

        // Cập nhật inventory cho từng đêm
        for ($i = 0; $i < $nights; $i++) {
            $currentDate = $checkIn->copy()->addDays($i);
            $dateString = $currentDate->format('Y-m-d');

            $this->updateInventoryForDate($property, $room, $dateString, $availabilityChange);
        }
    }

    /**
     * Tính toán lại inventory cho room và khoảng thời gian
     */
    protected function recalculateInventoryForRoomAndDates(
        $property,
        $room,
        string $checkInDate,
        string $checkOutDate
    ): void {
        $checkIn = Carbon::parse($checkInDate);
        $checkOut = Carbon::parse($checkOutDate);

        // Xử lý trường hợp checkin/checkout cùng ngày
        if ($checkIn->isSameDay($checkOut)) {
            $checkOut = $checkIn->copy()->addDay();
        }

        // Tính số đêm
        $nights = $checkIn->diffInDays($checkOut);

        // Tính toán lại inventory cho từng đêm
        for ($i = 0; $i < $nights; $i++) {
            $currentDate = $checkIn->copy()->addDays($i);
            $dateString = $currentDate->format('Y-m-d');

            $this->recalculateInventoryForDate($property, $room, $dateString);
        }
    }

    /**
     * Cập nhật inventory cho một ngày cụ thể
     */
    protected function updateInventoryForDate($property, $room, string $date, int $availabilityChange): void
    {
        // Lấy inventory hiện tại
        $inventory = \App\Models\Inventory::where([
            'property_id' => $property->id,
            'room_type_id' => $room->id,
            'date' => $date,
            'rate_plan_id' => null,
        ])->first();

        if ($inventory) {
            // Cập nhật availability hiện tại
            $newAvailability = max(0, $inventory->availability + $availabilityChange);
            $inventory->update(['availability' => $newAvailability]);
        } else {
            // Nếu chưa có inventory record, tính toán từ đầu
            $totalUnits = $room->roomUnits()->count();
            $bookedUnits = $this->getBookedUnitsForDate($room, $date);
            $calculatedAvailability = max(0, $totalUnits - $bookedUnits + $availabilityChange);

            \App\Models\Inventory::create([
                'property_id' => $property->id,
                'room_type_id' => $room->id,
                'date' => $date,
                'availability' => $calculatedAvailability,
            ]);
        }
    }

    /**
     * Tính toán lại inventory cho một ngày cụ thể
     */
    protected function recalculateInventoryForDate($property, $room, string $date): void
    {
        $totalUnits = $room->roomUnits()->count();
        $bookedUnits = $this->getBookedUnitsForDate($room, $date);
        $calculatedAvailability = max(0, $totalUnits - $bookedUnits);

        // Update or create inventory record
        \App\Models\Inventory::updateOrCreate(
            [
                'property_id' => $property->id,
                'room_type_id' => $room->id,
                'date' => $date,
            ],
            [
                'availability' => $calculatedAvailability,
            ]
        );
    }

    /**
     * Lấy số phòng đã được đặt cho một ngày cụ thể
     */
    protected function getBookedUnitsForDate($room, string $date): int
    {
        return \App\Models\BookingRoom::where('room_id', $room->id)
            ->where(function ($query) use ($date) {
                $query->where(function ($q) use ($date) {
                    $q->where('check_in_date', '<=', $date)
                        ->where('check_out_date', '>', $date);
                })->orWhere(function ($q) use ($date) {
                    // Xử lý trường hợp checkin/checkout cùng ngày
                    $q->where('check_in_date', '=', $date)
                        ->where('check_out_date', '=', $date);
                });
            })
            ->whereHas('booking', function ($q) {
                // Chỉ tính các booking có status hợp lệ
                $q->whereNotIn('status', ['Hủy', 'Bị Hủy']);
            })
            ->count();
    }

    protected function syncAvailability(Booking $booking)
    {
        // Sync với Channex cho tất cả các room types trong booking
        $booking->load('bookingRooms.room.property');

        foreach ($booking->bookingRooms as $bookingRoom) {
            $room = $bookingRoom->room;
            if (!$room) continue;

            $property = $room->property;
            if (!$property || !$property->is_sync_enabled || !$room->external_id) {
                continue;
            }

            app(ChannexService::class)->recalculateAndSyncAvailabilityForRoom(
                $room,
                $booking->check_in_date,
                $booking->check_out_date
            );
        }
    }
}
