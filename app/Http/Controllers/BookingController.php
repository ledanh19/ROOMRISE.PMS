<?php

namespace App\Http\Controllers;

use App\Exports\ExportBookings;
use App\Helpers\Helper;
use App\Helpers\ResponseHelper;
use App\Helpers\RoleHelper;
use App\Http\Resources\BookingItemResource;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingCustomer;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\Inventory;
use App\Models\OccupancyOption;
use App\Models\Partner;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomUnit;
use App\Services\ChannexService;
use App\Services\PancakeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Booking::class, 'booking');
    }

    public function index(Request $request)
    {
        $search = $request->search;
        $paginate = is_numeric($request->paginate) && $request->paginate > 0 ? $request->paginate : 10;
        $date_type = $request->date_type;
        $range_date = $request->range_date;
        $status = $request->status;
        $payment_type = $request->payment_type;
        $payment_status = $request->payment_status;
        $room_type = $request->room_type;
        $ota_name = $request->ota_name;
        $property_id = $request->property_id;

        $filters = $request->only([
            'search',
            'paginate',
            'date_type',
            'range_date',
            'status',
            'payment_type',
            'payment_status',
            'room_type',
            'ota_name',
            'property_id',
        ]);

        $partnerGroupId = RoleHelper::getScopedPartnerGroupId();

        $dates = null;
        if (is_string($range_date) && str_contains($range_date, ' to ')) {
            [$start, $end] = explode(' to ', $range_date);
            $dates = [
                'start' => Carbon::parse($start),
                'end' => Carbon::parse($end),
            ];
        } elseif ($range_date) {
            $date = Carbon::parse($range_date);
            $dates = [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ];
        }

        $bookingsQuery = Booking::with(['property', 'bookingRooms', 'room', 'roomUnit', 'customer.partner'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('customer', function ($q) use ($search) {
                    $q->where('full_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($room_type, function ($query) use ($room_type) {
                $query->whereHas('bookingRooms', function ($q) use ($room_type) {
                    $q->where('room_id', $room_type);
                });
            })
            ->when($ota_name, function ($query) use ($ota_name) {
                $query->where('ota_name', $ota_name);
            })
            ->when($payment_type, function ($query) use ($payment_type) {
                $query->where('payment_type', $payment_type);
            })
            ->when($payment_status, function ($query) use ($payment_status) {
                $query->where('payment_status', $payment_status);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($property_id, function ($query) use ($property_id) {
                $query->where('property_id', $property_id);
            })

            ->when($date_type && $dates, function ($query) use ($date_type, $dates) {
                if (in_array($date_type, ['check_in_date', 'check_out_date'])) {
                    $query->whereHas('bookingRooms', function ($q) use ($date_type, $dates) {
                        $q->whereBetween($date_type, [$dates['start'], $dates['end']]);
                    });
                }
            })

            ->when($partnerGroupId, function ($query) use ($partnerGroupId) {
                $query->whereHas('property', function ($q) use ($partnerGroupId) {
                    $q->where('partner_group_id', $partnerGroupId);
                });
            })
            ->orderBy('created_at', 'desc');


        $bookings = (clone $bookingsQuery)
            ->paginate($paginate)
            ->appends($filters);

        $totalAmount = (clone $bookingsQuery)->sum('total_amount');
        $totalPaid = (clone $bookingsQuery)->sum('paid');
        $totalRemaining = (clone $bookingsQuery)->sum('remaining');
        $query = DB::table('properties')
            ->select('id', 'name')
            ->where('is_active', 1);

        if (RoleHelper::isPartnerScopedUser()) {
            $query->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
        }

        $properties = $query->orderBy('name')->get();
        return Inertia::render('Bookings/Index', [
            'filters' => $filters,
            'data' => ResponseHelper::dataTable($bookings, BookingItemResource::class),
            'totalAmount' => $totalAmount,
            'totalPaid' => $totalPaid,
            'totalRemaining' => $totalRemaining,
            'propertyOptions' => $properties,

        ]);
    }

    public function getDetail(Booking $booking)
    {
        return new BookingItemResource($booking);
    }

    public function schedule(Request $request)
    {
        $this->authorize('viewSchedule', Booking::class);

        $partnerGroupId = RoleHelper::getScopedPartnerGroupId();
        $filters = $request->only(['property_id', 'room_id']);
        $propertyId = $filters['property_id'] ?? null;
        $roomId = $filters['room_id'] ?? null;
        $propertiesQuery = Property::with('rooms.roomUnits')
            ->when($partnerGroupId, fn($q) => $q->where('partner_group_id', $partnerGroupId));

        if ($propertyId) {
            $propertiesQuery->where('id', $propertyId);
        }

        $properties = $propertiesQuery->get();

        $resourceTree = $properties->flatMap(function ($property) use ($roomId) {
            return $property->rooms
                ->when($roomId, fn($query) => $query->where('id', $roomId))
                ->values()
                ->map(function ($room) use ($property) {
                    return [
                        'id' => "room_$room->id",
                        'title' => 'Loại phòng: ' . $room->name,
                        'group' => $property->name,
                        'extendedProps' => [
                            'property_id' => $property->id,
                            'room_id' => $room->id,
                        ],
                        'children' => $room->roomUnits->map(function ($unit) use ($property, $room) {
                            return [
                                'id' => "unit_$unit->id",
                                'group' => $property->name,
                                'title' => 'Phòng ' . $unit->name,
                                'extendedProps' => [
                                    'room_unit_id' => $unit->id,
                                    'note' => $unit->note,
                                    'property_id' => $property->id,
                                    'room_id' => $room->id,
                                ]
                            ];
                        })->values()->toArray()
                    ];
                });
        })->values()->toArray();

        // Lấy booking_rooms thay vì bookings
        $bookingRoomsQuery = BookingRoom::with(['booking.customer', 'room', 'roomUnit'])
            ->whereHas('booking', function ($q) use ($partnerGroupId, $propertyId) {
                if ($partnerGroupId) {
                    $q->whereHas('property', fn($q2) => $q2->where('partner_group_id', $partnerGroupId));
                }

                if ($propertyId) {
                    $q->where('property_id', $propertyId);
                }

                $q->where('status', '!=', 'Hủy');
            });


        if ($roomId) {
            $bookingRoomsQuery->where('room_id', $roomId);
        }

        // todo: check if need to filter by status
        $bookingRooms = $bookingRoomsQuery->get();

        $events = $bookingRooms->map(function ($bookingRoom) {
            $booking = $bookingRoom->booking;

            // Xử lý end time cho booking 1 đêm
            $checkInDate = $bookingRoom->check_in_date;
            $checkOutDate = $bookingRoom->check_out_date;
            $checkInTime = $bookingRoom->check_in_time;
            $checkOutTime = $bookingRoom->check_out_time;

            // Xử lý end time cho booking
            $isOneNight = $checkInDate === $checkOutDate ||
                (strtotime($checkOutDate) - strtotime($checkInDate)) <= 86400; // 1 ngày = 86400 giây

            $endTime = $checkOutTime;
            if ($isOneNight) {
                // Với booking 1 đêm, kết thúc vào 23:59 ngày check-in
                $endTime = '23:59';
                $checkOutDate = $checkInDate;
            } else {
                // Với booking nhiều đêm, kết thúc vào 23:59 ngày trước check-out
                // Ví dụ: check-out 24/3 thì event kết thúc 23/3 23:59
                $checkOutDate = date('Y-m-d', strtotime($checkOutDate . ' -1 day'));
                $endTime = '23:59';
            }

            return [
                'id' => $bookingRoom->id,
                'resourceId' => $bookingRoom->room_unit_id ? 'unit_' . $bookingRoom->room_unit_id : 'room_' . $bookingRoom->room_id,
                'start' => $checkInDate . 'T' . $checkInTime,
                'end' => $checkOutDate . 'T' . $endTime,
                'title' => $booking->customer?->full_name ?? 'Không rõ khách',
                'extendedProps' => [
                    'booking_id' => $booking->id,
                    'booking_room_id' => $bookingRoom->id,
                    'room_unit_id' => $bookingRoom->room_unit_id,
                    'room_id' => $bookingRoom->room_id,
                    'status' => $booking->status,
                    'ota_name' => $booking->ota_name,
                    'start' => $checkInDate . 'T' . $checkInTime,
                    'end' => $checkOutDate . 'T' . $endTime,
                    'origin_end' => $bookingRoom->check_out_date,
                ]
            ];
        });

        $existingEventsByUnit = $events->groupBy('resourceId')->map(function ($eventsForUnit) {
            return $eventsForUnit->map(function ($event) {
                return [
                    'start' => $event['start'],
                    'end' => $event['end'],
                ];
            })->values();
        });

        $unscheduledBookings = Booking::with('customer')
            ->whereNull('room_unit_id')
            ->when($partnerGroupId, fn($q) => $q->whereHas('property', fn($q2) => $q2->where('partner_group_id', $partnerGroupId)))
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->when($roomId, fn($q) => $q->where('room_id', $roomId))
            ->get()->map(function ($booking) {
                // Xử lý end time cho booking 1 đêm
                $checkInDate = $booking->check_in_date;
                $checkOutDate = $booking->check_out_date;
                $checkInTime = $booking->check_in_time;
                $checkOutTime = $booking->check_out_time;

                // Xử lý end time cho booking
                $isOneNight = $checkInDate === $checkOutDate ||
                    (strtotime($checkOutDate) - strtotime($checkInDate)) <= 86400; // 1 ngày = 86400 giây

                $endTime = $checkOutTime;
                if ($isOneNight) {
                    // Với booking 1 đêm, kết thúc vào 23:59 ngày check-in
                    $endTime = '23:59';
                    $checkOutDate = $checkInDate;
                } else {
                    // Với booking nhiều đêm, kết thúc vào 23:59 ngày trước check-out
                    // Ví dụ: check-out 24/3 thì event kết thúc 23/3 23:59
                    $checkOutDate = date('Y-m-d', strtotime($checkOutDate . ' -1 day'));
                    $endTime = '23:59';
                }

                return [
                    'id' => $booking->id,
                    'title' => $booking->customer?->full_name ?? 'Không rõ khách',
                    'extendedProps' => [
                        'booking_id' => $booking->id,
                        'status' => $booking->status,
                        'room_id' => $booking->room_id,
                        'room_name' => $booking->room?->name,
                        'property_id' => $booking->property_id,
                        'property_name' => $booking->property?->name,
                        'ota_name' => $booking->ota_name,
                        'start' => $checkInDate . 'T' . $checkInTime,
                        'end' => $checkOutDate . 'T' . $endTime,
                    ],
                ];
            });

        $unitRoomMap = RoomUnit::pluck('room_id', 'id');

        // $propertyOptions = Property::select('id', 'name')->get();
        $propertyOptions = Property::select('id', 'name')
            ->when($partnerGroupId, fn($q) => $q->where('partner_group_id', $partnerGroupId))
            ->get();

        $roomOptions = [];
        if ($propertyId) {
            $roomOptions = Room::where('property_id', $propertyId)
                ->select('id', 'name')
                ->get();
        }
        return Inertia::render('Bookings/Schedule', ['filters' => $filters, 'resourceTree' => $resourceTree, 'bookings' => $events, 'unscheduledBookings' => $unscheduledBookings, 'unitRoomMap' => $unitRoomMap, 'existingEventsByUnit' => $existingEventsByUnit, 'propertyOptions' => $propertyOptions, 'roomOptions' => $roomOptions]);
    }

    public function assignRoomUnit(Booking $booking, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_unit_id' => 'exists:room_units,id',
        ], [
            'room_unit_id.exists'   => 'Không tồn tại phòng này.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors([
                'msg' =>  $validator->errors()->first('room_unit_id'),
            ]);
        }

        $roomUnitId = $request->input('room_unit_id');

        if ($roomUnitId) {
            $start = Carbon::parse($booking->check_in_date . ' ' . $booking->check_in_time);
            $end = Carbon::parse($booking->check_out_date . ' ' . $booking->check_out_time);

            $conflict = Booking::where('room_unit_id', $roomUnitId)
                ->where('id', '!=', $booking->id)
                ->where(function ($q) use ($start, $end) {
                    $q->where(function ($q) use ($start, $end) {
                        $q->whereDate('check_in_date', '<=', $end->toDateString())
                            ->whereDate('check_out_date', '>=', $start->toDateString());
                    })->where(function ($q) use ($start, $end) {
                        $q->whereRaw("STR_TO_DATE(CONCAT(check_in_date, ' ', check_in_time), '%Y-%m-%d %H:%i:%s') < ?", [$end])
                            ->whereRaw("STR_TO_DATE(CONCAT(check_out_date, ' ', check_out_time), '%Y-%m-%d %H:%i:%s') > ?", [$start]);
                    });
                })->exists();

            if ($conflict) {
                return back()->withErrors([
                    'msg' => 'Phòng này đã có người đặt trong khoảng thời gian đó.',
                ]);
            }
        }

        $booking->room_unit_id = $roomUnitId;
        $booking->save();
        return redirect()->back();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id'           => 'required|exists:properties,id',
            'status'                => 'required|string',
            'check_in_date'         => 'required|date',
            'check_out_date'        => 'required|date|after_or_equal:check_in_date',
            'check_in_time'         => 'required|date_format:H:i',
            'check_out_time'        => 'required|date_format:H:i',
            'customer_id'           => 'nullable|exists:customers,id',
            'paid'                  => 'nullable|numeric',
            'payment_method'        => 'nullable|string',
            'note'                  => 'nullable|string',
            'ota_name'              => 'required|string',
            'room_payment_method'   => 'required|string',
            'adults'                => 'required|numeric|min:1',
            'children'              => 'nullable|numeric',
            'newborn'               => 'nullable|numeric',
            'payment_content'       => 'nullable|string',

            'booking_rooms' => 'required|array|min:1',
            'booking_rooms.*.room_id' => 'required|exists:rooms,id',
            'booking_rooms.*.room_unit_id' => 'nullable|exists:room_units,id',
            'booking_rooms.*.rate_plan_id' => 'required|exists:rate_plans,id',
            'booking_rooms.*.room_price_at_booking' => 'required|numeric',
            'booking_rooms.*.check_in_date' => 'required|date',
            'booking_rooms.*.check_out_date' => 'required|date|after_or_equal:booking_rooms.*.check_in_date',
            'booking_rooms.*.check_in_time' => 'required|date_format:H:i',
            'booking_rooms.*.check_out_time' => 'required|date_format:H:i',

            'customer' => 'required|array|min:1',
            'customer.0.full_name' => 'required|string|max:255',
            'customer.0.phone'     => 'required|string|max:20',
            'customer.0.email'     => 'required|email|max:255',
            'customer.0.country'   => 'nullable|string|max:100',
            'customer.0.id_number' => 'nullable|string|max:50',
            'customer.0.issue_date' => 'nullable|date',
            'customer.0.type'      => 'nullable|string|max:50',
            'customer.0.image'     => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'customer.0.user_id'   => 'nullable|exists:customers,id',
            'customer.0.partner_id'   => 'nullable|exists:partners,id',
        ], [
            // Booking
            'property_id.required' => 'Vui lòng chọn khách sạn.',
            'property_id.exists' => 'Khách sạn không hợp lệ.',
            'status.required' => 'Trạng thái là bắt buộc.',
            'check_in_date.required' => 'Vui lòng chọn ngày nhận phòng.',
            'check_out_date.required' => 'Vui lòng chọn ngày trả phòng.',
            'check_out_date.after_or_equal' => 'Ngày trả phòng phải sau hoặc bằng ngày nhận phòng.',
            'check_in_time.required' => 'Vui lòng nhập giờ nhận phòng.',
            'check_out_time.required' => 'Vui lòng nhập giờ trả phòng.',
            'check_in_time.date_format' => 'Giờ nhận phòng không đúng định dạng (H:i).',
            'check_out_time.date_format' => 'Giờ trả phòng không đúng định dạng (H:i).',
            'ota_name.required' => 'Vui lòng chọn nguồn đặt phòng.',
            'room_payment_method.required' => 'Vui lòng chọn phương thức thanh toán phòng.',
            'adults.required' => 'Vui lòng nhập số lượng người lớn.',
            'adults.min' => 'Phải có ít nhất 1 người lớn.',

            // Booking Rooms
            'booking_rooms.required' => 'Cần ít nhất một phòng đặt.',
            'booking_rooms.*.room_id.required' => 'Vui lòng chọn loại phòng.',
            'booking_rooms.*.room_id.exists' => 'Loại phòng không tồn tại.',
            'booking_rooms.*.rate_plan_id.required' => 'Vui lòng chọn gói giá.',
            'booking_rooms.*.rate_plan_id.exists' => 'Gói giá không hợp lệ.',
            'booking_rooms.*.room_price_at_booking.required' => 'Vui lòng nhập giá phòng.',
            'booking_rooms.*.room_price_at_booking.numeric' => 'Giá phòng phải là số.',

            // Customer
            'customer.required' => 'Cần nhập thông tin khách hàng.',
            'customer.0.full_name.required' => 'Tên khách hàng là bắt buộc.',
            'customer.0.email.required' => 'Email khách hàng là bắt buộc.',
            'customer.0.email.email' => 'Email không hợp lệ.',
            'customer.0.phone.required' => 'Số điện thoại khách hàng là bắt buộc.',
            'customer.0.image.mimes' => 'Ảnh khách hàng phải là jpg, jpeg hoặc png.',
            'customer.0.image.max' => 'Ảnh khách hàng không được vượt quá 2MB.',
        ]);

        // Validate room availability trước khi vào transaction
        $fallbackDates = [
            'check_in_date' => $validated['check_in_date'],
            'check_out_date' => $validated['check_out_date'],
            'check_in_time' => $validated['check_in_time'],
            'check_out_time' => $validated['check_out_time'],
        ];
        $result = $this->validateRoomAvailability($validated['booking_rooms'], $fallbackDates);

        if (!$result['valid']) {
            return back()->withErrors([
                "booking_rooms.{$result['error']['index']}.room_unit_id" => $result['error']['message']
            ])->withInput();
        }

        try {
            return DB::transaction(function () use ($request, $validated) {
                $customerData = $validated['customer'][0];
                if ($request->hasFile('customer.0.image')) {
                    $imageFile = $request->file('customer.0.image');
                    $imagePath = $imageFile->store('customers', 'public');
                    $customerData['image'] = $imagePath;
                }
                $partner_id = null;
                if (empty($customerData['user_id'])) {
                    $customer = Customer::create([
                        'full_name'   => $customerData['full_name'],
                        'phone'       => $customerData['phone'],
                        'email'       => $customerData['email'],
                        'country'     => $customerData['country'],
                        'id_number'   => $customerData['id_number'],
                        'issue_date'  => $customerData['issue_date'],
                        'type'        => $customerData['type'],
                        'partner_id'  => $customerData['partner_id'],
                        'image'       => $customerData['image'] ?? null,
                    ]);
                    $validated['customer_id'] = $customer->id;
                    $partner_id = $customer->partner_id;
                } else {
                    $validated['customer_id'] = $customerData['user_id'];
                    $customer = Customer::findOrFail($customerData['user_id']);
                    $partner_id = $customer->partner_id;
                }

                $totalAmount = 0;
                $paymentTypeMap = [
                    'Thu tại KS' => 'Hotel Collect',
                    'Thu bởi đối tác' => 'Partner Collect',
                ];

                $payment_type = $paymentTypeMap[$validated['room_payment_method']] ?? null;

                $booking = Booking::create([
                    'property_id' => $validated['property_id'],
                    'status' => $validated['status'],
                    'status_2' => $validated['status_2'] ?? null,
                    'check_in_date' => $validated['check_in_date'],
                    'check_out_date' => $validated['check_out_date'],
                    'check_in_time' => $validated['check_in_time'],
                    'check_out_time' => $validated['check_out_time'],
                    'customer_id' => $validated['customer_id'] ?? null,
                    'paid' => 0,
                    'total_amount' => 0,
                    'customer_payment_amount' => 0,
                    'remaining' => 0,
                    'payment_status' => null,
                    'payment_type' => $payment_type,
                    'payment_method' => $validated['payment_method'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'payment_content' => $validated['payment_content'] ?? null,
                    'adults' => $validated['adults'] ?? null,
                    'children' => $validated['children'] ?? 0,
                    'newborn' => $validated['newborn'] ?? 0,
                    'commission_fee' => 0,
                    'room_payment_method' => $validated['room_payment_method'] ?? null,
                    'ota_name' => $validated['ota_name'] ?? null,
                ]);

                foreach ($validated['booking_rooms'] as $room) {
                    $ratePlanId = $room['rate_plan_id'];
                    $checkIn = Carbon::parse($room['check_in_date']);
                    $checkOut = Carbon::parse($room['check_out_date']);
                    $nights = $checkIn->diffInDays($checkOut);

                    $roomModel = Room::find($room['room_id']);

                    $totalRoomAmount = 0;
                    $priceItems = [];

                    foreach (CarbonPeriod::create($checkIn, $checkOut->copy()->subDay()) as $date) {
                        $dateStr = $date->format('Y-m-d');
                        $dailyRate = $roomModel->getLocalRateForDate($dateStr, $ratePlanId);
                        $totalRoomAmount += $dailyRate;
                        $priceItems[] = "{$dateStr}:{$dailyRate}";
                    }

                    $priceString = implode(',', $priceItems);

                    $booking->bookingRooms()->create([
                        'property_id' => $validated['property_id'],
                        'room_id' => $room['room_id'],
                        'room_unit_id' => $room['room_unit_id'] ?? null,
                        'rate_plan_id' => $ratePlanId,
                        'room_price_at_booking' => $roomModel->getLocalRateForDate($checkIn->format('Y-m-d'), $ratePlanId),
                        'check_in_date' => $room['check_in_date'],
                        'check_out_date' => $room['check_out_date'],
                        'check_in_time' => $room['check_in_time'],
                        'check_out_time' => $room['check_out_time'],
                        'total' => $totalRoomAmount,
                        'nights' => $nights,
                        'room_status' => "Chưa nhận phòng",
                        'price_date' => $priceString,
                    ]);

                    $booking->bookingCustomers()->attach($validated['customer_id']);
                    $totalAmount += $totalRoomAmount;
                }

                // TODO: optimize to use one request
                $roomIds = $booking->bookingRooms->pluck('room_id')->unique();
                foreach ($roomIds as $roomId) {
                    $room = Room::find($roomId);
                    $property = $room->property;
                    if ($property && $property->is_sync_enabled && $room->external_id) {
                        app(ChannexService::class)->recalculateAndSyncAvailabilityForRoom(
                            $room,
                            $booking->check_in_date,
                            $booking->check_out_date
                        );
                    }
                }

                $totalAmountCustomer = $totalAmount;

                $commissionFee = 0;
                $partner = Partner::find($partner_id);
                if ($partner) {
                    $commissionRate = $partner->commission ?? 0;
                    $commissionFee = round($totalAmount * $commissionRate / 100, 2);
                }

                $paid = $validated['paid'] ?? 0;
                $remaining = max($totalAmountCustomer - $paid, 0);
                $paymentStatus = $paid == 0
                    ? 'Chưa thanh toán'
                    : ($paid < $totalAmountCustomer ? 'Đã cọc' : 'Đã thanh toán');

                $booking->update([
                    'paid' => $paid,
                    'remaining' => $remaining,
                    'commission_fee' => $commissionFee,
                    'payment_status' => $paymentStatus,
                    'total_amount' => $totalAmount,
                    'customer_payment_amount' => $totalAmountCustomer,
                ]);
                $expensePaymentStatus = $paid >= $totalAmountCustomer
                    ? 'Đã thanh toán'
                    : 'Chờ thanh toán';

                if ($paid > 0) {
                    $booking->paymentHistories()->create([
                        'paid' => $paid,
                        'payment_method' => $validated['payment_method'],
                        'staff' => Auth::user()->name,
                        'payment_date' => Carbon::now(),
                        'note' => $validated['payment_content'] ?? null,
                    ]);
                    $source_business_type = $payment_type === 'Hotel Collect' ? 'Booking' : 'Partner';
                    $source_business_code = $source_business_type . '-' . $booking->id;
                    $property = Property::findOrFail($validated['property_id']);
                    $partnerGroupId = $property->partner_group_id;
                    $expense = IncomeExpense::create([
                        'date' => Carbon::now(),
                        'type' => 'income',
                        'room_payment_method' => $payment_type,
                        'payment_method' => $validated['payment_method'],
                        'payment_source' => '-',
                        'payment_object' => Customer::findOrFail($validated['customer_id'])->full_name,
                        'booking_id' => $booking->id,
                        'business_type' => 'Đặt phòng',
                        'amount' => $paid,
                        // 'payment_status' => $expensePaymentStatus,
                        'payment_status' => 'Đã thanh toán',
                        'source_business_type' => $source_business_type,
                        'source_business_code' => $source_business_code,
                        'created_by' => Auth::user()->name,
                        'note' => $validated['payment_content'] ?? null,
                        'partner_group_id' => $partnerGroupId,
                    ]);

                    $actionType = $expense->payment_status === 'Đã thanh toán'
                        ? 'confirm_payment'
                        : 'create';
                    AuditLog::create([
                        'income_expense_id' => $expense->id,
                        'action_type'       => $actionType,
                        'performed_by'      => $payment_type === 'Hotel Collect' ? Auth::user()->name : 'Hệ thống',
                        'performed_at'      => now(),
                        'source_type'       => 'auto',
                    ]);
                }

                return redirect()->back()->with('success', 'Tạo đặt phòng thành công');
            });
        } catch (\Exception $e) {
            logger($e);
            return back()->with('error', 'Có lỗi xảy ra, không thể tạo đặt phòng lúc này!');
        }
    }

    protected function validateRoomAvailability(array $bookingRooms, array $fallbackDates = [])
    {
        foreach ($bookingRooms as $index => $room) {
            if (empty($room['room_unit_id'])) {
                continue;
            }

            $checkInDate = $room['check_in_date'] ?? $fallbackDates['check_in_date'] ?? null;
            $checkOutDate = $room['check_out_date'] ?? $fallbackDates['check_out_date'] ?? null;
            $checkInTime = $room['check_in_time'] ?? $fallbackDates['check_in_time'] ?? null;
            $checkOutTime = $room['check_out_time'] ?? $fallbackDates['check_out_time'] ?? null;

            if (!$checkInDate || !$checkOutDate || !$checkInTime || !$checkOutTime) {
                return [
                    'valid' => false,
                    'error' => [
                        'index' => $index,
                        'message' => 'Thiếu thông tin thời gian để kiểm tra phòng.',
                    ]
                ];
            }

            $available = Helper::checkRoomUnitAvailable(
                $room['room_unit_id'],
                $checkInDate,
                $checkInTime,
                $checkOutDate,
                $checkOutTime
            );

            if (!$available) {
                return [
                    'valid' => false,
                    'error' => [
                        'index' => $index,
                        'room_unit_id' => $room['room_unit_id'],
                        'message' => 'Phòng đơn vị đã có người đặt trong khoảng thời gian này.',
                    ]
                ];
            }
        }

        return ['valid' => true];
    }
    public function checkAvailability(Request $request)
    {
        $bookingRooms = $request->input('booking_rooms', []);
        $fallbackDates = $request->only([
            'check_in_date',
            'check_out_date',
            'check_in_time',
            'check_out_time',
        ]);

        $result = $this->validateRoomAvailability($bookingRooms, $fallbackDates);

        if (!$result['valid']) {
            return response()->json([
                'available' => false,
                'error' => $result['error'],
            ]);
        }

        return response()->json([
            'available' => true,
        ]);
    }


    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'property_id'           => 'required|exists:properties,id',
            'status'                => 'required|string',
            'status_2'              => 'nullable|string',
            'check_in_date'         => 'required|date',
            'check_out_date'        => 'required|date|after_or_equal:check_in_date',
            'check_in_time'         => 'required|date_format:H:i',
            'check_out_time'        => 'required|date_format:H:i',
            'customer_id'           => 'nullable|exists:customers,id',
            'paid'                  => 'nullable|numeric',
            'payment_method'        => 'nullable|string',
            'note'                  => 'nullable|string',
            'booking_rooms' => 'required|array|min:1',
            'booking_rooms.*.room_id' => 'required|exists:rooms,id',
            'booking_rooms.*.room_unit_id' => 'nullable|exists:room_units,id',
            'booking_rooms.*.rate_plan_id' => 'nullable|exists:rate_plans,id',
            'booking_rooms.*.room_price_at_booking' => 'required|numeric',
            'booking_rooms.*.check_in_date' => 'required|date',
            'booking_rooms.*.check_out_date' => 'required|date|after_or_equal:booking_rooms.*.check_in_date',
            'booking_rooms.*.check_in_time' => 'required|date_format:H:i',
            'booking_rooms.*.check_out_time' => 'required|date_format:H:i',
        ]);

        // Validate room unit availability
        foreach ($validated['booking_rooms'] as $index => $room) {
            if (!empty($room['room_unit_id'])) {
                $available = Helper::checkRoomUnitAvailable(
                    $room['room_unit_id'],
                    $room['check_in_date'],
                    $room['check_in_time'],
                    $room['check_out_date'],
                    $room['check_out_time'],
                    $booking->id
                );
                if (!$available) {
                    return back()->withErrors([
                        "booking_rooms.$index.room_unit_id" => 'Phòng đơn vị này đã có người đặt trong khoảng thời gian đã chọn.',
                    ])->withInput();
                }
            }
        }

        $totalAmount = collect($validated['booking_rooms'])->sum(function ($room) {
            $nights = max(\Carbon\Carbon::parse($room['check_in_date'])->diffInDays(\Carbon\Carbon::parse($room['check_out_date'])), 1);
            return $nights * $room['room_price_at_booking'];
        });
        $paid = $validated['paid'] ?? 0;
        $remaining = max($totalAmount - $paid, 0);
        $paymentStatus = $paid == 0 ? 'Chưa thanh toán' : ($paid < $totalAmount ? 'Đã cọc' : 'Đã thanh toán');

        $booking->update([
            'property_id' => $validated['property_id'],
            'status' => $validated['status'],
            'status_2' => $validated['status_2'] ?? null,
            'check_in_date' => $validated['check_in_date'],
            'check_out_date' => $validated['check_out_date'],
            'check_in_time' => $validated['check_in_time'],
            'check_out_time' => $validated['check_out_time'],
            'customer_id' => $validated['customer_id'] ?? null,
            'paid' => $paid,
            'total_amount' => $totalAmount,
            'remaining' => $remaining,
            'payment_status' => $paymentStatus,
            'payment_method' => $validated['payment_method'] ?? null,
            'note' => $validated['note'] ?? null,
        ]);

        // Xóa các booking_rooms cũ
        $booking->bookingRooms()->delete();

        // Lưu lại các booking_rooms mới
        foreach ($validated['booking_rooms'] as $room) {
            $booking->bookingRooms()->create([
                'room_id' => $room['room_id'],
                'room_unit_id' => $room['room_unit_id'] ?? null,
                'rate_plan_id' => $room['rate_plan_id'] ?? null,
                'room_price_at_booking' => $room['room_price_at_booking'],
                'check_in_date' => $room['check_in_date'],
                'check_out_date' => $room['check_out_date'],
                'check_in_time' => $room['check_in_time'],
                'check_out_time' => $room['check_out_time'],
            ]);
        }

        return redirect()->back()->with('success', 'Cập nhật đặt phòng thành công');
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();
        return back()->with('deleted', 'Chỗ đặt phòng đã được xóa!');
    }

    public function getLogisticsData(Request $request)
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $todayBookings = Booking::whereDate('check_in_date', $today)->get();

        $todayRevenue = $todayBookings->sum(function ($booking) {
            $days = Carbon::parse($booking->check_in_date)->diffInDays(Carbon::parse($booking->check_out_date), false);
            $days = $days === 0 ? 1 : $days;
            return $days * $booking->room_price_at_booking;
        });

        $yesterdayBookings = Booking::whereDate('check_in_date', $yesterday)->get();

        $yesterdayRevenue = $yesterdayBookings->sum(function ($booking) {
            $days = Carbon::parse($booking->check_in_date)->diffInDays(Carbon::parse($booking->check_out_date), false);
            $days = $days === 0 ? 1 : $days;
            return $days * $booking->room_price_at_booking;
        });

        $changeRevenue = $yesterdayRevenue > 0
            ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1) . '%'
            : '100%';

        $todayCheckin = $todayBookings->count();
        $yesterdayCheckin = $yesterdayBookings->count();

        $changeCheckin = $yesterdayCheckin > 0
            ? round((($todayCheckin - $yesterdayCheckin) / $yesterdayCheckin) * 100, 1) . '%'
            : '100%';


        $todayCustomerIds = Booking::whereDate('check_in_date', $today)
            ->pluck('customer_id')
            ->unique();

        $yesterdayCustomerIds = Booking::whereDate('check_in_date', $yesterday)
            ->pluck('customer_id')
            ->unique();

        $newCustomers = $todayCustomerIds->diff($yesterdayCustomerIds)->count();

        $totalRooms = Room::sum('quantity');
        $roomOccupancy = $todayCheckin . '/' . $totalRooms;
        $occupancyPercent = $totalRooms > 0
            ? round(($todayCheckin / $totalRooms) * 100) . '%'
            : '0%';

        $logisticsData = [
            [
                'icon' => 'tabler-brand-cashapp',
                'color' => 'primary',
                'title' => 'Doanh Thu Hôm Nay',
                'value' => number_format($todayRevenue, 0, ',', '.') . ' VND',
                'change' => $changeRevenue,
                'isHover' => false,
                'compare' => 'So với hôm qua',
            ],
            [
                'icon' => 'tabler-alert-triangle',
                'color' => 'success',
                'title' => 'Check-in hôm nay',
                'value' => $todayCheckin,
                'change' => $changeCheckin,
                'isHover' => false,
                'compare' => 'So với hôm qua',
            ],
            [
                'icon' => 'tabler-git-fork',
                'color' => 'info',
                'title' => 'Khách hàng mới',
                'value' => $newCustomers,
                'change' => $newCustomers,
                'isHover' => false,
                'compare' => 'khách mới',
            ],
            [
                'icon' => 'tabler-clock',
                'color' => 'warning',
                'title' => 'Số Phòng Đã Đặt',
                'value' => $roomOccupancy,
                'change' => $occupancyPercent,
                'isHover' => false,
                'compare' => 'Công Suất',
            ],
        ];

        return response()->json($logisticsData);
    }

    public function getCheckInData(Request $request)
    {
        $today = now()->toDateString();

        $checkIns = Booking::whereDate('check_in_date', $today)
            ->with(['customer', 'room', 'roomUnit'])
            ->get();

        $data = $checkIns->map(function ($booking) {
            return [
                'title' => $booking->customer->full_name ?? 'Không rõ tên',
                'value' => 'Check-in',
                'desc' => ($booking->room->name ?? 'Phòng ?') . ' - ' . Carbon::parse($booking->check_in_time)->format('H:i'),
                'color' => 'info',
            ];
        });

        return response()->json($data);
    }


    public function getCheckOutData(Request $request)
    {
        $today = now()->toDateString();
        $checkOuts = Booking::whereDate('check_out_date', $today)
            ->with(['customer', 'roomUnit'])
            ->get();

        $data = $checkOuts->map(function ($booking) {
            return [
                'title' => $booking->customer->full_name ?? 'Không rõ tên',
                'value' => 'Checkout',
                'desc' => ($booking->room->name ?? 'Phòng ?') . ' - ' . Carbon::parse($booking->check_out_time)->format('H:i'),
                'color' => 'secondary',
            ];
        });

        return response()->json($data);
    }

    public function getRecentBookings(Request $request)
    {
        $recentBookings = Booking::with(['customer', 'property', 'room', 'roomUnit'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $data = $recentBookings->map(function ($booking) {
            return [
                'title' => $booking->customer->full_name ?? 'Không rõ tên',
                'desc' => ($booking->room->name ?? 'Phòng ?') . ' - ' . Carbon::parse($booking->check_in_time)->format('H:i'),
                'value' => $booking->status,
                'color' => match ($booking->status) {
                    'Xác nhận' => 'success',
                    'Chờ xác nhận' => 'warning',
                    'Đã hủy' => 'danger',
                    'Yêu cầu' => 'primary',
                    'Mới' => 'info',
                    default => 'secondary',
                },

            ];
        });

        return response()->json($data);
    }

    public function getVehicleData(Request $request)
    {

        $totalCreated = Room::sum('quantity');
        $today = Carbon::today();
        $bookedRooms = Booking::whereDate('check_in_date', $today)
            ->distinct('room_unit_id')
            ->count('room_unit_id');
        $availableRooms = $totalCreated - $bookedRooms;
        $bookedPercentage = $totalCreated > 0 ? round(($bookedRooms / $totalCreated) * 100, 1) : 0;
        $availablePercentage = 100 - $bookedPercentage;
        $data = [
            [
                'icon' => 'tabler-clock',
                'title' => 'Còn Trống',
                'time' => $availableRooms . ' Phòng',
                'percentage' => $availablePercentage,
            ],
            [
                'icon' => 'tabler-calendar-clock',
                'title' => 'Đã Đặt (Hôm nay)',
                'time' => $bookedRooms . ' Phòng',
                'percentage' => $bookedPercentage,
            ],
        ];
        return response()->json([
            'data' => $data,
            'availablePercentage' => $availablePercentage,
            'bookedPercentage' => $bookedPercentage
        ]);
    }

    public function getRoomStatusData(Request $request)
    {
        $today = Carbon::today();

        $totalUnits = RoomUnit::count();

        $maintenance = RoomUnit::where('status', 'maintenance')->count();
        $cleaning = RoomUnit::where('status', 'cleaning')->count();
        $booked = Booking::whereDate('check_in_date', $today)
            ->whereNotNull('room_unit_id')
            ->distinct()
            ->count('room_unit_id');
        $available = $totalUnits - ($booked + $maintenance + $cleaning);
        $data = [
            'booked' => $booked,
            'maintenance' => $maintenance,
            'cleaning' => $cleaning,
            'available' => $available,
            'total' => $totalUnits,
        ];

        return response()->json($data);
    }

    public function show(Booking $booking)
    {
        $booking->load(['property', 'customer.partner', 'incomeExpenses', 'partnerIncomeExpenses', 'bookingRooms', 'bookingRooms.room', 'bookingRooms.roomUnit', 'bookingRooms.property', 'bookingCustomers', 'settlement.settlementBookings']);

        $customer =  $booking->customer;
        $paymentHistories =  $booking->incomeExpenses;
        $paymentHistoriesPartner = $booking->partnerIncomeExpenses
            ->where('pivot.type', 'partner');
        $otaHistories =  $booking->partnerIncomeExpenses
            ->where('pivot.type', 'ota');

        $bookingCustomers =  $booking->bookingCustomers;

        return Inertia::render('Bookings/View', [
            'booking' => $booking,
            'customer' => $customer,
            'paymentHistories' => $paymentHistories,
            'paymentHistoriesPartner' => $paymentHistoriesPartner,
            'bookingCustomers' => $bookingCustomers,
            'otaHistories' => $otaHistories,
        ]);
    }

    public function getRoomType(Request $request)
    {
        $query = Room::select('id', 'name')
            ->with('property');

        // Filter by property_id if provided
        if ($request->has('property_id') && $request->property_id) {
            $query->where('property_id', $request->property_id);
        }

        if (RoleHelper::isPartnerScopedUser()) {
            $query->whereHas('property', function ($q) {
                $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
            });
        }

        $data = $query->get();

        return response()->json($data);
    }


    public function exportBookings(Request $request)
    {
        $date_type = $request->date_type;
        $range_date = $request->range_date;
        $status = $request->status;
        $payment_type = $request->payment_type;
        $payment_status = $request->payment_status;
        $room_type = $request->room_type;

        return Excel::download(new ExportBookings($date_type, $range_date, $status, $payment_type, $payment_status, $room_type), 'exportBookings.xlsx', ExcelExcel::XLSX);
    }

    public function roomStore(Request $request, Booking $booking)
    {
        $this->authorize('create-dat-phong', Booking::class);
        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'property_id' => ['required', 'exists:properties,id'],
            'room_unit_id' => ['required', 'exists:room_units,id'],
            'rate_plan_id' => ['nullable', 'exists:rate_plans,id'],
            'room_price_at_booking' => ['required', 'numeric', 'min:0'],
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'check_in_time' => ['required'],
            'check_out_time' => ['required'],
            'note' => ['nullable', 'string'],
            'total' => ['nullable', 'numeric'],
            'discount' => ['nullable', 'numeric'],
        ]);
        // dd($validated);

        $result = $this->validateRoomAvailability([
            [
                'room_unit_id' => $validated['room_unit_id'],
                'check_in_date' => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'check_in_time' => $validated['check_in_time'],
                'check_out_time' => $validated['check_out_time'],
            ]
        ]);

        if (!$result['valid']) {
            return back()->withErrors(['room_unit_id' => $result['error']['message']]);
        }
        $prices = $request->input('prices');
        $priceString = null;

        if (is_array($prices)) {
            $priceString = collect($prices)
                ->map(fn($item) => "{$item['date']}:{$item['price']}")
                ->implode(',');
        }
        $checkIn = Carbon::parse($validated['check_in_date']);
        $checkOut = Carbon::parse($validated['check_out_date']);
        $nights = $checkIn->diffInDays($checkOut);
        $booking->bookingRooms()->create([
            'room_id' => $validated['room_id'],
            'property_id' => $validated['property_id'],
            'room_unit_id' => $validated['room_unit_id'],
            'rate_plan_id' => $validated['rate_plan_id'] ?? null,
            'room_price_at_booking' => $validated['room_price_at_booking'],
            'check_in_date' => $validated['check_in_date'],
            'check_out_date' => $validated['check_out_date'],
            'check_in_time' => $validated['check_in_time'],
            'check_out_time' => $validated['check_out_time'],
            'note' => $validated['note'] ?? null,
            'total' => $validated['total'] ?? null,
            'discount' => $validated['discount'] ?? null,
            'price_date' => $priceString,
            'nights' => $nights,
            'room_status' => 'Chưa nhận phòng',
        ]);


        $totalAmount = $booking->bookingRooms()->sum('total');
        $newRoomTotal = $validated['total'] ?? 0;
        $newRoomDiscount = $validated['discount'] ?? 0;
        $newCustomerPay = max($newRoomTotal - $newRoomDiscount, 0);
        $totalCustomerPayment = $booking->customer_payment_amount + $newCustomerPay;
        $paid = $booking->paid;
        $remaining = max($totalCustomerPayment - $paid, 0);
        $booking->update([
            'total_amount' => $totalAmount,
            'customer_payment_amount' => $totalCustomerPayment,
            'remaining' => $remaining,
        ]);

        return back()->with('success', 'Thêm phòng thành công');
    }

    public function updateBookingInformation(Request $request, Booking $booking)
    {
        $validationRules = [
            'ota_name' => 'nullable|string|max:255',
            'created_at' => 'nullable|date',
            'room_payment_method' => 'nullable|string|max:255',
            'commission_fee' => 'nullable|numeric',
            'note' => 'nullable|string|max:1000',
        ];

        // Only allow total_amount editing for imported bookings
        if ($booking->is_imported) {
            $validationRules['total_amount'] = 'nullable|numeric|min:0';
        }

        $validated = $request->validate($validationRules);

        if (array_key_exists('total_amount', $validated)) {
            $validated['customer_payment_amount'] = $validated['total_amount'];
            $validated['remaining'] = $validated['total_amount'] - $booking->paid;
        }

        $booking->update($validated);

        return redirect()->back()->with('success', 'Cập nhật thông tin đặt phòng thành công.');
    }

    public function addNewCustomer(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'adults' => 'required|integer|min:0',
            'children' => 'required|integer|min:0',
            'newborn' => 'required|integer|min:0',
            'customers' => 'required|array|min:1',
            'customers.*.user_id' => 'nullable|exists:customers,id',
            'customers.*.full_name' => 'required|string|max:255',
            'customers.*.type' => 'required|string|max:255',
            'customers.*.phone' => 'nullable|string|max:50',
            'customers.*.email' => 'nullable|email|max:255',
            'customers.*.id_number' => 'nullable|string|max:50',
            'customers.*.partner_id' => 'nullable|exists:partners,id',
            'customers.*.image' => 'nullable|file|image|max:2048',
        ]);
        $booking->update([
            'adults' => $validated['adults'],
            'children' => $validated['children'],
            'newborn' => $validated['newborn'],
        ]);
        foreach ($validated['customers'] as $customerData) {
            if (!empty($customerData['user_id'])) {
                $customer = Customer::find($customerData['user_id']);
                if (!$customer) continue;
            } else {
                $customer = new Customer();
            }
            $customer->full_name = $customerData['full_name'];
            $customer->phone = $customerData['phone'] ?? null;
            $customer->email = $customerData['email'] ?? null;
            $customer->id_number = $customerData['id_number'] ?? null;
            $customer->partner_id = $customerData['partner_id'] ?? null;
            $customer->type = $customerData['type'] ?? null;
            if (
                isset($customerData['image']) &&
                $customerData['image'] instanceof \Illuminate\Http\UploadedFile
            ) {
                $path = $customerData['image']->store("customers", 'public');
                $customer->image = $path;
            }
            $customer->save();
            BookingCustomer::firstOrCreate([
                'booking_id' => $booking->id,
                'customer_id' => $customer->id,
            ]);
        }
        return redirect()->back()->with('success', 'Cập nhật khách lưu trú thành công.');
    }

    public function customerCheckIn(Request $request, BookingRoom $room)
    {
        $validated = $request->validate([
            'id' => 'required|exists:customers,id',
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'note' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'id_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'type' => 'nullable|string|max:100',
            'id_type' => 'nullable|string|max:100',
            'partner_id' => 'nullable|exists:partners,id',
            'image' => 'nullable|file|image|max:2048',
        ]);
        $room->update(['room_status' => "Đã nhận phòng"]);
        $customer = Customer::find($validated['id']);

        if (!$customer) {
            return response()->json(['message' => 'Không tìm thấy khách hàng.'], 404);
        }
        $customer->fill($validated);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('customers', 'public');
            $customer->image = $path;
        }
        $customer->save();
        return redirect()->back()->with('success', 'Cập nhật nhận phòng thành công.');
    }

    public function undoCheckIn(Request $request, BookingRoom $room)
    {
        $room->update(['room_status' => "Chưa nhận phòng"]);

        return response()->json([
            'message' => 'Cập nhật nhận phòng thành công.',
        ]);
    }

    public function undoCheckOut(Booking $booking, BookingRoom $room)
    {
        $booking->update([
            'status' => 'Đã xác nhận',
        ]);

        $room->update(['room_status' => "Đã nhận phòng"]);

        return response()->json([
            'message' => 'Đã hoàn tác trả phòng thành công.',
        ]);
    }


    public function exportInvoicePdf(Booking $booking)
    {
        $booking->load(['property', 'room', 'roomUnit', 'customer', 'paymentHistories', 'bookingRooms', 'bookingRooms.room', 'bookingRooms.roomUnit', 'bookingCustomers']);
        $pdf = Pdf::loadView('invoice', compact('booking'))->setPaper('a4');
        return $pdf->stream("hoa-don-dat-phong-{$booking->id}.pdf");
    }

    public function roomUpdate(Request $request, Booking $booking, BookingRoom $room)
    {
        $this->authorize('update-dat-phong', Booking::class);
        $validated = $request->validate([
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',

            'check_in_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'check_out_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],

            'property_id' => 'required|exists:properties,id',
            'room_id' => 'required|exists:rooms,id',
            'room_unit_id' => 'required|exists:room_units,id',
            'rate_plan_id' => 'required|exists:rate_plans,id',
            'room_price_at_booking' => 'required|numeric',
            'total' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'prices' => 'required|array|min:1',
            'prices.*.date' => 'required|date',
            'prices.*.price' => 'required|numeric|min:0',
        ]);

        $room->update([
            'property_id' => $validated['property_id'],
            'room_id' => $validated['room_id'],
            'room_unit_id' => $validated['room_unit_id'],
            'rate_plan_id' => $validated['rate_plan_id'],
            'room_price_at_booking' => $validated['room_price_at_booking'],
            'check_in_date' => $validated['check_in_date'],
            'check_out_date' => $validated['check_out_date'],
            'check_in_time' => $validated['check_in_time'],
            'check_out_time' => $validated['check_out_time'],
            'note' => $validated['note'] ?? null,
            'total' => $validated['total'],
            'discount' => $validated['discount'] ?? 0,
            'nights' => \Carbon\Carbon::parse($validated['check_in_date'])->diffInDays($validated['check_out_date']),
            'price_date' => collect($validated['prices'])
                ->map(fn($p) => $p['date'] . ':' . $p['price'])
                ->implode(','),
        ]);

        $bookingRooms = $booking->bookingRooms()->get();

        $totalAmount = $bookingRooms->sum('total');

        $totalCustomerPayment = $bookingRooms->sum(function ($r) {
            return max($r->total - ($r->discount ?? 0), 0);
        });

        $paid = $booking->paid;
        $remaining = max($totalCustomerPayment - $paid, 0);

        $booking->update([
            'total_amount' => $totalAmount,
            'customer_payment_amount' => $totalCustomerPayment,
            'remaining' => $remaining,
        ]);

        return redirect()->back()->with('success', 'Cập nhật phòng thành công');
    }

    public function exportConfirmInvoicePdf(Booking $booking)
    {
        $booking->load(['property', 'customer', 'paymentHistories', 'bookingRooms', 'bookingRooms.room', 'bookingRooms.roomUnit', 'bookingCustomers']);
        $pdf = Pdf::loadView('confirm-invoice', compact('booking'))->setPaper('a4');
        return $pdf->stream("xac-nhan-dat-phong-{$booking->id}.pdf");
    }

    public function cancelBooking(Request $request, Booking $booking)
    {
        $isRefund = $request->refundOption === 'Hoàn tiền';

        if ($isRefund) {
            $request->validate([
                'paid' => ['required', 'numeric', 'min:0', function ($attribute, $value, $fail) use ($booking) {
                    if ($value > $booking->paid) {
                        $fail('Số tiền hoàn không được lớn hơn số tiền khách đã thanh toán (' . number_format($booking->paid, 0) . 'đ).');
                    }
                }],
            ]);
        }

        DB::transaction(function () use ($request, $booking, $isRefund) {

            // Lưu lại partner_group_id từ property
            $partnerGroupId = optional($booking->property)->partner_group_id;

            // 1️⃣ Cập nhật trạng thái Booking
            $booking->update([
                'status' => 'Hủy',
                'payment_status' => 'Đã thanh toán',
                'commission_fee' => 0,
                'total_amount' => 0,
                'customer_payment_amount' => 0,
                'remaining' => 0,
            ]);

            // 2️⃣ Cập nhật tất cả Booking Rooms
            foreach ($booking->bookingRooms as $room) {
                $room->room_status = 'Hủy';
                // $room->total = 0;
                // $room->discount = 0;
                $room->save();
            }

            // Sync availability
            $roomIds = $booking->bookingRooms->pluck('room_id')->unique();

            foreach ($roomIds as $roomId) {
                $room = Room::find($roomId);
                $property = $room->property;
                if ($property && $property->is_sync_enabled && $room->external_id) {
                    app(ChannexService::class)->recalculateAndSyncAvailabilityForRoom(
                        $room,
                        $booking->check_in_date,
                        $booking->check_out_date
                    );
                }
            }

            // 3️⃣ Nếu có hoàn tiền thì tạo phiếu chi
            if ($isRefund) {
                $source_business_type = $booking->payment_type === 'Hotel Collect' ? 'Booking' : 'Partner';
                $source_business_code = $source_business_type . '-' . $booking->id;

                $expense = IncomeExpense::create([
                    'date' => Carbon::now(),
                    'type' => 'expense',
                    'room_payment_method' => $booking->payment_type,
                    'payment_method' => $request->payment_method,
                    'payment_source' => '-',
                    'payment_object' => optional($booking->customer)->full_name,
                    'booking_id' => $booking->id,
                    'business_type' => 'Hủy đặt phòng',
                    'amount' => $request->paid,
                    'payment_status' => 'Đã thanh toán',
                    'source_business_type' => $source_business_type,
                    'source_business_code' => $source_business_code,
                    'created_by' => Auth::user()->name,
                    'note' => $request->note ?? null,
                    'partner_group_id' => $partnerGroupId,
                ]);

                AuditLog::create([
                    'income_expense_id' => $expense->id,
                    'action_type'       => 'confirm_payment',
                    'performed_by'      => $booking->payment_type === 'Hotel Collect' ? Auth::user()->name : 'Hệ thống',
                    'performed_at'      => now(),
                    'source_type'       => 'auto',
                ]);
            }
        });

        return redirect()->back()->with('success', 'Đã hủy đặt phòng thành công.');
    }

    public function syncBookings()
    {
        $bookings = app(ChannexService::class)->getFutureBookingsByProperty();

        Log::info('Number of future bookings:', ['count' => count($bookings)]);

        foreach ($bookings as $booking) {
            app(ChannexService::class)->handleBooking($booking, $booking['attributes']['revision_id']);
        }

        return response()->json([
            'message' => 'Có ' . count($bookings) . ' đặt phòng từ tương lai',
        ]);
    }
}
