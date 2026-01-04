<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
use App\Models\BookingSource;
use App\Models\PartnerGroup;
use App\Models\Property;
use App\Services\ChannexService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;
use App\Models\User;

class PropertyController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Property::class, 'property');
    }

    public function index(Request $request)
    {
        $search = $request->search;
        $paginate = is_numeric($request->paginate) && $request->paginate > 0 ? $request->paginate : 10;
        $property_id = $request->property_id;
        $filters = $request->only(['search', 'paginate', 'property_id']);
        $partnerGroupId = RoleHelper::getScopedPartnerGroupId();

        $partnerGroup = PartnerGroup::select(['id', 'name'])->get();

        $data = Property::query()
            ->with('bookingSources')
            ->when($search, fn($query) => $query->where('name', 'LIKE', "%{$search}%"))
            ->when($partnerGroupId, fn($query) => $query->where('partner_group_id', $partnerGroupId))
            ->when($property_id, fn($query) => $query->where('id', $property_id))
            ->orderBy('created_at', 'desc')
            ->paginate($paginate)
            ->appends($filters);

        // Get Partner Admin users for the form
        // $partnerAdmins = User::role('Partner Admin')
        //     ->select('id', 'name', 'email')
        //     ->get();

        $bookingSources = BookingSource::select('id', 'name')->get();

        return Inertia::render('Properties/Index', [
            'data' => $data,
            'filters' => $filters,
            'partnerGroup' => $partnerGroup,
            // 'partnerAdmins' => $partnerAdmins,
            'bookingSources' => $bookingSources,
        ]);
    }

    public function list()
    {
        $partnerGroupId = RoleHelper::getScopedPartnerGroupId();

        // $query = Property::select('id', 'name', 'currency')
        //     ->when($partnerGroupId, fn($q) => $q->where('partner_group_id', $partnerGroupId));
        // return $query->get();

        return Property::when($partnerGroupId, fn($q) => $q->where('partner_group_id', $partnerGroupId))
            ->select('id', 'name', 'currency', 'checkin_from_time', 'checkout_to_time')->get();
    }



    public function roomsByProperty(Property $property)
    {
        return $property->rooms()->select('id', 'name')->get();
    }

    public function bookingSourcesByProperty(Property $property)
    {
        return $property->bookingSources()->select('id', 'name')->get();
    }

    public function store(Request $request, ChannexService $channexService)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_sync_enabled' => 'nullable|boolean',
            'checkin_from_time' => 'nullable|date_format:H:i',
            'checkout_to_time' => 'nullable|date_format:H:i',
            'deposit_amount' => 'nullable|numeric|min:0|max:999999999.99',
            'max_room_types' => 'required|integer|min:1',
            'max_rooms' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value < $request->input('max_room_types')) {
                        $fail('Số phòng tối đa phải lớn hơn hoặc bằng số loại phòng.');
                    }
                },
            ],
            'booking_source_ids' => 'nullable|array',
            'booking_source_ids.*' => 'exists:booking_sources,id',
        ]);
        if (!RoleHelper::isPartnerScopedUser()) {
            $request->validate([
                'partner_group_id' => 'required|exists:partner_groups,id'
            ]);
            $validated['partner_group_id'] = $request->input('partner_group_id');
        }

        if (RoleHelper::isPartnerScopedUser()) {
            $validated['partner_group_id'] = RoleHelper::getScopedPartnerGroupId();
        }

        $partnerGroup = PartnerGroup::find($validated['partner_group_id']);
        if ($partnerGroup) {
            $validated['owner_id'] = $partnerGroup->partner_admin_id;
        } else {
            $validated['owner_id'] = null; // fallback nếu group không tồn tại
        }

        // Set default values if not provided
        if (empty($validated['checkin_from_time'])) {
            $validated['checkin_from_time'] = '14:00';
        }
        if (empty($validated['checkout_to_time'])) {
            $validated['checkout_to_time'] = '12:00';
        }

        try {
            DB::transaction(function () use ($channexService, $validated) {

                $bookingSourceIds = $validated['booking_source_ids'] ?? [];
                unset($validated['booking_source_ids']);

                $property = Property::create($validated);

                if (!empty($bookingSourceIds)) {
                    $property->bookingSources()->attach($bookingSourceIds);
                }

                if ($property->is_sync_enabled) {
                    $externalId = $channexService->createProperty($property->toArray());
                    $webhookId = $channexService->createWebhook($externalId);

                    $property->update(['external_id' => $externalId, 'webhook_id' => $webhookId]);
                }
            });

            return redirect()->back()->with('created', 'Chỗ nghỉ được tạo thành công');
        } catch (Throwable $e) {
            Log::error('Transaction failed: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function update(Property $property, Request $request, ChannexService $channexService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_sync_enabled' => 'nullable|boolean',
            'checkin_from_time' => 'nullable|date_format:H:i',
            'checkout_to_time' => 'nullable|date_format:H:i',
            'deposit_amount' => 'nullable|numeric|min:0|max:999999999.99',
            'max_room_types' => 'required|integer|min:1',
            'max_rooms' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value < $request->input('max_room_types')) {
                        $fail('Số phòng tối đa phải lớn hơn hoặc bằng số loại phòng.');
                    }
                },
            ],
            'booking_source_ids' => 'nullable|array',
            'booking_source_ids.*' => 'exists:booking_sources,id',
        ]);

        $originalSyncState = $property->getOriginal('is_sync_enabled');

        try {
            DB::transaction(function () use ($property, $validated, $channexService, $originalSyncState) {
                $bookingSourceIds = $validated['booking_source_ids'] ?? [];
                unset($validated['booking_source_ids']);

                $property->update($validated);

                $originalIds = $property->bookingSources()->pluck('booking_sources.id')->toArray();
                $newIds = $bookingSourceIds;

                // Sync booking sources
                $property->bookingSources()->sync($bookingSourceIds);

                // Handle Channex sync
                $isSyncStateChanged = $originalSyncState !== $property->is_sync_enabled;
                if ($isSyncStateChanged || $property->is_sync_enabled) {
                    if ($property->is_sync_enabled) {
                        if (!$property->external_id) {
                            $externalId = $channexService->createProperty($property->toArray());
                            $webhookId = $channexService->createWebhook($externalId);
                            $property->update(['external_id' => $externalId, 'webhook_id' => $webhookId]);
                        } else {
                            $channexService->updateProperty($property->external_id, $property->toArray());
                            $channexService->updateWebhook($property, true);
                        }
                    } else {
                        if ($property->external_id) {
                            $channexService->updateWebhook($property, false);
                        }
                    }
                }
                $this->handleRatePlanBookingSourceChanges($property, $originalIds, $newIds, $channexService);
            });

            return back()->with('created', 'Chỗ nghỉ được cập nhật thành công');
        } catch (Throwable $e) {
            Log::error('Transaction failed: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function handleRatePlanBookingSourceChanges($property, $originalIds, $newIds, $channexService)
    {
        $addedIds = array_diff($newIds, $originalIds);
        $removedIds = array_diff($originalIds, $newIds);

        // Lấy tất cả rate plans của property này
        $ratePlans = $property->ratePlans()->with('ratePlanOTAs.bookingSource')->get();

        foreach ($ratePlans as $ratePlan) {
            // Xử lý xóa booking source
            foreach ($removedIds as $removedId) {
                $ratePlanOTA = $ratePlan->ratePlanOTAs()
                    ->where('booking_source_id', $removedId)
                    ->first();

                if ($ratePlanOTA && $ratePlanOTA->external_id) {
                    try {
                        // Xóa trên Channex
                        $channexService->deleteRatePlan($ratePlanOTA->external_id);
                        // Xóa trong DB
                        $ratePlanOTA->delete();
                    } catch (\Exception $e) {
                        Log::error('Failed to delete rate plan from Channex', [
                            'rate_plan_ota_id' => $ratePlanOTA->id,
                            'external_id' => $ratePlanOTA->external_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Xử lý thêm booking source mới
            // foreach ($addedIds as $addedId) {
            //     $bookingSource = \App\Models\BookingSource::find($addedId);
            //     if (!$bookingSource) continue;

            //     // Kiểm tra đã tồn tại chưa
            //     $existingRatePlanOTA = $ratePlan->ratePlanOTAs()
            //         ->where('booking_source_id', $addedId)
            //         ->first();

            //     if (!$existingRatePlanOTA) {
            //         try {
            //             // Tạo mới rate plan OTA
            //             $ratePlanOTA = $ratePlan->ratePlanOTAs()->create([
            //                 'booking_source_id' => $addedId,
            //             ]);

            //             // Tạo trên Channex
            //             $payload = app(\App\Http\Controllers\RatePlanController::class)->formatRatePlanPayload($ratePlan, $ratePlan->price, $bookingSource);
            //             $response = $channexService->createRatePlan($payload);

            //             // Lưu external_id
            //             $ratePlanOTA->update(['external_id' => $response['id']]);

            //             // Tạo occupancy options
            //             foreach ($response['attributes']['options'] as $option) {
            //                 $ratePlanOTA->occupancyOptions()->create([
            //                     'external_id' => $option['id'],
            //                     'occupancy' => $option['occupancy'],
            //                     'is_primary' => $option['is_primary'] ?? false,
            //                     'rate' => $option['rate'],
            //                 ]);
            //             }
            //         } catch (\Exception $e) {
            //             Log::error('Failed to create rate plan on Channex for new booking source', [
            //                 'rate_plan_id' => $ratePlan->id,
            //                 'booking_source_id' => $addedId,
            //                 'error' => $e->getMessage()
            //             ]);
            //         }
            //     }
            // }
        }
    }

    public function destroy(Property $property, ChannexService $channexService)
    {
        DB::beginTransaction();
        try {
            try {
                $property->delete();
            } catch (\Throwable $th) {
                throw new \Exception('Không thể xoá chỗ nghỉ. Vui lòng thử lại sau!');
            }
            if ($property->is_sync_enabled && $property->external_id) {
                try {
                    $channexService->deleteProperty($property->external_id);
                } catch (\Throwable $th) {
                    throw new \Exception('Không thể xoá chỗ nghỉ trên Channex');
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            logger($e);
            DB::rollBack();
            Log::error("Failed to delete Channex property", ['message' => $e->getMessage()]);
            return back()->with('error', $e->getMessage());
        }
        return back()->with('deleted', 'Chỗ nghỉ đã được xóa!');
    }
}
