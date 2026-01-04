<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Helpers\RoleHelper;
use App\Http\Requests\RoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Property;
use App\Models\Room;
use App\Services\ChannexService;
use App\Services\InventoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class RoomController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Room::class, 'room_type');
    }

    public function index(Request $request)
    {
        $partnerGroupId = RoleHelper::getScopedPartnerGroupId();

        $propertyOptions = Property::select('id', 'name')
            ->when($partnerGroupId, fn($query) => $query->where('partner_group_id', $partnerGroupId))
            ->get();
        $search = $request->search;
        $paginate = is_numeric($request->paginate) && $request->paginate > 0 ? $request->paginate : 10;

        $filters = $request->only(['search', 'paginate', 'property_id']);
        $propertyId = $filters['property_id'] ?? null;

        $filters['property_id'] = $propertyId;

        $data = Room::with('roomUnits')->when($search, function ($query) use ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        })
            ->when($partnerGroupId, function ($query) use ($partnerGroupId) {
                $query->whereHas('property', function ($q) use ($partnerGroupId) {
                    $q->where('partner_group_id', $partnerGroupId);
                });
            })

            ->when($propertyId, function ($query) use ($propertyId) {
                $query->where('property_id', $propertyId);
            })
            ->paginate($paginate)
            ->appends($filters);

        return Inertia::render('Rooms/Index', [
            'data' => ResponseHelper::dataTable($data, RoomResource::class),
            'properties' => $propertyOptions,
            'filters' => $filters,
        ]);
    }



    public function unitsByRoom(Room $room)
    {
        return $room->roomUnits()->select('id', 'name')->get();
    }

    public function store(RoomRequest $request)
    {
        $validated = $request->validated();

        // Check if property has reached max_room_types limit
        $property = Property::find($validated['property_id']);
        $currentRoomTypesCount = Room::where('property_id', $validated['property_id'])->count();

        if ($property && $property->max_room_types && $currentRoomTypesCount >= $property->max_room_types) {
            return redirect()->back()->with('error', 'Chỗ nghỉ này đã đạt giới hạn số loại phòng tối đa.');
        }

        $validated['unit'] = 'unknown';
        $validated['max_people'] = 0;

        $room = Room::create($validated);

        foreach ($validated['room_units'] as $unitData) {
            $room->roomUnits()->create(['name' => $unitData['name']]);
        }

        // Sync Channex
        $property = $room->property;
        if ($property && $property->is_sync_enabled && $property->external_id) {
            $channexService = app(\App\Services\ChannexService::class);

            try {
                $externalId = $channexService->createRoomType([
                    'property_id' => $property->external_id,
                    'title' => $room->name,
                    'count_of_rooms' => $room->quantity,
                    'occ_adults' => $room->adults ?? 0,
                    'default_occupancy' => $room->adults ?? 0,
                    'occ_children' => $room->children ?? 0,
                    'occ_infants' => 0, // Todo: add this field in roomise
                ]);

                $room->update(['external_id' => $externalId]);
            } catch (Exception $e) {
                Log::error('Failed to sync room to Channex', ['message' => $e->getMessage()]);
            }
        }

        // Cập nhật inventory cho room type mới
        app(InventoryService::class)->updateInventoryForNewRoom($room);

        return redirect()->back()->with('created', 'Thêm mới phòng thành công');
    }

    public function update(RoomRequest $request, Room $roomType)
    {
        $validated = $request->validated();
        // Store the original count of room units before update
        $originalUnitCount = $roomType->roomUnits()->count();

        $roomType->update($validated);

        $submittedUnitIds = [];
        foreach ($validated['room_units'] as $unitData) {
            if (!empty($unitData['id'])) {
                $unit = $roomType->roomUnits()->find($unitData['id']);
                if ($unit) {
                    $unit->update(['name' => $unitData['name']]);
                    $submittedUnitIds[] = $unit->id;
                }
            } else {
                $newUnit = $roomType->roomUnits()->create(['name' => $unitData['name']]);
                $submittedUnitIds[] = $newUnit->id;
            }
        }

        $roomType->roomUnits()->whereNotIn('id', $submittedUnitIds)->doesntHave('bookings')->delete();

        // Calculate the new count of room units after update
        $newUnitCount = $roomType->roomUnits()->count();
        // Calculate the availability change
        $availabilityChange = $newUnitCount - $originalUnitCount;

        // Sync Channex
        $property = $roomType->property;
        if ($property && $property->is_sync_enabled && $property->external_id) {
            $channexService = app(\App\Services\ChannexService::class);

            try {
                if ($roomType->external_id) {
                    // synced => update in channex
                    $channexService->updateRoomType($roomType->external_id, [
                        'property_id' => $property->external_id,
                        'title' => $roomType->name,
                        'count_of_rooms' => $roomType->quantity,
                        'occ_adults' => $roomType->adults ?? 0,
                        'default_occupancy' => $roomType->adults ?? 0,
                        'occ_children' => $roomType->children ?? 0,
                        'occ_infants' => 0,
                    ]);
                } else {
                    // not yet => create in channex
                    $externalId = $channexService->createRoomType([
                        'property_id' => $property->external_id,
                        'title' => $roomType->name,
                        'count_of_rooms' => $roomType->quantity,
                        'occ_adults' => $roomType->adults ?? 0,
                        'default_occupancy' => $roomType->adults ?? 0,
                        'occ_children' => $roomType->children ?? 0,
                        'occ_infants' => 0,
                    ]);

                    $roomType->update(['external_id' => $externalId]);

                    app(InventoryService::class)->updateInventoryForNewRoom($roomType);
                }
            } catch (\Exception $e) {
                Log::error('Failed to sync room type to Channex', ['message' => $e->getMessage()]);
            }
        }

        // Cập nhật inventory nếu có thay đổi số lượng phòng
        if ($availabilityChange != 0) {
            app(InventoryService::class)->updateInventoryForRoomChange($roomType, $availabilityChange);
        }

        return redirect()->back()->with('updated', 'Cập nhật phòng thành công');
    }

    public function destroy(Room $roomType)
    {
        $property = $roomType->property;

        if ($property && $property->is_sync_enabled && $roomType->external_id) {
            $channexService = app(\App\Services\ChannexService::class);

            try {
                $channexService->deleteRoomType($roomType->external_id);
            } catch (\Exception $e) {
                Log::error('Failed to delete room type from Channex', ['message' => $e->getMessage()]);
            }
        }

        $roomType->delete();
        return back()->with('deleted', 'Phòng đã được xóa');
    }
}
