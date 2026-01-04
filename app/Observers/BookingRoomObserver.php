<?php

namespace App\Observers;

use App\Models\BookingRoom;
use App\Models\Inventory;
use Carbon\Carbon;

class BookingRoomObserver
{
    public function created(BookingRoom $bookingRoom)
    {
        if ($this->shouldUpdateInventory($bookingRoom)) {
            $this->updateInventory($bookingRoom, -1);
        }
    }

    public function updated(BookingRoom $bookingRoom)
    {
        if ($bookingRoom->wasChanged('room_status') && $bookingRoom->room_status === 'Hủy') {
            $this->updateInventory($bookingRoom, 1);
        }
    }

    public function deleted(BookingRoom $bookingRoom)
    {
        $this->updateInventory($bookingRoom, 1);
    }

    protected function shouldUpdateInventory(BookingRoom $bookingRoom): bool
    {
        // Kiểm tra booking status thông qua relationship
        // Todo: kiểm tra status trong bookingRoom?
        $bookingStatus = $bookingRoom->booking->status ?? '';
        return !$this->isCancelledStatus($bookingStatus);
    }

    protected function isCancelledStatus(?string $status): bool
    {
        if (!$status) return false;

        $cancelledStatuses = ['Hủy'];
        return in_array($status, $cancelledStatuses);
    }

    protected function updateInventory(BookingRoom $bookingRoom, int $delta)
    {
        $checkIn = Carbon::parse($bookingRoom->check_in_date);
        $checkOut = Carbon::parse($bookingRoom->check_out_date);

        for ($date = $checkIn->copy(); $date->lt($checkOut); $date->addDay()) {
            $inventory = Inventory::firstOrNew([
                'property_id' => $bookingRoom->property_id,
                'room_type_id' => $bookingRoom->room_id,
                'date' => $date->format('Y-m-d'),
                'rate_plan_id' => null,
            ]);

            $inventory->availability = max(0, ($inventory->availability ?? $inventory->room_type->roomUnits()->count()) + $delta);
            $inventory->save();
        }
    }
}
