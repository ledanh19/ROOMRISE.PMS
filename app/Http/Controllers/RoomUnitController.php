<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Helpers\RoleHelper;
use App\Http\Requests\RoomUnitRequest;
use App\Http\Resources\RoomResource;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomUnit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomUnitController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(RoomUnit::class, 'room');
    }

    public function index(Request $request)
    {
        $search = $request->search;
        $partnerGroupId = RoleHelper::getScopedPartnerGroupId();

        $propertyOptions = Property::select('id', 'name')
            ->when($partnerGroupId, fn($q) => $q->where('partner_group_id', $partnerGroupId))
            ->get();

        $paginate = is_numeric($request->paginate) && $request->paginate > 0 ? $request->paginate : 10;

        $filters = $request->only(['search', 'paginate', 'property_id']);
        $propertyId = $filters['property_id'] ?? null;
        $filters['property_id'] = $propertyId;

        $data = Room::with('roomUnits')
            ->when($search, fn($q) => $q->where('name', 'LIKE', "%{$search}%"))
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->when($partnerGroupId, function ($query) use ($partnerGroupId) {
                $query->whereHas('property', fn($q) => $q->where('partner_group_id', $partnerGroupId));
            })
            ->paginate($paginate)
            ->appends($filters);

        return Inertia::render('RoomUnits/Index', [
            'data' => ResponseHelper::dataTable($data, RoomResource::class),
            'propertyOptions' => $propertyOptions,
            'filters' => $filters,
        ]);
    }



    public function update(RoomUnitRequest $request, RoomUnit $room)
    {
        $validated = $request->validated();
        $room->update($validated);

        return redirect()->back()->with('updated', 'Cập nhật phòng thành công');
    }
}
