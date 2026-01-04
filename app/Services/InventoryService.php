<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Room;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Cập nhật inventory khi có thay đổi số lượng phòng
     * 
     * @param Room $room Room type cần cập nhật
     * @param int $availabilityChange Số lượng thay đổi (có thể âm hoặc dương)
     * @param string|null $startDate Ngày bắt đầu cập nhật (mặc định là hôm nay)
     * @param string|null $endDate Ngày kết thúc cập nhật (mặc định là 1 năm sau)
     */
    public function updateInventoryForRoomChange(
        Room $room,
        int $availabilityChange,
        ?string $startDate = null,
        ?string $endDate = null
    ): void {
        $property = $room->property;
        if (!$property) {
            Log::warning('Room has no property, skipping inventory update', ['room_id' => $room->id]);
            return;
        }

        // Set default date range if not provided
        $startDate = $startDate ?: now()->toDateString();
        $endDate = $endDate ?: now()->addYear()->toDateString();

        // 1. Cập nhật local inventory trước
        $this->updateLocalInventory($room, $availabilityChange, $startDate, $endDate);

        // 2. Nếu property có bật sync với Channex thì sync
        if ($property->is_sync_enabled && $property->external_id && $room->external_id) {
            $this->syncToChannex($room, $availabilityChange, $startDate, $endDate);
        }
    }

    /**
     * Cập nhật inventory trong local database
     */
    private function updateLocalInventory(Room $room, int $availabilityChange, string $startDate, string $endDate): void
    {
        $property = $room->property;
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        logger($availabilityChange);

        // Lặp qua từng ngày trong khoảng thời gian
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateString = $date->toDateString();
            logger($dateString);
            // Kiểm tra xem có inventory record nào đã tồn tại cho ngày này không
            $existingInventory = Inventory::where([
                'property_id' => $property->id,
                'room_type_id' => $room->id,
                'date' => $dateString,
            ])->first();

            if ($existingInventory) {
                // Nếu đã có record, cập nhật availability hiện tại
                logger($dateString);
                logger($existingInventory);
                $newAvailability = max(0, $existingInventory->availability + $availabilityChange);
                $existingInventory->update(['availability' => $newAvailability]);
            } else {
                // TODO: check
                // // Nếu chưa có record, tính toán availability dựa trên bookings hiện tại
                // $bookedUnits = $this->getBookedUnitsForDate($room, $dateString);
                // $totalUnits = $room->roomUnits()->count();
                // $calculatedAvailability = max(0, $totalUnits - $bookedUnits);

                // // Chỉ tạo record nếu availability khác với số lượng phòng mặc định
                // // hoặc nếu có booking nào đó
                // if ($calculatedAvailability !== $totalUnits || $bookedUnits > 0) {
                //     Inventory::create([
                //         'property_id' => $property->id,
                //         'room_type_id' => $room->id,
                //         'date' => $dateString,
                //         'availability' => $calculatedAvailability,
                //     ]);
                // }
            }
        }
    }

    /**
     * Lấy số lượng phòng đã được đặt cho một ngày cụ thể
     */
    private function getBookedUnitsForDate(Room $room, string $date): int
    {
        return Booking::where('room_id', $room->id)
            ->where(function ($query) use ($date) {
                $query->where(function ($q) use ($date) {
                    $q->where('check_in_date', '<=', $date)
                        ->where('check_out_date', '>', $date);
                })->orWhere(function ($q) use ($date) {
                    // Handle same-day bookings
                    $q->where('check_in_date', '=', $date)
                        ->where('check_out_date', '=', $date);
                });
            })
            ->whereNotIn('status', ['Hủy', 'Bị Hủy'])
            ->count();
    }

    /**
     * Sync thay đổi lên Channex
     */
    private function syncToChannex(Room $room, int $availabilityChange, string $startDate, string $endDate): void
    {
        try {
            $property = $room->property;
            $channexService = app(ChannexService::class);

            // Lấy availability hiện tại từ Channex
            $currentAvailability = $channexService->getAvailability(
                $property->external_id,
                $startDate,
                $endDate
            );

            $updateAvailabilities = [];

            // Xử lý từng ngày và điều chỉnh availability theo thay đổi
            if (isset($currentAvailability[$room->external_id])) {
                foreach ($currentAvailability[$room->external_id] as $date => $currentAvl) {
                    $newAvailability = max(0, $currentAvl + $availabilityChange);

                    $updateAvailabilities[] = [
                        'property_id'   => $property->external_id,
                        'room_type_id'  => $room->external_id,
                        'date'          => $date,
                        'availability'  => $newAvailability,
                    ];
                }
            }

            // Nếu không có availability hiện tại, tạo mới cho toàn bộ khoảng thời gian
            if (empty($updateAvailabilities)) {
                $newUnitCount = $room->roomUnits()->count();
                $updateAvailabilities = [
                    [
                        'property_id'   => $property->external_id,
                        'room_type_id'  => $room->external_id,
                        'date_from'     => $startDate,
                        'date_to'       => $endDate,
                        'availability'  => $newUnitCount,
                    ]
                ];
            }

            if (!empty($updateAvailabilities)) {
                $channexService->updateAvailability($updateAvailabilities);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync inventory changes to Channex', [
                'room_id' => $room->id,
                'message' => $e->getMessage(),
                'availability_change' => $availabilityChange
            ]);
        }
    }

    /**
     * Cập nhật inventory khi tạo mới room type
     */
    public function updateInventoryForNewRoom(Room $room): void
    {
        $property = $room->property;
        if (!$property) return;

        $availability = $room->roomUnits()->count();
        $startDate = now()->toDateString();
        $endDate = now()->addDays(499)->toDateString();

        // 1. Cập nhật local inventory
        $this->updateLocalInventory($room, $availability, $startDate, $endDate);

        // 2. Sync với Channex nếu cần
        if ($property->is_sync_enabled && $property->external_id && $room->external_id) {
            $this->syncToChannex($room, $availability, $startDate, $endDate);
        }
    }
}
