<?php

namespace App\Http\Controllers;

use App\Helpers\MoneyHelper;
use App\Helpers\RoleHelper;
use App\Models\Inventory;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RateplanHistory;
use App\Models\Room;
use App\Services\ChannexService;
use App\Services\ChannexRateLimiter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Inventory::class, 'inventory');
    }
    public function index(Request $request)
    {
        $filters = $request->validate([
            'property_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
        ]);

        $partnerGroupId = RoleHelper::getScopedPartnerGroupId();

        $propertyOptions = Property::select('id', 'name')
            ->when($partnerGroupId, fn($q) => $q->where('partner_group_id', $partnerGroupId))
            ->get();

        if ($propertyOptions->isEmpty()) {
            return Inertia::render('Inventory/Index', [
                'propertyOptions' => [],
                'filters' => $filters,
                'dates' => [],
                'roomTypes' => [],
                'inventoryGrid' => [],
                'currency' => 'VND',
            ]);
        }

        $propertyId = $filters['property_id'] ?? $propertyOptions->first()->id;
        $filters['property_id'] = $propertyId;

        $property = Property::find($propertyId);
        $currency = $property->currency ?? 'VND';

        $startDate = Carbon::parse($filters['start_date'] ?? now());
        $numberOfDays = 14;
        $maxEndDate = now()->clone()->addDays(499); // 500 days from today
        $endDate = $startDate->clone()->addDays($numberOfDays - 1);
        if ($endDate->gt($maxEndDate)) {
            $endDate = $maxEndDate;
        }

        $dates = collect(Carbon::parse($startDate)->toPeriod($endDate))->map(fn(Carbon $date) => [
            'dayOfWeek' => $date->format('D'),
            'dayOfMonth' => $date->format('d'),
            'fullDate' => $date->format('Y-m-d'),
        ]);

        $roomTypes = Room::where('property_id', $propertyId)
            ->withCount('roomUnits')
            ->with(['ratePlans.ratePlanOTAs.bookingSource', 'ratePlans.ratePlanOTAs.occupancyOptions'])
            ->get();

        $inventoryOverrides = Inventory::where('property_id', $propertyId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $inventoryData = [];
        foreach ($inventoryOverrides as $item) {
            $dateStr = $item->date->format('Y-m-d');

            if ($item->rate_plan_ota_id) {
                // OTA-specific rate
                if ($item->occupancy_option_id) {
                    // Per person mode - include occupancy_option_id in key
                    $key = 'ota_' . $item->rate_plan_ota_id . '_occupancy_option_' . $item->occupancy_option_id;
                } else {
                    // Per room mode - existing logic
                    $key = 'ota_' . $item->rate_plan_ota_id;
                }
            } elseif ($item->rate_plan_id) {
                // Local rate plan rate
                $key = 'local_' . $item->rate_plan_id;
            } else {
                // Availability
                $key = 'avl_' . $item->room_type_id;
            }

            $inventoryData[$dateStr][$key] = $item;
        }

        $inventoryGrid = [];
        foreach ($dates as $date) {
            $fullDate = $date['fullDate'];
            $inventoryGrid[$fullDate] = [];

            foreach ($roomTypes as $roomType) {
                $inventoryGrid[$fullDate][$roomType->id] = [];

                // Availability
                $avlKey = 'avl_' . $roomType->id;
                $avlRecord = $inventoryData[$fullDate][$avlKey] ?? null;
                $inventoryGrid[$fullDate][$roomType->id]['avl'] = $avlRecord ? (int) $avlRecord->availability : $roomType->room_units_count;

                foreach ($roomType->ratePlans as $ratePlan) {
                    $inventoryGrid[$fullDate][$roomType->id][$ratePlan->id] = [];

                    // Local rate (webapp rate) - sử dụng rateplan_history nếu cần
                    $localKey = 'local_' . $ratePlan->id;
                    $localRecord = $inventoryData[$fullDate][$localKey] ?? null;

                    if ($localRecord) {
                        $localRate = (float) $localRecord->rate;
                        $isOverride = true;
                    } else {
                        // Lấy từ rateplan_history nếu có, nếu không thì từ ratePlan hiện tại
                        $localRate = $this->getRatePlanValue($ratePlan, 'price', $fullDate, $ratePlan->price);
                        $isOverride = false;
                    }

                    $inventoryGrid[$fullDate][$roomType->id][$ratePlan->id]['local'] = [
                        'title' => $ratePlan->title,
                        'price' => $localRate,
                        'is_override' => $isOverride,
                    ];

                    // OTA rates for each booking source
                    foreach ($ratePlan->ratePlanOTAs as $ratePlanOTA) {
                        $bookingSource = $ratePlanOTA->bookingSource;

                        if ($ratePlan->sell_mode === 'per_person' && $ratePlanOTA->occupancyOptions->isNotEmpty()) {
                            // Per person mode - hiển thị từng occupancy option

                            // Tìm primary occupancy rate đã update (nếu có)
                            $primaryOccupancyOption = $ratePlanOTA->occupancyOptions->where('is_primary', true)->first();
                            $updatedPrimaryRate = null;

                            if ($primaryOccupancyOption) {
                                $primaryOtaKey = 'ota_' . $ratePlanOTA->id . '_occupancy_option_' . $primaryOccupancyOption->id;
                                $primaryOtaRecord = $inventoryData[$fullDate][$primaryOtaKey] ?? null;

                                // Nếu không tìm thấy với occupancy_option_id, thử tìm với rate_plan_ota_id
                                if (!$primaryOtaRecord) {
                                    $primaryOtaKey = 'ota_' . $ratePlanOTA->id;
                                    $primaryOtaRecord = $inventoryData[$fullDate][$primaryOtaKey] ?? null;
                                }

                                if ($primaryOtaRecord && $primaryOtaRecord->rate) {
                                    $updatedPrimaryRate = (float) $primaryOtaRecord->rate;
                                }
                            }

                            foreach ($ratePlanOTA->occupancyOptions as $occupancyOption) {
                                $occupancy = $occupancyOption->occupancy;
                                $isPrimary = $occupancyOption->is_primary;

                                // Check for OTA-specific override for this occupancy option
                                $otaKey = 'ota_' . $ratePlanOTA->id . '_occupancy_option_' . $occupancyOption->id;
                                $otaRecord = $inventoryData[$fullDate][$otaKey] ?? null;

                                // Nếu là primary occupancy và không tìm thấy record cụ thể, 
                                // thử tìm record với occupancy_option_id = NULL (restrictions cho primary)
                                if (!$otaRecord && $isPrimary) {
                                    $otaKey = 'ota_' . $ratePlanOTA->id;
                                    $otaRecord = $inventoryData[$fullDate][$otaKey] ?? null;
                                }

                                if ($otaRecord && $otaRecord->rate) {
                                    $otaRate = (float) $otaRecord->rate;
                                    $isOverride = true;
                                } else {
                                    // Calculate rate based on mode
                                    if ($ratePlan->rate_mode === 'auto') {
                                        // Với auto mode, cần lấy giá primary occupancy theo thứ tự ưu tiên:
                                        // 1. Inventory override (nếu có)
                                        // 2. Rateplan_history (nếu có)
                                        // 3. Rateplan gốc (fallback)

                                        $primaryRate = null;

                                        // 1. Kiểm tra inventory override trước
                                        if ($updatedPrimaryRate !== null) {
                                            $primaryRate = $updatedPrimaryRate;
                                        } else {
                                            // 2. Lấy từ rateplan_history nếu có
                                            $defaultValues = RateplanHistory::getDefaultValuesForDate($ratePlan->id, $fullDate);
                                            if ($defaultValues && $defaultValues->rate !== null) {
                                                $primaryRate = (float) $defaultValues->rate;
                                            } else {
                                                // 3. Fallback về rateplan gốc
                                                $primaryRate = (float) $ratePlan->price;
                                            }
                                        }

                                        // Tính toán giá cho occupancy này dựa trên primary rate
                                        $otaRate = $this->calculateAutoModeRate($ratePlan, $occupancyOption, $bookingSource, $primaryRate);
                                    } else {
                                        // Với manual mode, lấy từ rateplan_history nếu có
                                        $defaultValues = RateplanHistory::getDefaultValuesForDate($ratePlan->id, $fullDate);

                                        if ($defaultValues && $defaultValues->occupancy_options) {
                                            $historyOption = collect($defaultValues->occupancy_options)->firstWhere('occupancy', $occupancy);
                                            $otaRate = $historyOption ? (float) $historyOption['rate'] : (float) $occupancyOption->rate;
                                        } else {
                                            $otaRate = (float) $occupancyOption->rate;
                                        }
                                    }
                                    $isOverride = false;
                                }

                                // Xác định thứ trong tuần (0 = CN, 1 = T2, ..., 6 = T7)
                                $carbonDate = \Carbon\Carbon::parse($fullDate);
                                $weekday = $carbonDate->dayOfWeek; // 0 (CN) -> 6 (T7)
                                // Map về index mảng: 0 (T2) -> 6 (CN)
                                $weeklyIndex = $weekday === 0 ? 6 : $weekday - 1;

                                $inventoryGrid[$fullDate][$roomType->id][$ratePlan->id]['otas'][$bookingSource->id . '_occupancy_' . $occupancy] = [
                                    'rate_plan_ota_id' => $ratePlanOTA->id,
                                    'occupancy_option_id' => $occupancyOption->id,
                                    'external_id' => $occupancyOption->external_id, // Add external_id for Channex updates
                                    'booking_source_id' => $bookingSource->id,
                                    'booking_source_name' => $bookingSource->name,
                                    'occupancy' => $occupancy,
                                    'is_primary' => $isPrimary,
                                    'rate' => $otaRate,
                                    'is_override' => $isOverride,
                                    // Edit permissions
                                    'can_edit_rate' => $ratePlan->rate_mode === 'manual', // Only manual mode allows rate editing
                                    'can_edit_restrictions' => $isPrimary && $ratePlan->rate_mode === 'manual', // Only primary occupancy in manual mode
                                    'min_stay_arrival' => $otaRecord !== null && $otaRecord->min_stay_arrival !== null
                                        ? (int) $otaRecord->min_stay_arrival
                                        : (int) $this->getWeeklyValue($ratePlan, 'min_stay_arrival', $fullDate, $weeklyIndex, 1),
                                    'min_stay_through' => $otaRecord !== null && $otaRecord->min_stay_through !== null
                                        ? (int) $otaRecord->min_stay_through
                                        : (int) $this->getWeeklyValue($ratePlan, 'min_stay_through', $fullDate, $weeklyIndex, 1),
                                    'max_stay' => $otaRecord !== null && $otaRecord->max_stay !== null
                                        ? (int) $otaRecord->max_stay
                                        : (int) $this->getWeeklyValue($ratePlan, 'max_stay', $fullDate, $weeklyIndex, 0),
                                    'closed_to_arrival' => $otaRecord !== null && $otaRecord->closed_to_arrival !== null
                                        ? (bool) $otaRecord->closed_to_arrival
                                        : (bool) $this->getWeeklyValue($ratePlan, 'closed_to_arrival', $fullDate, $weeklyIndex, false),
                                    'closed_to_departure' => $otaRecord !== null && $otaRecord->closed_to_departure !== null
                                        ? (bool) $otaRecord->closed_to_departure
                                        : (bool) $this->getWeeklyValue($ratePlan, 'closed_to_departure', $fullDate, $weeklyIndex, false),
                                    'stop_sell' => $otaRecord !== null && $otaRecord->stop_sell !== null
                                        ? (bool) $otaRecord->stop_sell
                                        : (bool) $this->getWeeklyValue($ratePlan, 'stop_sell', $fullDate, $weeklyIndex, false),
                                ];
                            }
                        } else {
                            // Per room mode - hiển thị như cũ
                            $primaryOccupancyOption = $ratePlanOTA->occupancyOptions->where('is_primary', true)->first();

                            // Xác định thứ trong tuần (0 = CN, 1 = T2, ..., 6 = T7)
                            $carbonDate = \Carbon\Carbon::parse($fullDate);
                            $weekday = $carbonDate->dayOfWeek; // 0 (CN) -> 6 (T7)
                            // Map về index mảng: 0 (T2) -> 6 (CN)
                            $weeklyIndex = $weekday === 0 ? 6 : $weekday - 1;

                            // Check for OTA-specific override
                            $otaKey = 'ota_' . $ratePlanOTA->id;
                            $otaRecord = $inventoryData[$fullDate][$otaKey] ?? null;

                            if ($otaRecord && $otaRecord->rate) {
                                $otaRate = (float) $otaRecord->rate;
                                $isOverride = true;
                            } else {
                                // Lấy giá từ rateplan_history nếu có, nếu không thì tính toán từ ratePlan hiện tại
                                $basePrice = $this->getRatePlanValue($ratePlan, 'price', $fullDate, $ratePlan->price);
                                $otaRate = round($basePrice * (1 + ($bookingSource->price_percentage / 100)));
                                $isOverride = false;
                            }

                            $inventoryGrid[$fullDate][$roomType->id][$ratePlan->id]['otas'][$bookingSource->id] = [
                                'rate_plan_ota_id' => $ratePlanOTA->id,
                                'occupancy_option_id' => $primaryOccupancyOption->id,
                                'external_id' => $ratePlanOTA->external_id, // Add external_id for Channex updates
                                'booking_source_id' => $bookingSource->id,
                                'booking_source_name' => $bookingSource->name,
                                'price_percentage' => $bookingSource->price_percentage,
                                'rate' => $otaRate,
                                'is_override' => $isOverride,
                                'primary_occupancy_rate' => $primaryOccupancyOption ? (float) $primaryOccupancyOption->rate : $otaRate,
                                // Edit permissions - per room mode allows all edits
                                'can_edit_rate' => true,
                                'can_edit_restrictions' => true,
                                'min_stay_arrival' => $otaRecord ? (int) $otaRecord->min_stay_arrival : (int) $this->getWeeklyValue($ratePlan, 'min_stay_arrival', $fullDate, $weeklyIndex, 1),
                                'min_stay_through' => $otaRecord ? (int) $otaRecord->min_stay_through : (int) $this->getWeeklyValue($ratePlan, 'min_stay_through', $fullDate, $weeklyIndex, 1),
                                'max_stay' => $otaRecord ? (int) $otaRecord->max_stay : (int) $this->getWeeklyValue($ratePlan, 'max_stay', $fullDate, $weeklyIndex, 0),
                                'closed_to_arrival' => $otaRecord ? (bool) $otaRecord->closed_to_arrival : (bool) $this->getWeeklyValue($ratePlan, 'closed_to_arrival', $fullDate, $weeklyIndex, false),
                                'closed_to_departure' => $otaRecord ? (bool) $otaRecord->closed_to_departure : (bool) $this->getWeeklyValue($ratePlan, 'closed_to_departure', $fullDate, $weeklyIndex, false),
                                'stop_sell' => $otaRecord ? (bool) $otaRecord->stop_sell : (bool) $this->getWeeklyValue($ratePlan, 'stop_sell', $fullDate, $weeklyIndex, false),
                            ];
                        }
                    }
                }
            }
        }

        return Inertia::render('Inventory/Index', [
            'propertyOptions' => $propertyOptions,
            'filters' => $filters,
            'dates' => $dates,
            'roomTypes' => $roomTypes,
            'inventoryGrid' => $inventoryGrid,
            'currency' => $currency,
        ]);
    }

    public function store(Request $request, ChannexService $channexService)
    {
        $validated = $request->validate([
            'property_id' => 'required|integer|exists:properties,id',
            'room_type_id' => 'required|integer|exists:rooms,id',
            'rate_plan_id' => 'nullable|integer|exists:rate_plans,id',
            'rate_plan_ota_id' => 'nullable|integer|exists:rate_plan_booking_source,id',
            'occupancy_option_id' => 'nullable|integer|exists:occupancy_options,id', // New field for per_person mode
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => ['required', 'string', Rule::in([
                'avl',
                'rate',
                'min_stay_arrival',
                'min_stay_through',
                'max_stay',
                'closed_to_arrival',
                'closed_to_departure',
                'stop_sell'
            ])],
            'value' => 'required|numeric',
        ]);

        $property = Property::findOrFail($validated['property_id']);

        // Check if property should sync with Channex
        $shouldSyncWithChannex = $property->external_id && $property->is_sync_enabled;

        // Validate editing permissions for per_person mode
        if ($validated['occupancy_option_id']) {
            $ratePlan = RatePlan::findOrFail($validated['rate_plan_id']);
            $occupancyOption = \App\Models\OccupancyOption::findOrFail($validated['occupancy_option_id']);

            // Check if non-primary occupancy is trying to edit restrictions in manual mode
            if (!$occupancyOption->is_primary && $ratePlan->rate_mode === 'manual') {
                if (in_array($validated['type'], ['min_stay_arrival', 'min_stay_through', 'max_stay', 'closed_to_arrival', 'closed_to_departure', 'stop_sell'])) {
                    return back()->with('error', 'Chỉ có thể chỉnh sửa restrictions cho occupancy chính trong chế độ manual.');
                }
            }

            // Check if trying to edit anything in auto mode
            if ($ratePlan->rate_mode === 'auto') {
                return back()->with('error', 'Không thể chỉnh sửa inventory trong chế độ auto.');
            }
        }

        $dates = Carbon::parse($validated['start_date'])->toPeriod($validated['end_date']);
        $channexUpdates = [];

        foreach ($dates as $date) {
            $dateString = $date->format('Y-m-d');
            $isRateUpdate = $validated['type'] === 'rate';
            $isRestrictionUpdate = in_array($validated['type'], [
                'min_stay_arrival',
                'min_stay_through',
                'max_stay',
                'closed_to_arrival',
                'closed_to_departure',
                'stop_sell'
            ]);

            $updateData = [];

            if ($isRateUpdate) {
                $updateData['rate'] = $validated['value'];
            } elseif ($isRestrictionUpdate) {
                $updateData[$validated['type']] = $validated['value'];
            } else {
                $updateData['availability'] = $validated['value'];
            }

            // Add occupancy_option_id to update data if provided
            if ($validated['occupancy_option_id']) {
                $updateData['occupancy_option_id'] = $validated['occupancy_option_id'];
            }

            // 1. Update local database
            $uniqueConstraints = [
                'property_id' => $validated['property_id'],
                'date' => $dateString,
                'room_type_id' => $validated['room_type_id'],
                'rate_plan_id' => ($isRateUpdate || $isRestrictionUpdate) ? $validated['rate_plan_id'] : null,
                'rate_plan_ota_id' => ($isRateUpdate || $isRestrictionUpdate) ? $validated['rate_plan_ota_id'] : null,
            ];

            // Add occupancy_option_id to unique constraints if provided
            if ($validated['occupancy_option_id']) {
                $uniqueConstraints['occupancy_option_id'] = $validated['occupancy_option_id'];
            }

            Inventory::updateOrCreate($uniqueConstraints, $updateData);

            // 2. Prepare Channex payload only if sync is enabled and it's an OTA update
            if ($shouldSyncWithChannex && ($isRateUpdate || $isRestrictionUpdate) && $validated['rate_plan_ota_id']) {
                $ratePlanOTA = \App\Models\RatePlanOTA::findOrFail($validated['rate_plan_ota_id']);

                if ($isRestrictionUpdate) {
                    // For restrictions: use RatePlanOTA's external_id (or primary occupancy option's external_id)
                    if ($validated['occupancy_option_id']) {
                        $occupancyOption = \App\Models\OccupancyOption::findOrFail($validated['occupancy_option_id']);
                        // Only allow restrictions for primary occupancy
                        if (!$occupancyOption->is_primary) {
                            return back()->with('error', 'Chỉ có thể chỉnh sửa restrictions cho occupancy chính.');
                        }
                        $ratePlanId = $occupancyOption->external_id;
                    } else {
                        // Fallback to RatePlanOTA's external_id for restrictions
                        $ratePlanId = $ratePlanOTA->external_id;
                    }
                } else {
                    // For rate updates: use occupancy option's external_id
                    if ($validated['occupancy_option_id']) {
                        $occupancyOption = \App\Models\OccupancyOption::findOrFail($validated['occupancy_option_id']);
                        $ratePlanId = $occupancyOption->external_id;
                    } else {
                        // Fallback to RatePlanOTA's external_id for rate updates
                        $ratePlanId = $ratePlanOTA->external_id;
                    }
                }

                $channexUpdate = [
                    'property_id' => $property->external_id,
                    'rate_plan_id' => $ratePlanId,
                    'date' => $dateString,
                ];

                if ($isRateUpdate) {
                    $channexUpdate['rate'] = (int)$validated['value'];
                } else {
                    // Handle boolean fields differently
                    if (in_array($validated['type'], ['closed_to_arrival', 'closed_to_departure', 'stop_sell'])) {
                        $channexUpdate[$validated['type']] = (bool)$validated['value'];
                    } else {
                        $channexUpdate[$validated['type']] = (int)$validated['value'];
                    }
                }

                $channexUpdates[] = $channexUpdate;
            }
        }

        // 3. Push to Channex only if sync is enabled
        if ($shouldSyncWithChannex && !empty($channexUpdates)) {
            try {
                $channexService->updateRestrictions($channexUpdates);
            } catch (\Exception $e) {
                Log::error("Failed to push updates to Channex: " . $e->getMessage());
                // Even if Channex fails, we've saved locally. Redirect back with error.
                return redirect()->back()->with('error', 'Đã lưu cục bộ, nhưng không thể đồng bộ với Channex');
            }
        }

        $message = $shouldSyncWithChannex ? 'Cập nhật tồn kho thành công.' : 'Cập nhật tồn kho cục bộ thành công';
        return redirect()->back()->with('success', $message);
    }

    public function storeMultiple(Request $request, ChannexService $channexService)
    {
        $validated = $request->validate([
            'property_id' => 'required|integer|exists:properties,id',
            'changes' => 'required|array',
            'changes.*.date' => 'required|date',
            'changes.*.room_type_id' => 'required|integer|exists:rooms,id',
            'changes.*.rate_plan_id' => 'nullable|integer|exists:rate_plans,id',
            'changes.*.rate_plan_ota_id' => 'nullable|integer|exists:rate_plan_booking_source,id',
            'changes.*.occupancy_option_id' => 'nullable|integer|exists:occupancy_options,id', // Thêm validation
            'changes.*.type' => ['required', 'string', Rule::in([
                'avl',
                'rate',
                'min_stay_arrival',
                'min_stay_through',
                'max_stay',
                'closed_to_arrival',
                'closed_to_departure',
                'stop_sell'
            ])],
            'changes.*.value' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    // Get the index of the current change
                    preg_match('/changes\.(\d+)\.value/', $attribute, $matches);
                    $index = $matches[1] ?? null;
                    $type = $index !== null ? $request->input("changes.$index.type") : null;
                    if (in_array($type, ['closed_to_arrival', 'closed_to_departure', 'stop_sell'])) {
                        if (!is_bool($value) && !in_array($value, [0, 1, '0', '1'], true)) {
                            $fail('Giá trị phải là boolean cho các trường đóng/mở.');
                        }
                    } else {
                        if (!is_numeric($value)) {
                            $fail('Giá trị phải là số.');
                        }
                    }
                }
            ],
        ]);

        $property = Property::findOrFail($validated['property_id']);
        $currency = strtoupper($property->currency ?? 'VND');

        // Check if property should sync with Channex
        $shouldSyncWithChannex = $property->external_id && $property->is_sync_enabled;

        $otaRateUpdates = [];
        $otaRestrictionUpdates = [];
        $availabilityUpdates = [];

        // Separate changes and create payloads only if sync is enabled
        if ($shouldSyncWithChannex) {
            foreach ($validated['changes'] as $change) {
                if ($change['type'] === 'rate' && !empty($change['rate_plan_ota_id'])) {
                    $otaRateUpdates[] = $change;
                } elseif ($change['type'] === 'avl') {
                    $availabilityUpdates[] = $change;
                } elseif (in_array($change['type'], [
                    'min_stay_arrival',
                    'min_stay_through',
                    'max_stay',
                    'closed_to_arrival',
                    'closed_to_departure',
                    'stop_sell'
                ]) && !empty($change['rate_plan_ota_id'])) {
                    $otaRestrictionUpdates[] = $change;
                }
                // Local rate changes are not synced to Channex
            }
        }

        $localRooms = null;
        $localRatePlanOTAs = null;
        $localOccupancyOptions = null;

        // Get local data only if sync is enabled
        if ($shouldSyncWithChannex) {
            $localRooms = Room::whereIn('id', array_column($availabilityUpdates, 'room_type_id'))->get()->keyBy('id');
            $localRatePlanOTAs = \App\Models\RatePlanOTA::whereIn('id', array_column(array_merge($otaRateUpdates, $otaRestrictionUpdates), 'rate_plan_ota_id'))->get()->keyBy('id');
            // Lấy occupancy options cho rate updates
            $occupancyOptionIds = array_filter(array_column($otaRateUpdates, 'occupancy_option_id'));
            if (!empty($occupancyOptionIds)) {
                $localOccupancyOptions = \App\Models\OccupancyOption::whereIn('id', $occupancyOptionIds)->get()->keyBy('id');
            }
        }

        // Process all local DB updates in one transaction
        DB::transaction(function () use ($validated, $currency) {
            foreach ($validated['changes'] as $change) {
                $isRateUpdate = $change['type'] === 'rate';
                $isRestrictionUpdate = in_array($change['type'], [
                    'min_stay_arrival',
                    'min_stay_through',
                    'max_stay',
                    'closed_to_arrival',
                    'closed_to_departure',
                    'stop_sell'
                ]);

                $updateData = [];
                if ($isRateUpdate) {
                    $updateData['rate'] = MoneyHelper::formatCurrency($change['value'], $currency);
                } elseif ($isRestrictionUpdate) {
                    $updateData[$change['type']] = $change['value'];
                } else {
                    $updateData['availability'] = $change['value'];
                }

                // Thêm occupancy_option_id vào updateData nếu có
                if (!empty($change['occupancy_option_id'])) {
                    $updateData['occupancy_option_id'] = $change['occupancy_option_id'];
                }

                Inventory::updateOrCreate(
                    [
                        'property_id' => $validated['property_id'],
                        'date' => $change['date'],
                        'room_type_id' => $change['room_type_id'],
                        'rate_plan_id' => ($isRateUpdate || $isRestrictionUpdate) ? $change['rate_plan_id'] : null,
                        'rate_plan_ota_id' => ($isRateUpdate || $isRestrictionUpdate) ? ($change['rate_plan_ota_id'] ?? null) : null,
                        'occupancy_option_id' => ($isRateUpdate || $isRestrictionUpdate) ? ($change['occupancy_option_id'] ?? null) : null, // Thêm vào unique key
                    ],
                    $updateData
                );
            }
        });

        // Push to Channex only if sync is enabled
        if ($shouldSyncWithChannex) {
            try {
                // Push Availability Updates
                if (!empty($availabilityUpdates)) {
                    $avlPayload = [];
                    foreach ($availabilityUpdates as $update) {
                        $room = $localRooms[$update['room_type_id']] ?? null;
                        if ($room && $room->external_id) {
                            $avlPayload[] = [
                                'property_id' => $property->external_id,
                                'room_type_id' => $room->external_id,
                                'date' => $update['date'],
                                'availability' => (int)$update['value'],
                            ];
                        }
                    }
                    if (!empty($avlPayload)) {
                        $channexService->updateAvailability($avlPayload);
                    }
                }

                $otaPayload = [];

                // rate updates - sử dụng external_id của occupancy_option_id
                foreach ($otaRateUpdates as $update) {
                    $occupancyOptionId = $update['occupancy_option_id'] ?? null;
                    if (!$occupancyOptionId) {
                        $primaryRatePlanOTA = $localRatePlanOTAs[$update['rate_plan_ota_id']] ?? null;
                        $rateplanId = $primaryRatePlanOTA->external_id;
                    } else {
                        $occupancyOption = $localOccupancyOptions[$occupancyOptionId] ?? null;
                        $rateplanId = $occupancyOption->external_id;
                    }

                    $otaPayload[] = [
                        'property_id' => $property->external_id,
                        'rate_plan_id' => $rateplanId, // Sử dụng external_id của occupancy_option
                        'date' => $update['date'],
                        'rate' => MoneyHelper::formatCurrency($update['value'], $currency),
                    ];
                }

                // restriction updates - vẫn sử dụng external_id của rate_plan_ota_id (vì restrictions chỉ áp dụng cho primary)
                foreach ($otaRestrictionUpdates as $update) {
                    $ratePlanOTA = $localRatePlanOTAs[$update['rate_plan_ota_id']] ?? null;
                    if ($ratePlanOTA && $ratePlanOTA->external_id) {
                        $payloadItem = [
                            'property_id' => $property->external_id,
                            'rate_plan_id' => $ratePlanOTA->external_id,
                            'date' => $update['date'],
                        ];

                        // Handle boolean fields differently
                        if (in_array($update['type'], ['closed_to_arrival', 'closed_to_departure', 'stop_sell'])) {
                            $payloadItem[$update['type']] = (bool) $update['value'];
                        } else {
                            $payloadItem[$update['type']] = (int) $update['value'];
                        }

                        $otaPayload[] = $payloadItem;
                    }
                }

                if (!empty($otaPayload)) {
                    $channexService->updateRestrictions($otaPayload);
                }
            } catch (\Exception $e) {
                logger($e);
                Log::error("Failed to push updates to Channex: " . $e->getMessage());
                return back()->with('error', 'Có lỗi xảy ra khi đồng bộ với channex');
            }
        }

        $message = $shouldSyncWithChannex ? 'Thay đổi đã được cập nhật thành công' : 'Thay đổi đã được cập nhật cục bộ thành công';
        return back()->with('success', $message);
    }

    public function updateBulk(Request $request, ChannexService $channexService)
    {
        $validated = $request->validate([
            'property_id' => 'required|integer|exists:properties,id',
            'dateRanges' => 'required|array',
            'changes' => 'required|array',
            'applyTo' => 'required|array',
        ]);

        $property = Property::findOrFail($validated['property_id']);
        $shouldSyncWithChannex = $property->external_id && $property->is_sync_enabled;

        $otaPayload = [];

        foreach ($validated['applyTo'] as $applyKey) {
            // Parse key: roomTypeId_ratePlanId_ratePlanOtaId
            [$roomTypeId, $ratePlanId, $ratePlanOtaId] = explode('_', $applyKey);

            foreach ($validated['dateRanges'] as $range) {
                $start = Carbon::parse($range['start']);
                $end = Carbon::parse($range['end']);
                $weekdays = $range['weekdays'] ?? [0, 1, 2, 3, 4, 5, 6];

                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    // Lọc theo thứ trong tuần
                    if (!in_array($date->dayOfWeekIso % 7, $weekdays)) continue;

                    $inventory = Inventory::firstOrNew([
                        'property_id' => $property->id,
                        'room_type_id' => $roomTypeId,
                        'rate_plan_id' => $ratePlanId,
                        'rate_plan_ota_id' => $ratePlanOtaId === 'local' ? null : $ratePlanOtaId,
                        'date' => $date->format('Y-m-d'),
                    ]);

                    // Xử lý từng trường thay đổi
                    foreach ($validated['changes'] as $field => $value) {
                        if ($field === 'rate') {
                            $rateType = $validated['changes']['rateType'] ?? 'exact';
                            $baseRate = $inventory->rate ?? RatePlan::find($ratePlanId)->price ?? 0;

                            switch ($rateType) {
                                case 'exact':
                                    $inventory->rate = $value;
                                    break;
                                case 'increase_amount':
                                    $inventory->rate = $baseRate + $value;
                                    break;
                                case 'decrease_amount':
                                    $inventory->rate = $baseRate - $value;
                                    break;
                                case 'increase_percent':
                                    $inventory->rate = $baseRate * (1 + $value / 100);
                                    break;
                                case 'decrease_percent':
                                    $inventory->rate = $baseRate * (1 - $value / 100);
                                    break;
                            }
                        } elseif (in_array($field, [
                            'min_stay_arrival',
                            'min_stay_through',
                            'max_stay',
                            'closed_to_arrival',
                            'closed_to_departure',
                            'stop_sell'
                        ])) {
                            $inventory->$field = $value;
                        }
                    }

                    $inventory->save();

                    // Nếu là OTA, chuẩn bị payload cho Channex
                    if ($shouldSyncWithChannex && $ratePlanOtaId !== 'local') {
                        $ratePlanOTA = \App\Models\RatePlanOTA::find($ratePlanOtaId);
                        if ($ratePlanOTA && $ratePlanOTA->external_id) {
                            $payloadItem = [
                                'property_id' => $property->external_id,
                                'rate_plan_id' => $ratePlanOTA->external_id,
                                'date' => $date->format('Y-m-d'),
                            ];
                            // Thêm các trường thay đổi
                            foreach ($validated['changes'] as $field => $value) {
                                if ($field === 'rate') {
                                    $payloadItem['rate'] = $inventory->rate;
                                } else {
                                    $payloadItem[$field] = $value;
                                }
                            }
                            $otaPayload[] = $payloadItem;
                        }
                    }
                }
            }
        }

        // Gửi lên Channex
        if ($shouldSyncWithChannex && !empty($otaPayload)) {
            logger($otaPayload);
            $channexService->updateRestrictions($otaPayload);
        }

        return back()->with('success', 'Thông tin cài đặt đã được cập nhật');
    }

    public function fullSync(Request $request, ChannexService $channexService)
    {
        $validated = $request->validate([
            'property_id' => 'required|integer|exists:properties,id',
        ]);

        $property = Property::findOrFail($validated['property_id']);

        // Check if property should sync with Channex
        if (!$property->external_id || !$property->is_sync_enabled) {
            return back()->with('error', 'Property không được đồng bộ với Channex');
        }

        try {
            // Calculate date range: 500 days from today
            $startDate = now()->format('Y-m-d');
            $endDate = now()->addDays(499)->format('Y-m-d'); // 500 days total

            Log::info("Starting full sync for property {$property->name}", [
                'property_id' => $property->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            // Check rate limits before starting
            $rateLimiter = app(ChannexRateLimiter::class);
            $usageStats = $rateLimiter->getUsageStats($property->external_id);

            Log::info("Rate limit stats before sync", $usageStats);

            // 1. Sync Availability for all rooms (500 days)
            $this->syncAvailabilityForAllRooms($property, $startDate, $endDate, $channexService);

            // 2. Sync Rates & Restrictions for all rate plans (500 days)
            $this->syncRatesAndRestrictionsForAllRatePlans($property, $startDate, $endDate, $channexService);

            Log::info("Full sync completed successfully for property {$property->name}");

            return back()->with('success', 'Full sync thành công cho 500 ngày');
        } catch (\Exception $e) {
            Log::error("Full sync failed for property {$property->name}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Full sync thất bại: ' . $e->getMessage());
        }
    }

    public function fullSyncAll(Request $request, ChannexService $channexService)
    {
        $properties = Property::whereNotNull('external_id')->get();

        $successCount = 0;
        $errorCount = 0;

        foreach ($properties as $property) {
            if (!$property->external_id || !$property->is_sync_enabled) {
                Log::warning("Property {$property->name} is not enabled for sync", [
                    'property_id' => $property->id,
                ]);
                $errorCount++;
                continue;
            }

            try {
                $startDate = now()->format('Y-m-d');
                $endDate = now()->addDays(499)->format('Y-m-d'); // 500 days total

                Log::info("Starting full sync for property {$property->name}", [
                    'property_id' => $property->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);

                // Check rate limits before starting
                $rateLimiter = app(ChannexRateLimiter::class);
                $usageStats = $rateLimiter->getUsageStats($property->external_id);

                Log::info("Rate limit stats before sync", $usageStats);

                // 1. Sync Availability
                $this->syncAvailabilityForAllRooms($property, $startDate, $endDate, $channexService);

                // 2. Sync Rates & Restrictions
                $this->syncRatesAndRestrictionsForAllRatePlans($property, $startDate, $endDate, $channexService);

                Log::info("Full sync completed successfully for property {$property->name}");
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Full sync failed for property {$property->name}", [
                    'property_id' => $property->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errorCount++;
                continue;
            }
        }

        // Log results
        if ($errorCount === 0) {
            logger("Full sync thành công cho $successCount property.");
        } else {
            logger("Đã full sync xong. Thành công: $successCount, Thất bại: $errorCount");
        }
    }

    /**
     * Sync availability for all rooms in the property for the given date range
     */
    private function syncAvailabilityForAllRooms(Property $property, string $startDate, string $endDate, ChannexService $channexService)
    {
        $rooms = Room::where('property_id', $property->id)
            ->whereNotNull('external_id')
            ->get();

        if ($rooms->isEmpty()) {
            Log::warning("No rooms with external_id found for property {$property->name}");
            return;
        }

        $availabilityPayload = [];
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);

        foreach ($rooms as $room) {
            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');

                // Get availability from inventory table or calculate from room units
                $inventory = Inventory::where([
                    'property_id' => $property->id,
                    'room_type_id' => $room->id,
                    'date' => $dateStr,
                ])
                    ->whereNull('rate_plan_id')
                    ->whereNull('rate_plan_ota_id')
                    ->first();

                $available = $inventory ? $inventory->availability : $room->roomUnits()->count();

                $availabilityPayload[] = [
                    'property_id' => $property->external_id,
                    'room_type_id' => $room->external_id,
                    'date' => $dateStr,
                    'availability' => $available,
                ];
            }
        }

        if (!empty($availabilityPayload)) {
            Log::info("Syncing availability for {$rooms->count()} rooms, {$period->count()} days");
            // dd($availabilityPayload);
            $channexService->updateAvailability($availabilityPayload);
        }
    }

    /**
     * Sync rates and restrictions for all rate plans in the property for the given date range
     */
    private function syncRatesAndRestrictionsForAllRatePlans(Property $property, string $startDate, string $endDate, ChannexService $channexService)
    {
        $ratePlanOTAs = \App\Models\RatePlanOTA::with(['ratePlan', 'bookingSource'])
            ->whereHas('ratePlan', function ($query) use ($property) {
                $query->where('property_id', $property->id);
            })
            ->whereNotNull('external_id')
            ->get();

        if ($ratePlanOTAs->isEmpty()) {
            Log::warning("No rate plan OTAs with external_id found for property {$property->name}");
            return;
        }

        $restrictionsPayload = [];
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);

        foreach ($ratePlanOTAs as $ratePlanOTA) {
            $ratePlan = $ratePlanOTA->ratePlan;

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');

                // Get rate and restrictions from inventory table
                $inventory = Inventory::where([
                    'property_id' => $property->id,
                    'room_type_id' => $ratePlan->room_id,
                    'rate_plan_id' => $ratePlan->id,
                    'rate_plan_ota_id' => $ratePlanOTA->id,
                    'date' => $dateStr,
                ])->first();

                $payloadItem = [
                    'property_id' => $property->external_id,
                    'rate_plan_id' => $ratePlanOTA->external_id,
                    'date' => $dateStr,
                ];

                // Rate
                if ($inventory && $inventory->rate !== null) {
                    $payloadItem['rate'] = $inventory->formatted_rate;
                } else {
                    $payloadItem['rate'] = $ratePlanOTA->formatted_base_price;
                }

                // Restrictions
                if ($inventory) {
                    $payloadItem['min_stay_arrival'] = $inventory->min_stay_arrival ?? 1;
                    $payloadItem['min_stay_through'] = $inventory->min_stay_through ?? 1;
                    $payloadItem['max_stay'] = $inventory->max_stay ?? 0;
                    $payloadItem['closed_to_arrival'] = $inventory->closed_to_arrival ?? false;
                    $payloadItem['closed_to_departure'] = $inventory->closed_to_departure ?? false;
                    $payloadItem['stop_sell'] = $inventory->stop_sell ?? false;
                } else {
                    // Default restrictions
                    $payloadItem['min_stay_arrival'] = 1;
                    $payloadItem['min_stay_through'] = 1;
                    $payloadItem['max_stay'] = 0;
                    $payloadItem['closed_to_arrival'] = false;
                    $payloadItem['closed_to_departure'] = false;
                    $payloadItem['stop_sell'] = false;
                }

                $restrictionsPayload[] = $payloadItem;
            }
        }

        if (!empty($restrictionsPayload)) {
            Log::info("Syncing rates and restrictions for {$ratePlanOTAs->count()} rate plan OTAs, {$period->count()} days");
            $channexService->updateRestrictions($restrictionsPayload);
        }
    }

    /**
     * Calculate rate for auto mode based on occupancy and auto_rate_settings
     */
    private function calculateAutoModeRate($ratePlan, $occupancyOption, $bookingSource, $updatedPrimaryRate = null)
    {
        $primaryOccupancy = $ratePlan->primary_occupancy ?? 1;
        $currentOccupancy = $occupancyOption->occupancy;

        // Determine base rate: use updated primary rate if available, otherwise use default
        if ($updatedPrimaryRate !== null) {
            $baseRateWithPercentage = $updatedPrimaryRate;
        } else {
            $baseRate = $ratePlan->price;
            $baseRateWithPercentage = round($baseRate * (1 + ($bookingSource->price_percentage / 100)));
        }

        // If this is the primary occupancy, return base rate
        if ($currentOccupancy === $primaryOccupancy) {
            return $baseRateWithPercentage;
        }

        // For non-primary occupancies, calculate based on auto_rate_settings
        $autoSettings = $ratePlan->auto_rate_settings ?? [];
        $difference = abs($currentOccupancy - $primaryOccupancy);

        if ($currentOccupancy < $primaryOccupancy) {
            // Decrease rate
            $decreaseMode = $autoSettings['decrease_mode'] ?? '$';
            $decreaseValue = (float)($autoSettings['decrease_value'] ?? 0);

            if ($decreaseMode === '%') {
                $decreaseAmount = $baseRateWithPercentage * ($decreaseValue / 100) * $difference;
            } else {
                $decreaseAmount = $decreaseValue * $difference;
            }

            return max(0, $baseRateWithPercentage - $decreaseAmount);
        } else {
            // Increase rate
            $increaseMode = $autoSettings['increase_mode'] ?? '$';
            $increaseValue = (float)($autoSettings['increase_value'] ?? 0);

            if ($increaseMode === '%') {
                $increaseAmount = $baseRateWithPercentage * ($increaseValue / 100) * $difference;
            } else {
                $increaseAmount = $increaseValue * $difference;
            }

            return $baseRateWithPercentage + $increaseAmount;
        }
    }

    /**
     * Lấy giá trị inventory với fallback về rateplan_history
     */
    private function getInventoryValueWithFallback($inventory, $field, $defaultValue = null)
    {
        // Nếu có giá trị trong inventory, trả về
        if ($inventory->$field !== null) {
            return $inventory->$field;
        }

        // Nếu không có, lấy từ rateplan_history
        $historyValue = RateplanHistory::getDefaultValueForDate(
            $inventory->rate_plan_id,
            $inventory->date,
            $field
        );

        return $historyValue ?? $defaultValue;
    }

    /**
     * Lấy occupancy_options cho inventory
     */
    private function getInventoryOccupancyOptions($inventory)
    {
        return RateplanHistory::getOccupancyOptionsForDate(
            $inventory->rate_plan_id,
            $inventory->date
        );
    }

    /**
     * Lấy giá cho một occupancy cụ thể
     */
    private function getOccupancyRate($inventory, $occupancy)
    {
        // Nếu có override trong inventory, trả về
        if ($inventory->rate !== null) {
            return $inventory->rate;
        }

        // Lấy từ rateplan_history
        return RateplanHistory::getOccupancyRateForDate(
            $inventory->rate_plan_id,
            $inventory->date,
            $occupancy
        );
    }

    /**
     * Lấy giá trị từ rateplan_history hoặc ratePlan hiện tại
     */
    private function getRatePlanValue($ratePlan, $field, $date, $defaultValue = null)
    {
        // Xử lý đặc biệt cho field 'price' - map sang 'rate' trong rateplan_history
        $historyField = $field === 'price' ? 'rate' : $field;

        // Lấy từ rateplan_history trước
        $historyValue = RateplanHistory::getDefaultValueForDate($ratePlan->id, $date, $historyField);

        if ($historyValue !== null) {
            return $historyValue;
        }

        // Fallback về ratePlan hiện tại
        return $ratePlan->$field ?? $defaultValue;
    }

    /**
     * Lấy giá trị weekly từ rateplan_history hoặc ratePlan hiện tại
     */
    private function getWeeklyValue($ratePlan, $field, $date, $weeklyIndex, $defaultValue = null)
    {
        // Lấy từ rateplan_history trước
        $historyValue = RateplanHistory::getDefaultValueForDate($ratePlan->id, $date, $field);

        if ($historyValue && is_array($historyValue) && array_key_exists($weeklyIndex, $historyValue)) {
            return $historyValue[$weeklyIndex];
        }

        // Fallback về ratePlan hiện tại
        if (isset($ratePlan->$field) && is_array($ratePlan->$field) && array_key_exists($weeklyIndex, $ratePlan->$field)) {
            return $ratePlan->$field[$weeklyIndex];
        }

        return $defaultValue;
    }

    /**
     * Lấy occupancy_options từ rateplan_history hoặc ratePlan hiện tại
     */
    private function getOccupancyOptions($ratePlan, $date)
    {
        // Lấy từ rateplan_history trước
        $historyOptions = RateplanHistory::getOccupancyOptionsForDate($ratePlan->id, $date);

        if ($historyOptions) {
            return $historyOptions;
        }

        // Fallback về occupancy_options từ RatePlanOTA (nếu có)
        $firstRatePlanOTA = $ratePlan->ratePlanOTAs->first();
        if ($firstRatePlanOTA && $firstRatePlanOTA->occupancyOptions->isNotEmpty()) {
            return $firstRatePlanOTA->occupancyOptions->map(function ($option) {
                return [
                    'occupancy' => $option->occupancy,
                    'rate' => $option->rate,
                    'is_primary' => $option->is_primary,
                ];
            })->toArray();
        }

        return null;
    }
}
