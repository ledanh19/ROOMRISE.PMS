<?php

namespace App\Http\Controllers;

use App\Helpers\MoneyHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RoleHelper;
use App\Http\Requests\RatePlanRequest;
use App\Http\Resources\RatePlanItemResource;
use App\Http\Resources\RatePlanResource;
use App\Models\BookingSource;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RatePlanOTA;
use App\Models\RateplanHistory;
use App\Models\Room;
use App\Services\ChannexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class RatePlanController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(RatePlan::class, 'rate_plan');
    }
    public function index(Request $request)
    {
        $search = $request->search;
        $paginate = is_numeric($request->paginate) && $request->paginate > 0 ? $request->paginate : 10;

        $partnerGroupId = RoleHelper::getScopedPartnerGroupId();

        $propertyOptions = Property::select('id', 'name')
            ->when($partnerGroupId, fn($q) => $q->where('partner_group_id', $partnerGroupId))
            ->get();

        $filters = $request->only(['search', 'paginate', 'property_id']);
        $propertyId = $filters['property_id'] ?? null;
        $filters['property_id'] = $propertyId;

        $data = Room::with(['ratePlans.ratePlanOTAs.bookingSource', 'property'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('ratePlans', fn($q) => $q->where('title', 'LIKE', "%{$search}%"));
            })
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->when($partnerGroupId, function ($query) use ($partnerGroupId) {
                $query->whereHas('property', fn($q) => $q->where('partner_group_id', $partnerGroupId));
            })
            ->paginate($paginate)
            ->appends($filters);

        // Get booking sources for the form
        $bookingSources = collect();
        if ($propertyId) {
            $property = Property::with('bookingSources')->find($propertyId);
            if ($property) {
                $bookingSources = $property->bookingSources->map(function ($source) {
                    return [
                        'id' => $source->id,
                        'name' => $source->name,
                    ];
                });
            }
        }

        return Inertia::render('RatePlans/Index', [
            'data' => ResponseHelper::dataTable($data, RatePlanResource::class),
            'propertyOptions' => $propertyOptions,
            'bookingSources' => $bookingSources,
            'filters' => $filters,
        ]);
    }

    public function options(Request $request)
    {
        $roomId = $request->room;
        $ratePlans = RatePlan::where('room_id', $roomId)->get();
        return response()->json(RatePlanItemResource::collection($ratePlans));
    }

    public function store(RatePlanRequest $request)
    {
        $validated = $request->validated();

        $room = Room::find($validated['room_id']);
        $property = $room->property;

        $validated['property_id'] = $room->property->id;
        $validated['currency'] = $room->property->currency;

        // Set default values for restrictions if not provided
        $validated = $this->setDefaultRestrictions($validated);

        // For per_person manual mode, ensure occupancy_options match room's max_people
        if ($validated['sell_mode'] === 'per_person' && $validated['rate_mode'] === 'manual') {
            $maxPeople = $room->adults ?? 1;
            // Set price as the rate of the is_primary occupancy
            $primaryOption = collect($validated['occupancy_options'])->firstWhere('occupancy', $maxPeople);
            $validated['price'] = $primaryOption['rate'] ?? 0;
        }

        // For per_person auto mode, generate occupancy options based on auto_rate_settings
        if ($validated['sell_mode'] === 'per_person' && $validated['rate_mode'] === 'auto') {
            $maxPeople = $room->adults ?? 1;
            $primaryOccupancy = $validated['primary_occupancy'] ?? 1;
            $price = $validated['price'] ?? 0; // Use price instead of default_rate
            $occupancyOptions = [];

            for ($i = 1; $i <= $maxPeople; $i++) {
                $occupancyOptions[] = [
                    'occupancy' => $i,
                    'rate' => $price, // Use price as the base rate
                    'is_primary' => ($i === $primaryOccupancy),
                ];
            }
            $validated['occupancy_options'] = $occupancyOptions;

            // Set price from primary occupancy for database compatibility
            $validated['price'] = $price;

            // Lưu primary_occupancy vào database
            $validated['primary_occupancy'] = $primaryOccupancy;

            // Chỉ giữ lại các fields cần thiết cho auto_rate_settings
            $validated['auto_rate_settings'] = [
                'increase_mode' => $validated['auto_rate_settings']['increase_mode'],
                'decrease_mode' => $validated['auto_rate_settings']['decrease_mode'],
                'increase_value' => $validated['auto_rate_settings']['increase_value'],
                'decrease_value' => $validated['auto_rate_settings']['decrease_value'],
            ];
        }

        try {
            DB::beginTransaction();

            $ratePlan = RatePlan::create($validated);

            // Tạo rateplan_history với effective_date = ngày hiện tại
            $this->createRateplanHistory($ratePlan, $validated, now());

            // Create RatePlanOTA records if booking sources provided
            if (!empty($validated['booking_source_ids'])) {
                foreach ($validated['booking_source_ids'] as $bookingSourceId) {
                    $ratePlan->ratePlanOTAs()->create([
                        'booking_source_id' => $bookingSourceId,
                    ]);
                }
            }

            // // Create RatePlanOTA records with connected booking sources
            // $propertyBookingSources = $property->bookingSources;
            // if ($propertyBookingSources->isNotEmpty()) {
            //     foreach ($propertyBookingSources as $bookingSource) {
            //         $ratePlan->ratePlanOTAs()->create([
            //             'booking_source_id' => $bookingSource->id,
            //         ]);
            //     }
            // }

            // Sync to Channex for each booking source
            if ($property->is_sync_enabled && $property->external_id && $room->external_id) {
                $channexService = app(\App\Services\ChannexService::class);

                // Get rate plan OTAs for this rate plan
                $ratePlanOTAs = $ratePlan->ratePlanOTAs()->with('bookingSource')->get();

                foreach ($ratePlanOTAs as $ratePlanOTA) {
                    try {
                        $payload = $this->formatRatePlanPayload($ratePlan, $ratePlanOTA->bookingSource);
                        logger($payload);
                        $response = $channexService->createRatePlan($payload);
                        // dd($response);
                        // Store the external ID
                        $ratePlanOTA->update(['external_id' => $response['id']]);

                        // Create occupancy options based on Channex response
                        foreach ($response['attributes']['options'] as $option) {
                            $ratePlanOTA->occupancyOptions()->create([
                                'external_id' => $option['id'],
                                'occupancy' => $option['occupancy'],
                                'is_primary' => $option['is_primary'] ?? false,
                                'rate' => $option['rate'],
                            ]);
                        }
                    } catch (\Exception $e) {
                        logger($e);
                        Log::error('Failed to sync rate plan to Channex for booking source: ' . $ratePlanOTA->bookingSource->name, ['message' => $e->getMessage()]);
                        // Continue with other booking sources even if one fails
                    }
                }
            }

            DB::commit();
            return back()->with('created', 'Tạo rate plan thành công');
        } catch (\Throwable $th) {
            logger($th);
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    private function setDefaultRestrictions($validated)
    {
        // Set default restrictions if not provided
        $defaultRestrictions = [
            'max_stay' => 0,
            'min_stay_arrival' => 1,
            'min_stay_through' => 1,
            'closed_to_arrival' => [false, false, false, false, false, false, false],
            'closed_to_departure' => [false, false, false, false, false, false, false],
            'stop_sell' => [false, false, false, false, false, false, false],
        ];

        foreach ($defaultRestrictions as $key => $defaultValue) {
            if (!isset($validated[$key])) {
                $validated[$key] = $defaultValue;
            }
        }

        // Convert single values to arrays for restrictions
        $restrictionFields = ['max_stay', 'min_stay_arrival', 'min_stay_through'];
        foreach ($restrictionFields as $field) {
            if (isset($validated[$field]) && !is_array($validated[$field])) {
                $value = $validated[$field];
                $validated[$field] = array_fill(0, 7, $value);
            }
        }

        return $validated;
    }

    public function update(RatePlanRequest $request, RatePlan $ratePlan)
    {
        $validated = $request->validated();

        $validated = $this->setDefaultRestrictions($validated);
        try {
            DB::beginTransaction();

            $property = $ratePlan->property;
            $room = $ratePlan->room;

            // For per_person manual mode, ensure occupancy_options match room's max_people
            if ($validated['sell_mode'] === 'per_person' && $validated['rate_mode'] === 'manual') {
                // Set price as the rate of the is_primary occupancy
                logger($validated['occupancy_options']);
                $primaryOption = collect($validated['occupancy_options'])->firstWhere('is_primary', true);
                $validated['price'] = $primaryOption['rate'] ?? 0;
            }

            // For per_person auto mode, generate occupancy options based on auto_rate_settings
            if ($validated['sell_mode'] === 'per_person' && $validated['rate_mode'] === 'auto') {
                $maxPeople = $room->max_people ?? 1;
                $primaryOccupancy = $validated['primary_occupancy'] ?? 1;
                $price = $validated['price'] ?? 0; // Use price instead of default_rate
                $occupancyOptions = [];

                for ($i = 1; $i <= $maxPeople; $i++) {
                    $occupancyOptions[] = [
                        'occupancy' => $i,
                        'rate' => $price, // Use price as the base rate
                        'is_primary' => ($i === $primaryOccupancy),
                    ];
                }
                $validated['occupancy_options'] = $occupancyOptions;

                // Set price from primary occupancy for database compatibility
                $validated['price'] = $price;

                // Lưu primary_occupancy vào database
                $validated['primary_occupancy'] = $primaryOccupancy;

                // Chỉ giữ lại các fields cần thiết cho auto_rate_settings
                $validated['auto_rate_settings'] = [
                    'increase_mode' => $validated['auto_rate_settings']['increase_mode'],
                    'decrease_mode' => $validated['auto_rate_settings']['decrease_mode'],
                    'increase_value' => $validated['auto_rate_settings']['increase_value'],
                    'decrease_value' => $validated['auto_rate_settings']['decrease_value'],
                ];
            }

            // Tạo rateplan_history với effective_date = ngày đầu tiên của inventory window (ngày 501)
            // $firstInventoryDate = now()->addDays(500);
            $firstInventoryDate = now()->addDays(500);
            $this->createRateplanHistory($ratePlan, $validated, $firstInventoryDate);

            $ratePlan->update($validated);

            // Sync to Channex for each booking source
            if ($property->is_sync_enabled && $property->external_id && $room->external_id) {
                $channexService = app(\App\Services\ChannexService::class);

                // Get current rate plan OTAs for this rate plan
                $ratePlanOTAs = $ratePlan->ratePlanOTAs()->with('bookingSource')->get();

                foreach ($ratePlanOTAs as $ratePlanOTA) {
                    try {
                        $payload = $this->formatRatePlanPayload($ratePlan, $ratePlanOTA->bookingSource);

                        if ($ratePlanOTA->external_id) {
                            // Update existing rate plan on Channex
                            $response = $channexService->updateRatePlan($ratePlanOTA->external_id, $payload);

                            foreach ($response['attributes']['options'] as $option) {
                                $occupancyOption = $ratePlanOTA->occupancyOptions()
                                    ->where('external_id', $option['id'])
                                    ->first();

                                if ($occupancyOption) {
                                    // Update existing occupancy option
                                    $occupancyOption->update([
                                        'occupancy' => $option['occupancy'],
                                        'is_primary' => $option['is_primary'] ?? false,
                                        'rate' => $option['rate'],
                                    ]);
                                } else {
                                    // Create new occupancy option if not exists
                                    $ratePlanOTA->occupancyOptions()->create([
                                        'external_id' => $option['id'],
                                        'occupancy' => $option['occupancy'],
                                        'is_primary' => $option['is_primary'] ?? false,
                                        'rate' => $option['rate'],
                                    ]);
                                }
                            }
                        } else {
                            // Create new rate plan on Channex
                            $response = $channexService->createRatePlan($payload);
                            $ratePlanOTA->update(['external_id' => $response['id']]);

                            // Create occupancy options based on Channex response
                            foreach ($response['attributes']['options'] as $option) {
                                $ratePlanOTA->occupancyOptions()->create([
                                    'external_id' => $option['id'],
                                    'occupancy' => $option['occupancy'],
                                    'is_primary' => $option['is_primary'] ?? false,
                                    'rate' => $option['rate'],
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        logger($e);
                        Log::error('Failed to sync rate plan to Channex for booking source: ' . $ratePlanOTA->bookingSource->name, ['message' => $e->getMessage()]);
                        // Continue with other booking sources even if one fails
                    }
                }
            }

            DB::commit();
            return back()->with('updated', 'Cập nhật rate plan thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }

    public function destroy(RatePlan $ratePlan)
    {
        try {
            $property = $ratePlan->property;
            $room = $ratePlan->room;

            // Delete from Channex for each booking source
            if ($property->is_sync_enabled && $property->external_id && $room->external_id) {
                $channexService = app(\App\Services\ChannexService::class);

                // Get rate plan OTAs for this rate plan
                $ratePlanOTAs = $ratePlan->ratePlanOTAs()->with('bookingSource')->get();

                foreach ($ratePlanOTAs as $ratePlanOTA) {
                    try {
                        if ($ratePlanOTA->external_id) {
                            $channexService->deleteRatePlan($ratePlanOTA->external_id);
                        }
                    } catch (\Exception $e) {
                        logger($e);
                        Log::error('Failed to delete rate plan on Channex for booking source: ' . $ratePlanOTA->bookingSource->name, ['message' => $e->getMessage()]);
                        // Continue with other booking sources even if one fails
                    }
                }
            }
            logger('delete rate plan');
            $ratePlan->delete();
            return back()->with('deleted', 'Giá đã được xóa');
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function formatRatePlanPayload(RatePlan $ratePlan, BookingSource $bookingSource = null): array
    {
        $room = $ratePlan->room;
        $property = $ratePlan->property;

        // Calculate base rate
        $baseRate = $ratePlan->price;
        if ($bookingSource) {
            $baseRate = round($ratePlan->price * (1 + ($bookingSource->price_percentage / 100)));
        }

        $payload = [
            'title' => $ratePlan->title  . ' (' . $bookingSource->name . ')',
            'property_id' => $property->external_id,
            'room_type_id' => $room->external_id,
            'currency' => $ratePlan->currency ?? 'VND',
            'sell_mode' => $ratePlan->sell_mode ?? 'per_room',
            'meal_type' => $ratePlan->meal_type ?? null,
            'children_fee' => number_format($ratePlan->children_fee ?? 0, 2),
            'infant_fee' => number_format($ratePlan->infant_fee ?? 0, 2),
            'max_stay' => $ratePlan->max_stay ?? [0, 0, 0, 0, 0, 0, 0],
            'min_stay_arrival' => $ratePlan->min_stay_arrival ?? [1, 1, 1, 1, 1, 1, 1],
            'min_stay_through' => $ratePlan->min_stay_through ?? [1, 1, 1, 1, 1, 1, 1],
            'closed_to_arrival' => $ratePlan->closed_to_arrival ?? [false, false, false, false, false, false, false],
            'closed_to_departure' => $ratePlan->closed_to_departure ?? [false, false, false, false, false, false, false],
            'stop_sell' => $ratePlan->stop_sell ?? [false, false, false, false, false, false, false],
            'inherit_rate' => $ratePlan->inherit_rate ?? false,
            'inherit_closed_to_arrival' => $ratePlan->inherit_closed_to_arrival ?? false,
            'inherit_closed_to_departure' => $ratePlan->inherit_closed_to_departure ?? false,
            'inherit_stop_sell' => $ratePlan->inherit_stop_sell ?? false,
            'inherit_min_stay_arrival' => $ratePlan->inherit_min_stay_arrival ?? false,
            'inherit_min_stay_through' => $ratePlan->inherit_min_stay_through ?? false,
            'inherit_max_stay' => $ratePlan->inherit_max_stay ?? false,
            'inherit_max_sell' => $ratePlan->inherit_max_sell ?? false,
            'inherit_max_availability' => $ratePlan->inherit_max_availability ?? false,
            'inherit_availability_offset' => $ratePlan->inherit_availability_offset ?? false,
        ];

        // Ensure restrictions are arrays
        $restrictionFields = ['max_stay', 'min_stay_arrival', 'min_stay_through'];
        foreach ($restrictionFields as $field) {
            if (!is_array($payload[$field])) {
                $value = $payload[$field];
                $payload[$field] = array_fill(0, 7, $value);
            }
        }

        // Handle sell_mode and rate_mode
        if ($ratePlan->sell_mode === 'per_room') {
            // Per room mode - simple single option
            $payload['rate_mode'] = 'manual';
            $payload['options'] = [
                [
                    'occupancy' => 1,
                    'is_primary' => true,
                    'rate' => $baseRate,
                ]
            ];
        } else {
            // Per person mode
            $payload['rate_mode'] = $ratePlan->rate_mode ?? 'manual';

            // Handle auto rate settings for per_person + auto mode
            if ($ratePlan->rate_mode === 'auto' && $ratePlan->auto_rate_settings) {
                $payload['auto_rate_settings'] = $ratePlan->auto_rate_settings;
            }

            $payload['options'] = $this->formatPerPersonOptions($ratePlan, $baseRate);
        }

        return $payload;
    }

    private function formatPerPersonOptions(RatePlan $ratePlan, $baseRate)
    {
        $options = [];
        $room = $ratePlan->room;
        $maxPeople = $room->max_people ?? 1;
        $adults = $room->adults ?? 1;

        if ($ratePlan->rate_mode === 'manual') {
            // Lấy occupancy_options từ request, set is_primary = true cho occupancy = room->adults
            $occupancyOptions = request('occupancy_options', []);
            foreach ($occupancyOptions as $option) {
                $options[] = [
                    'occupancy' => $option['occupancy'],
                    'is_primary' => ($option['occupancy'] == $adults),
                    'rate' => $option['rate'],
                    'derived_option' => null,
                ];
            }
            return $options;
        }

        // Get occupancy options from request or use existing ones from database
        $occupancyOptions = request('occupancy_options', []);

        if (empty($occupancyOptions)) {
            // Use existing occupancy options from database if available
            if ($ratePlan->occupancy_options) {
                $occupancyOptions = $ratePlan->occupancy_options;
            } else {
                // Generate options based on room's max_people
                for ($i = 1; $i <= $maxPeople; $i++) {
                    $occupancyOptions[] = [
                        'occupancy' => $i,
                        'rate' => $baseRate,
                        'is_primary' => false,
                    ];
                }
            }
        }

        foreach ($occupancyOptions as $option) {
            $rateOption = [
                'occupancy' => $option['occupancy'],
                'is_primary' => false,
                'rate' => $baseRate, // Use baseRate instead of option['rate']
            ];

            if ($ratePlan->rate_mode === 'auto') {
                // For auto mode, use primary_occupancy from database
                $primaryOccupancy = $ratePlan->primary_occupancy ?? 1;
                if ($option['occupancy'] === $primaryOccupancy) {
                    $rateOption['is_primary'] = true;
                }

                // Calculate derived options for non-primary occupancies
                if ($option['occupancy'] !== $primaryOccupancy) {
                    $rule = '';
                    $value = 0;

                    if ($option['occupancy'] < $primaryOccupancy) {
                        $rule = "decrease_by_" . ($ratePlan->auto_rate_settings['decrease_mode'] === '%' ? 'percent' : 'amount');
                        $value = abs($option['occupancy'] - $primaryOccupancy) * ($ratePlan->auto_rate_settings['decrease_value'] ?? 0);
                    } elseif ($option['occupancy'] > $primaryOccupancy) {
                        $rule = "increase_by_" . ($ratePlan->auto_rate_settings['increase_mode'] === '%' ? 'percent' : 'amount');
                        $value = abs($option['occupancy'] - $primaryOccupancy) * ($ratePlan->auto_rate_settings['increase_value'] ?? 0);
                    }

                    if ($rule) {
                        $rateOption['derived_option'] = [
                            'rate' => [[$rule, (string)$value]]
                        ];
                    } else {
                        $rateOption['derived_option'] = null;
                    }
                } else {
                    $rateOption['derived_option'] = null;
                }
            } else {
                $rateOption['derived_option'] = null;
            }

            $options[] = $rateOption;
        }

        return $options;
    }

    /**
     * Tạo rateplan_history với dữ liệu từ rateplan
     */
    private function createRateplanHistory(RatePlan $ratePlan, array $data, $effectiveDate)
    {
        $historyData = [
            'rate_plan_id' => $ratePlan->id,
            'effective_date' => $effectiveDate,
            'primary_occupancy' => $data['primary_occupancy'] ?? 1,
            'rate' => $data['price'] ?? 0,
            'children_fee' => $data['children_fee'] ?? null,
            'infant_fee' => $data['infant_fee'] ?? null,
            'min_stay_arrival' => $data['min_stay_arrival'] ?? [1, 1, 1, 1, 1, 1, 1],
            'min_stay_through' => $data['min_stay_through'] ?? [1, 1, 1, 1, 1, 1, 1],
            'max_stay' => $data['max_stay'] ?? [0, 0, 0, 0, 0, 0, 0],
            'closed_to_arrival' => $data['closed_to_arrival'] ?? [false, false, false, false, false, false, false],
            'closed_to_departure' => $data['closed_to_departure'] ?? [false, false, false, false, false, false, false],
            'stop_sell' => $data['stop_sell'] ?? [false, false, false, false, false, false, false],
            'metadata' => [
                'sell_mode' => $data['sell_mode'],
                'rate_mode' => $data['rate_mode'] ?? null,
                'auto_rate_settings' => $data['auto_rate_settings'] ?? null,
            ],
        ];

        // Thêm occupancy_options nếu rate_mode = manual
        if (isset($data['occupancy_options']) && $data['rate_mode'] === 'manual') {
            $historyData['occupancy_options'] = $data['occupancy_options'];
        }

        RateplanHistory::updateOrCreate(
            [
                'rate_plan_id' => $historyData['rate_plan_id'],
                'effective_date' => is_object($historyData['effective_date'])
                    ? $historyData['effective_date']->format('Y-m-d')
                    : (is_string($historyData['effective_date'])
                        ? \Carbon\Carbon::parse($historyData['effective_date'])->format('Y-m-d')
                        : $historyData['effective_date']),
            ],
            $historyData
        );
    }
}
