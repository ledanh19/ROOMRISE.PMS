<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
use App\Models\Booking;
use App\Models\BookingIncomeExpense;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\Room;
use App\Models\RoomUnit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('ControlPanel/Index');
        // return Inertia::render('Dashboard/Index');
    }

    public function report(Request $request)
    {
        return Inertia::render('Dashboard/Report');
    }
    public function components(Request $request)
    {
        return Inertia::render('Dashboard/Component');
    }

    public function getChartData(Request $request)
    {
        $property = $request->get('property');
        $data = [];
        $labels = [];
        $revenueData = [];
        $newBookingData = [];
        $checkinData = [];
        $checkoutData = [];

        // Lấy dữ liệu 7 ngày gần nhất
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $displayDate = Carbon::now()->subDays($i)->format('d/m');
            $labels[] = $displayDate;

            // Doanh thu (tổng customer_payment_amount của bookings trong ngày)           
            $revenue = Booking::whereDate('check_in_date', $date)
                ->when($property, function ($q) use ($property) {
                    $q->where('property_id', $property);
                })
                ->when(RoleHelper::isPartnerScopedUser(), function ($query) {
                    $query->whereHas('property', function ($q) {
                        $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
                    });
                })
                ->sum('customer_payment_amount') / 1000000;
            $revenueData[] = round($revenue, 1);

            // Booking mới (số booking được tạo trong ngày)            
            $newBookings = Booking::whereDate('created_at', $date)
                ->when($property, function ($q) use ($property) {
                    $q->where('property_id', $property);
                })
                ->when(RoleHelper::isPartnerScopedUser(), function ($query) {
                    $query->whereHas('property', function ($q) {
                        $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
                    });
                })
                ->count();
            $newBookingData[] = $newBookings;

            // Check-in (bookings có room_status "Đã nhận phòng" trong ngày)           
            $checkins = Booking::whereDate('check_in_date', $date)
                ->when($property, function ($q) use ($property) {
                    $q->where('property_id', $property);
                })
                ->when(RoleHelper::isPartnerScopedUser(), function ($query) {
                    $query->whereHas('property', function ($q) {
                        $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
                    });
                })
                ->whereHas('bookingRooms', function ($query) {
                    $query->where('room_status', 'Đã nhận phòng');
                })
                ->count();
            $checkinData[] = $checkins;

            // Check-out (bookings có room_status "Đã trả phòng" trong ngày)
            $checkouts = Booking::whereDate('check_out_date', $date)
                ->when($property, function ($q) use ($property) {
                    $q->where('property_id', $property);
                })
                ->when(RoleHelper::isPartnerScopedUser(), function ($query) {
                    $query->whereHas('property', function ($q) {
                        $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
                    });
                })
                ->whereHas('bookingRooms', function ($query) {
                    $query->where('room_status', 'Đã trả phòng');
                })
                ->count();
            $checkoutData[] = $checkouts;
        }

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Doanh thu (triệu VNĐ)',
                    'data' => $revenueData,
                    'borderColor' => '#dc3545',
                    'backgroundColor' => 'rgba(220, 53, 69, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                ],
                [
                    'label' => 'Booking mới',
                    'data' => $newBookingData,
                    'borderColor' => '#fd7e14',
                    'backgroundColor' => 'rgba(253, 126, 20, 0.1)',
                    'tension' => 0.4,
                    'fill' => false,
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'Check-in',
                    'data' => $checkinData,
                    'borderColor' => '#28a745',
                    'backgroundColor' => 'rgba(40, 167, 69, 0.1)',
                    'tension' => 0.4,
                    'fill' => false,
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'Check-out',
                    'data' => $checkoutData,
                    'borderColor' => '#007bff',
                    'backgroundColor' => 'rgba(0, 123, 255, 0.1)',
                    'tension' => 0.4,
                    'fill' => false,
                    'yAxisID' => 'y1',
                ]
            ]
        ]);
    }

    public function getBookingDetails(Request $request)
    {
        $property = $request->get('property');
        $startDate = Carbon::now()->subDays(6)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $bookings = Booking::whereBetween('check_in_date', [$startDate, $endDate])
            ->with([
                'property',
                'customer',
                'bookingRooms.room',     // Loại phòng
                'bookingRooms.roomUnit', // Tên phòng cụ thể
            ])
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when(RoleHelper::isPartnerScopedUser(), function ($query) {
                $query->whereHas('property', function ($q) {
                    $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
                });
            })
            ->orderBy('check_in_date', 'desc')
            ->get();

        return response()->json([
            'bookings' => $bookings
        ]);
    }

    public function getDashboardStats(Request $request)
    {
        $property = $request->get('property');
        $partnerGroupId = RoleHelper::getScopedPartnerGroupId();

        $today = Carbon::now()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $nextMonth = Carbon::now()->addMonth();
        $thisMonth = Carbon::now();

        // Base query cho hôm nay
        $todayQuery = Booking::whereDate('check_in_date', $today)
            ->where('status', '!=', 'Hủy')
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            });


        $yesterdayQuery = Booking::whereDate('check_in_date', $yesterday)
            ->where('status', '!=', 'Hủy')
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            });

        // Doanh thu hôm nay và hôm qua
        $todayRevenue = $todayQuery->sum('customer_payment_amount');
        $yesterdayRevenue = $yesterdayQuery->sum('customer_payment_amount');
        $revenueChange = $yesterdayRevenue > 0
            ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1)
            : 0;

        // Doanh thu ĐÃ THU hôm nay      
        $bookingIdsToday = Booking::whereDate('check_in_date', today())
            ->where('status', '!=', 'Hủy')
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            })
            ->pluck('id');

        $directCollected = IncomeExpense::where('type', 'income')
            ->whereIn('booking_id', $bookingIdsToday)
            ->sum('amount');

        $partnerCollected = BookingIncomeExpense::whereIn('booking_id', $bookingIdsToday)
            ->sum('amount');

        $totalCollectedToday = $directCollected + $partnerCollected;


        // Doanh thu dự kiến tháng sau
        $nextMonthRevenue = Booking::whereYear('check_in_date', $nextMonth->year)
            ->whereMonth('check_in_date', $nextMonth->month)
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            })
            ->sum('customer_payment_amount');

        $thisMonthRevenue = Booking::whereYear('check_in_date', $thisMonth->year)
            ->whereMonth('check_in_date', $thisMonth->month)
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            })
            ->sum('customer_payment_amount');

        $nextMonthIncrease = $thisMonthRevenue > 0 ? round((($nextMonthRevenue - $thisMonthRevenue) / $thisMonthRevenue) * 100, 1) : 0;


        // Tỷ lệ lấp đầy
        $totalRooms = RoomUnit::when(
            $partnerGroupId,
            fn($q) =>
            $q->whereHas(
                'room.property',
                fn($sub) =>
                $sub->where('partner_group_id', $partnerGroupId)
            )
        )
            ->when(
                $property,
                fn($q) =>
                $q->whereHas(
                    'room.property',
                    fn($sub) =>
                    $sub->where('property_id', $property)  // <-- đúng
                )
            )
            ->count();


        $occupiedRooms = Booking::whereDate('check_in_date', '<=', $today)
            ->whereDate('check_out_date', '>', $today)
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->whereHas('bookingRooms')
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            })

            ->count();

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        $yesterdayOccupied = Booking::whereDate('check_in_date', '<=', $yesterday)
            ->whereDate('check_out_date', '>', $yesterday)
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->whereHas('bookingRooms')
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            })

            ->count();

        $yesterdayOccupancy = $totalRooms > 0 ? round(($yesterdayOccupied / $totalRooms) * 100) : 0;
        $occupancyChange = $occupancyRate - $yesterdayOccupancy;


        // Booking mới       
        $newBookings = $todayQuery->count();
        $yesterdayBookings = $yesterdayQuery->count();
        $newBookingsChange = $newBookings - $yesterdayBookings;

        // Check-in/out
        $todayCheckinsQuery = Booking::whereDate('check_in_date', $today)
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('bookingRooms.roomUnit.room.property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            })
            ->whereHas('bookingRooms', function ($query) {
                $query->where('room_status', 'Đã nhận phòng');
            });
        $todayCheckins = $todayCheckinsQuery->count();

        $yesterdayCheckins = Booking::whereDate('check_in_date', $yesterday)
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('bookingRooms.roomUnit.room.property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            })

            ->whereHas('bookingRooms', function ($query) {
                $query->where('room_status', 'Đã nhận phòng');
            })->count();
        $checkinChange = $todayCheckins - $yesterdayCheckins;

        $todayCheckouts = Booking::whereDate('check_out_date', $today)
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('bookingRooms.roomUnit.room.property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            })

            ->whereHas('bookingRooms', function ($query) {
                $query->where('room_status', 'Đã trả phòng');
            })->count();
        $yesterdayCheckouts = Booking::whereDate('check_out_date', $yesterday)
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('bookingRooms.roomUnit.room.property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            })

            ->whereHas('bookingRooms', function ($query) {
                $query->where('room_status', 'Đã trả phòng');
            })->count();
        $checkoutChange = $todayCheckouts - $yesterdayCheckouts;

        // Chưa gán phòng       
        $unassignedRooms = Booking::where('status', '!=', 'Hủy')
            ->when($property, fn($q) => $q->where('property_id', $property))
            ->when($partnerGroupId, fn($q) => $q->whereHas('bookingRooms.roomUnit.room.property', fn($sub) => $sub->where('partner_group_id', $partnerGroupId)))
            ->where(function ($q) {
                $q->whereDoesntHave('bookingRooms') // chưa có bookingRooms
                    ->orWhereHas('bookingRooms', fn($sub) => $sub->whereNull('room_unit_id')); // có bookingRooms nhưng room_unit_id null
            })
            ->count();


        // Chưa thu tiền - booking có số tiền chưa thu đủ        
        $unpaidBookings = Booking::where('status', '!=', 'Hủy')
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('bookingRooms.roomUnit.room.property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            })

            ->whereDate('check_in_date', $today)
            ->whereRaw('customer_payment_amount > (
                COALESCE((
                    SELECT SUM(amount) FROM income_expenses 
                    WHERE booking_id = bookings.id AND type = "income"
                ), 0) +
                COALESCE((
                    SELECT SUM(booking_income_expenses.amount) 
                    FROM booking_income_expenses 
                    JOIN income_expenses ON income_expenses.id = booking_income_expenses.income_expense_id
                    WHERE booking_income_expenses.booking_id = bookings.id 
                    AND income_expenses.type = "income"
                ), 0)
            )')
            ->count();

        $unpaidAmount = Booking::where('status', '!=', 'Hủy')
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('bookingRooms.roomUnit.room.property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            })

            ->whereDate('check_in_date', $today)
            ->whereRaw('customer_payment_amount > (
                COALESCE((
                    SELECT SUM(amount) FROM income_expenses 
                    WHERE booking_id = bookings.id AND type = "income"
                ), 0) +
                COALESCE((
                    SELECT SUM(booking_income_expenses.amount) 
                    FROM booking_income_expenses 
                    JOIN income_expenses ON income_expenses.id = booking_income_expenses.income_expense_id
                    WHERE booking_income_expenses.booking_id = bookings.id 
                    AND income_expenses.type = "income"
                ), 0)
            )')
            ->sum(DB::raw('customer_payment_amount - (
                COALESCE((
                    SELECT SUM(amount) FROM income_expenses 
                    WHERE booking_id = bookings.id AND type = "income"
                ), 0) +
                COALESCE((
                    SELECT SUM(booking_income_expenses.amount) 
                    FROM booking_income_expenses 
                    JOIN income_expenses ON income_expenses.id = booking_income_expenses.income_expense_id
                    WHERE booking_income_expenses.booking_id = bookings.id 
                    AND income_expenses.type = "income"
                ), 0)
            )'));


        $yesterdayUnpaidBookings = Booking::where('status', '!=', 'Hủy')
            ->when($property, function ($q) use ($property) {
                $q->where('property_id', $property);
            })
            ->when($partnerGroupId, function ($q) use ($partnerGroupId) {
                $q->whereHas('bookingRooms.roomUnit.room.property', function ($sub) use ($partnerGroupId) {
                    $sub->where('partner_group_id', $partnerGroupId);
                });
            })

            ->whereDate('check_in_date', $yesterday)
            ->whereRaw('customer_payment_amount > (
                COALESCE((
                    SELECT SUM(amount) FROM income_expenses 
                    WHERE booking_id = bookings.id AND type = "income"
                ), 0) +
                COALESCE((
                    SELECT SUM(booking_income_expenses.amount) 
                    FROM booking_income_expenses 
                    JOIN income_expenses ON income_expenses.id = booking_income_expenses.income_expense_id
                    WHERE booking_income_expenses.booking_id = bookings.id 
                    AND income_expenses.type = "income"
                ), 0)
            )')
            ->count();

        $unpaidChange = $unpaidBookings - $yesterdayUnpaidBookings;

        return response()->json([
            'todayRevenue' => $todayRevenue,
            'todayCollected' => $totalCollectedToday,
            'revenueChange' => $revenueChange,
            'nextMonthRevenue' => $nextMonthRevenue,
            'nextMonthIncrease' => $nextMonthIncrease,
            'occupancyRate' => $occupancyRate,
            'occupiedRooms' => $occupiedRooms,
            'totalRooms' => $totalRooms,
            'occupancyChange' => $occupancyChange,
            'newBookings' => $newBookings,
            'newBookingsChange' => $newBookingsChange,
            'todayCheckins' => $todayCheckins,
            'checkinChange' => $checkinChange,
            'todayCheckouts' => $todayCheckouts,
            'checkoutChange' => $checkoutChange,
            'unassignedRooms' => $unassignedRooms,
            'unassignedChange' => -1,
            'unpaidBookings' => $unpaidBookings,
            'unpaidAmount' => $unpaidAmount,
            'unpaidChange' => $unpaidChange,
        ]);
    }

    public function getBookingSources(Request $request)
    {
        $timeRange = $request->get('timeRange');
        $property = $request->get('property');
        $area = $request->get('area');

        $dates = null;
        if (is_string($timeRange) && str_contains($timeRange, ' to ')) {
            [$start, $end] = explode(' to ', $timeRange);
            $dates = [
                'start' => Carbon::parse($start),
                'end' => Carbon::parse($end),
            ];
        } elseif ($timeRange) {
            $date = Carbon::parse($timeRange);
            $dates = [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ];
        }

        $query = Booking::query()
            ->select('ota_name', DB::raw('COUNT(*) as count'))
            ->whereNotNull('ota_name')
            ->where('status', '!=', 'Hủy')
            ->with('bookingRooms');

        $query->when(RoleHelper::isPartnerScopedUser(), function ($q) {
            $q->whereHas('property', function ($sub) {
                $sub->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
            });
        });
        // Join với properties nếu lọc theo area
        if ($area) {
            $query->whereHas('property', function ($q) use ($area) {
                $q->where('area_id', $area);
            });
        }

        if ($property) {
            $query->where('property_id', $property);
        }

        // Filter theo ngày dựa vào bookingRooms
        if ($dates) {
            $query->whereHas('bookingRooms', function ($q) use ($dates) {
                $q->whereBetween('check_in_date', [$dates['start'], $dates['end']]);
            });
        }

        $sources = $query->groupBy('ota_name')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'labels' => $sources->pluck('ota_name'),
            'data' => $sources->pluck('count')
        ]);
    }

    public function getCustomerLocations(Request $request)
    {
        $timeRange = $request->get('timeRange');
        $property = $request->get('property');
        $area = $request->get('area');

        $dates = null;
        if (is_string($timeRange) && str_contains($timeRange, ' to ')) {
            [$start, $end] = explode(' to ', $timeRange);
            $dates = [
                'start' => Carbon::parse($start),
                'end' => Carbon::parse($end),
            ];
        } elseif ($timeRange) {
            $date = Carbon::parse($timeRange);
            $dates = [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ];
        }

        $query = DB::table('bookings')
            ->join('customers', 'bookings.customer_id', '=', 'customers.id')
            ->join('booking_rooms', 'booking_rooms.booking_id', '=', 'bookings.id')
            ->whereNotNull('customers.city');

        $query->join('properties', 'bookings.property_id', '=', 'properties.id');
        if (RoleHelper::isPartnerScopedUser()) {
            $query->where('properties.partner_group_id', RoleHelper::getScopedPartnerGroupId());
        }

        if ($dates) {
            $query->whereBetween('booking_rooms.check_in_date', [$dates['start'], $dates['end']]);
        }

        if ($property) {
            $query->where('bookings.property_id', $property);
        }

        if ($area) {
            $query->join('properties', 'bookings.property_id', '=', 'properties.id')
                ->where('properties.area_id', $area);
        }

        $areas = $query->select('customers.city', DB::raw('COUNT(*) as count'))
            ->groupBy('customers.city')
            ->orderByDesc('count')
            ->get();

        $labels = $areas->pluck('city')->toArray();
        $data = $areas->pluck('count')->toArray();

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }


    public function getBookingByArea(Request $request)
    {
        $timeRange = $request->get('timeRange');
        $property = $request->get('property');
        $area = $request->get('area');

        $dates = null;
        if (is_string($timeRange) && str_contains($timeRange, ' to ')) {
            [$start, $end] = explode(' to ', $timeRange);
            $dates = [
                'start' => Carbon::parse($start),
                'end' => Carbon::parse($end),
            ];
        } elseif ($timeRange) {
            $date = Carbon::parse($timeRange);
            $dates = [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ];
        }

        $query = DB::table('bookings')
            ->join('customers', 'bookings.customer_id', '=', 'customers.id');
        $query->join('properties', 'bookings.property_id', '=', 'properties.id');
        if (RoleHelper::isPartnerScopedUser()) {
            $query->where('properties.partner_group_id', RoleHelper::getScopedPartnerGroupId());
        }

        if ($dates) {
            $query->whereBetween('check_in_date', [$dates['start'], $dates['end']]);
        }
        // Filter theo property
        if ($property) {
            $query->where('bookings.property_id', $property);
        }

        // Filter theo area
        if ($area) {
            $query->join('properties', 'bookings.property_id', '=', 'properties.id')
                ->where('properties.area_id', $area);
        }

        $areas = $query->select('customers.province', DB::raw('COUNT(*) as count'))
            ->whereNotNull('customers.province')
            ->groupBy('customers.province')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        $labels = $areas->pluck('province')->toArray();
        $data = $areas->pluck('count')->toArray();

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }

    public function getBookingBySource(Request $request)
    {
        $timeRange = $request->get('timeRange');
        $property = $request->get('property');
        $area = $request->get('area');

        $dates = null;
        if (is_string($timeRange) && str_contains($timeRange, ' to ')) {
            [$start, $end] = explode(' to ', $timeRange);
            $dates = [
                'start' => Carbon::parse($start)->startOfDay(),
                'end' => Carbon::parse($end)->endOfDay(),
            ];
        } elseif ($timeRange) {
            $date = Carbon::parse($timeRange);
            $dates = [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ];
        }

        $query = Booking::with(['customer', 'bookingRooms.roomUnit', 'bookingRooms.room', 'property'])
            ->where('status', '!=', 'Hủy')
            ->whereHas('bookingRooms', function ($q) use ($dates) {
                if ($dates) {
                    $q->whereBetween('check_in_date', [$dates['start'], $dates['end']]);
                }
            });

        // ✅ Lọc theo property
        if ($property) {
            $query->where('property_id', $property);
        }

        // ✅ Lọc theo area
        if ($area) {
            $query->whereHas('property', function ($q) use ($area) {
                $q->where('area_id', $area);
            });
        }

        // ✅ Lọc theo partner group id nếu là Partner Scoped User
        if (RoleHelper::isPartnerScopedUser()) {
            $query->whereHas('property', function ($q) {
                $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
            });
        }

        $bookings = $query->orderBy('ota_name', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $bookings,
            'total' => $bookings->count(),
        ]);
    }

    public function getProperties()
    {
        $query = DB::table('properties')
            ->select('id', 'name')
            ->where('is_active', 1);

        if (RoleHelper::isPartnerScopedUser()) {
            $query->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
        }

        $properties = $query->orderBy('name')->get();

        return response()->json($properties);
    }


    public function getCustomerDetails(Request $request)
    {
        $timeRange = $request->get('timeRange');
        $property = $request->get('property');
        $area = $request->get('area');

        $dates = null;
        if (is_string($timeRange) && str_contains($timeRange, ' to ')) {
            [$start, $end] = explode(' to ', $timeRange);
            $dates = [
                'start' => Carbon::parse($start)->startOfDay(),
                'end' => Carbon::parse($end)->endOfDay(),
            ];
        } elseif ($timeRange) {
            $date = Carbon::parse($timeRange);
            $dates = [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ];
        }

        try {
            $query = Customer::with(['bookings.bookingRooms'])
                ->whereHas('bookings.bookingRooms', function ($q) use ($area, $dates) {
                    if ($area) {
                        $q->whereHas('booking.property', function ($q2) use ($area) {
                            $q2->where('area_id', $area);
                        });
                    }

                    if ($dates) {
                        $q->whereBetween('check_in_date', [$dates['start'], $dates['end']]);
                    }
                })
                ->whereHas('bookings', function ($q) use ($property) {
                    if ($property) {
                        $q->where('property_id', $property);
                    }
                });

            // ✅ Thêm điều kiện lọc partner_group_id cho Partner Admin
            if (RoleHelper::isPartnerScopedUser()) {
                $query->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
            }

            $customers = $query->get()
                ->filter(fn($customer) => $customer->bookings->count() > 0)
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'full_name' => $customer->full_name,
                        'phone' => $customer->phone,
                        'city' => $customer->city,
                        'country' => $customer->country,
                        'bookings_count' => $customer->bookings->count(),
                    ];
                });

            return response()->json([
                'data' => $customers->values()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Lỗi khi lấy chi tiết khách hàng'], 500);
        }
    }

    public function getBookingsNeedProcessing(Request $request)
    {
        $timeRange = $request->get('timeRange');
        $property = $request->get('property');
        $area = $request->get('area');

        $dates = null;
        if (is_string($timeRange) && str_contains($timeRange, ' to ')) {
            [$start, $end] = explode(' to ', $timeRange);
            $dates = [
                'start' => Carbon::parse($start)->startOfDay(),
                'end' => Carbon::parse($end)->endOfDay(),
            ];
        } elseif ($timeRange) {
            $date = Carbon::parse($timeRange);
            $dates = [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ];
        }

        try {
            $query = Booking::with(['customer', 'bookingRooms.roomUnit', 'bookingRooms.room', 'property'])
                ->where('status', '!=', 'Hủy')
                ->whereHas('bookingRooms', function ($q) use ($dates) {
                    if ($dates) {
                        $q->whereBetween('check_in_date', [$dates['start'], $dates['end']]);
                    }
                });

            if ($property) {
                $query->where('property_id', $property);
            }

            if ($area) {
                $query->whereHas('property', function ($q) use ($area) {
                    $q->where('area_id', $area);
                });
            }

            // ✅ Thêm điều kiện partner_group_id cho Partner Admin
            if (RoleHelper::isPartnerScopedUser()) {
                $query->whereHas('property', function ($q) {
                    $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
                });
            }

            $bookings = $query->get()->filter(function ($booking) {
                if ($booking->bookingRooms->isEmpty()) {
                    $booking->issue_type = 'Chưa gán phòng';
                    return true;
                }

                if ($booking->payment_status !== 'Đã thanh toán') {
                    $booking->issue_type = 'Chưa thu tiền';
                    return true;
                }

                return false;
            })->values();

            return response()->json([
                'data' => $bookings
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Lỗi khi lấy booking cần xử lý'], 500);
        }
    }


    public function getCurrentGuests(Request $request)
    {
        $timeRange = $request->get('timeRange');
        $property = $request->get('property');
        $area = $request->get('area');

        $dates = null;

        if (is_string($timeRange) && str_contains($timeRange, ' to ')) {
            [$start, $end] = explode(' to ', $timeRange);
            $dates = [
                'start' => Carbon::parse($start)->startOfDay(),
                'end' => Carbon::parse($end)->endOfDay(),
            ];
        } elseif ($timeRange) {
            $date = Carbon::parse($timeRange);
            $dates = [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ];
        }

        try {
            $query = Booking::with(['customer', 'bookingRooms.roomUnit', 'bookingRooms.room', 'property'])
                ->where('status', '!=', 'Hủy')
                ->whereHas('bookingRooms', function ($q) use ($dates) {
                    $q->where('room_status', 'Đã nhận phòng');

                    if ($dates) {
                        $q->whereBetween('check_in_date', [$dates['start'], $dates['end']]);
                    }
                });

            if ($property) {
                $query->where('property_id', $property);
            }

            if ($area) {
                $query->whereHas('property', function ($q) use ($area) {
                    $q->where('area_id', $area);
                });
            }

            // ✅ Thêm điều kiện Partner Admin
            if (RoleHelper::isPartnerScopedUser()) {
                $query->whereHas('property', function ($q) {
                    $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
                });
            }

            $now = now();
            $guests = $query->get()->map(function ($booking) use ($now) {
                $checkoutDate = Carbon::parse($booking->check_out_date);

                if ($checkoutDate->isSameDay($now)) {
                    $booking->status = 'Check-out hôm nay';
                } else {
                    $booking->status = 'Đang lưu trú';
                }

                $booking->room_info = $booking->bookingRooms
                    ->map(fn($room) => $room->roomUnit->name ?? 'N/A')
                    ->join(', ');

                return $booking;
            });

            return response()->json(['data' => $guests]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Lỗi khi lấy khách đang lưu trú'], 500);
        }
    }


    public function getRoomStatusByType(Request $request)
    {
        $timeRange = $request->get('timeRange');
        $property = $request->get('property');
        $area = $request->get('area');

        $dates = null;
        if (is_string($timeRange) && str_contains($timeRange, ' to ')) {
            [$start, $end] = explode(' to ', $timeRange);
            $dates = [
                'start' => Carbon::parse($start)->startOfDay(),
                'end' => Carbon::parse($end)->endOfDay(),
            ];
        } elseif ($timeRange) {
            $date = Carbon::parse($timeRange);
            $dates = [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ];
        }

        // Base query lấy tất cả room units có booking trong khoảng
        $bookedUnitIds = BookingRoom::query()
            ->whereHas('booking', function ($q) use ($dates) {
                $q->where('status', '!=', 'Hủy');

                if ($dates) {
                    $q->where('check_in_date', '<=', $dates['end'])
                        ->where('check_out_date', '>=', $dates['start']);
                }

                // Thêm lọc Partner Group ở đây
                if (RoleHelper::isPartnerScopedUser()) {
                    $q->whereHas('property', function ($q2) {
                        $q2->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
                    });
                }
            })
            ->pluck('room_unit_id')
            ->unique();


        $rooms = Room::with(['roomUnits', 'property'])
            ->when($property, fn($q) => $q->where('property_id', $property))
            ->when($area, fn($q) => $q->whereHas('property', fn($q2) => $q2->where('area_id', $area)))
            ->when(RoleHelper::isPartnerScopedUser(), function ($q) {
                $q->whereHas('property', function ($q2) {
                    $q2->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
                });
            })
            ->get();

        $data = $rooms->map(function ($room) use ($bookedUnitIds) {
            $totalUnits = $room->roomUnits->count();
            $bookedUnits = $room->roomUnits->whereIn('id', $bookedUnitIds)->count();
            $availableUnits = $totalUnits - $bookedUnits;

            return [
                'id' => $room->id,
                'room_name' => $room->name,
                'total' => $totalUnits,
                'booked' => $bookedUnits,
                'available' => $availableUnits,
                'percentage' => $totalUnits > 0 ? round($bookedUnits / $totalUnits * 100) : 0,
            ];
        });

        return response()->json(['data' => $data]);
    }


    public function getRoomDetails(Request $request)
    {
        $roomId = $request->get('room_id');
        $property = $request->get('property');
        $area = $request->get('area');
        $timeRange = $request->get('timeRange');

        $dates = null;
        if (is_string($timeRange) && str_contains($timeRange, ' to ')) {
            [$start, $end] = explode(' to ', $timeRange);
            $dates = [
                'start' => Carbon::parse($start)->startOfDay(),
                'end' => Carbon::parse($end)->endOfDay(),
            ];
        } elseif ($timeRange) {
            $date = Carbon::parse($timeRange);
            $dates = [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ];
        }

        try {
            $roomUnits = RoomUnit::with('room')
                ->whereHas('room', function ($q) use ($roomId, $property, $area) {
                    if ($roomId) $q->where('id', $roomId);
                    if ($property) $q->where('property_id', $property);
                    if ($area) {
                        $q->whereHas('property', fn($q2) => $q2->where('area_id', $area));
                    }
                    if (RoleHelper::isPartnerScopedUser()) {
                        $q->whereHas('property', fn($q3) => $q3->where('partner_group_id', RoleHelper::getScopedPartnerGroupId()));
                    }
                })
                ->get();

            $total = $roomUnits->count();

            // Lấy tất cả unit đã được đặt
            $bookedUnitIds = BookingRoom::query()
                ->whereHas('booking', function ($q) use ($dates) {
                    $q->where('status', '!=', 'Hủy');
                    if ($dates) {
                        $q->where('check_in_date', '<=', $dates['end'])
                            ->where('check_out_date', '>=', $dates['start']);
                    }
                })
                ->pluck('room_unit_id')
                ->unique();

            $used = $roomUnits->whereIn('id', $bookedUnitIds)->count();
            $available = $total - $used;
            $emptyRooms = $roomUnits->whereNotIn('id', $bookedUnitIds)->map(fn($unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
            ])->values();

            return response()->json([
                'data' => [
                    'total' => $total,
                    'used' => $used,
                    'available' => $available,
                    'percent' => $total > 0 ? round($used / $total * 100) : 0,
                    'empty_rooms' => $emptyRooms,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi getRoomDetails: ' . $e->getMessage());
            return response()->json(['error' => 'Lỗi khi lấy chi tiết phòng'], 500);
        }
    }

    public function dashboardExecutive()
    {
        return Inertia::render('Dashboard/DashboardExecutive/Index');
    }

    public function dashboardBooking()
    {
        return Inertia::render('Dashboard/DashboardBooking/Index');
    }

    public function dashboardRevenue()
    {
        return Inertia::render('Dashboard/DashboardRevenue/Index');
    }

    public function dashboardPerformance()
    {
        return Inertia::render('Dashboard/DashboardPerformance/Index');
    }
}
