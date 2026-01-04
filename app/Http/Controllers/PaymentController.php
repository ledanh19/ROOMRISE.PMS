<?php

namespace App\Http\Controllers;

use App\Exports\HotelExportBooking;
use App\Helpers\ResponseHelper;
use App\Helpers\RoleHelper;
use App\Http\Resources\BookingItemResource;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\PartnerGroup;
use App\Models\PaymentHistory;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

class PaymentController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewHotelCollect', IncomeExpense::class);
        $filters = $request->only([
            'range_date',
            'search',
            'date_type',
            'ota_name',
            'payment_method',
            'paginate',
            'created_by',
            'property_id',
        ]);

        $paginate = is_numeric($filters['paginate'] ?? null) ? (int) $filters['paginate'] : 10;
        $baseQuery = IncomeExpense::query()
            ->with('booking.customer', 'booking.bookingRooms.room', 'booking.bookingRooms.roomUnit', 'booking.property')
            ->whereHas('booking', function ($q) use ($filters) {
                $q->where('payment_type', 'Hotel Collect');
                if (!empty($filters['property_id'])) {
                    $q->where('property_id', $filters['property_id']);
                }
            })
            ->where('type', 'income')
            ->when(
                !empty($filters['range_date']) && str_contains($filters['range_date'], ' to '),
                function ($q) use ($filters) {
                    [$start, $end] = explode(' to ', $filters['range_date']);
                    $dateType = $filters['date_type'] ?? 'date';

                    $q->when(in_array($dateType, ['check_in_date', 'check_out_date', 'created_at']), function ($query) use ($dateType, $start, $end) {
                        $query->whereHas('booking', function ($bookingQuery) use ($dateType, $start, $end) {
                            $bookingQuery->whereDate($dateType, '>=', $start)
                                ->whereDate($dateType, '<=', $end);
                        });
                    }, function ($query) use ($start, $end) {
                        $query->whereDate('date', '>=', $start)
                            ->whereDate('date', '<=', $end);
                    });
                }
            )
            ->when(!empty($filters['ota_name']), function ($q) use ($filters) {
                $q->whereHas('booking', function ($bookingQuery) use ($filters) {
                    $bookingQuery->where('ota_name', $filters['ota_name']);
                });
            })
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where(function ($subQuery) use ($search) {
                    $subQuery->where('payment_object', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhere('created_by', 'like', "%{$search}%")
                        ->orWhereHas('booking', function ($bookingQuery) use ($search) {
                            $bookingQuery->whereRaw("COALESCE(ota_reservation_code, CAST(id AS CHAR)) LIKE ?", ["%{$search}%"])
                                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                    $customerQuery->where('full_name', 'like', "%{$search}%");
                                });
                        });
                });
            })

            ->when(!empty($filters['payment_method']), fn($q) => $q->where('payment_method', $filters['payment_method']))
            ->when(!empty($filters['created_by']), fn($q) => $q->where('created_by', 'like', "%{$filters['created_by']}%"))
            ->when(RoleHelper::isPartnerScopedUser(), function ($q) {
                $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
            })
            ->orderBy('created_at', 'desc');
        $incomeIds = $baseQuery->pluck('id');
        $totalBookings = (clone $baseQuery)->count();
        $totalIncome = (clone $baseQuery)->where('type', 'income')->sum('amount');
        $data = $baseQuery->paginate($paginate)->appends($filters);

        $usersQuery = User::select('id', 'name');
        if (RoleHelper::isPartnerScopedUser()) {
            $usersQuery->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
        }

        $users = $usersQuery->orderBy('name')->get();

        return Inertia::render('Payment/Index', [
            'filters' => $filters,
            'data' => $data,
            'totalIncome' => $totalIncome,
            'incomeIds' => $incomeIds,
            'totalBookings' => $totalBookings,
            'users' => $users,
        ]);
    }


    public function storePayment(Request $request, $id)
    {
        $request->validate([
            'paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'note' => 'nullable|string',
            'date' => 'nullable|date',
            'checkout' => 'nullable|string',
        ]);


        $booking  = Booking::findOrFail($id);
        if ($request->paid == 0 && $request->checkout === 'checkout' && $booking->payment_status !== 'Đã thanh toán') {
            return redirect()->back()->withErrors([
                'paid' => 'Không thể trả phòng khi chưa thanh toán đủ.',
            ]);
        }

        if ($request->paid > 0 && $request->paid > $booking->remaining) {
            return redirect()->back()->withErrors([
                'paid' => 'Số tiền thanh toán không được vượt quá số tiền còn lại (' . number_format($booking->remaining, 0) . 'đ).',
            ]);
        }

        DB::transaction(function () use ($booking, $request) {
            $isCheckout = $request->checkout === 'checkout';
            if ($request->paid > 0) {
                // Ghi nhận lịch sử thanh toán
                $booking->paymentHistories()->create([
                    'paid' => $request->paid,
                    'payment_method' => $request->payment_method,
                    'staff' => Auth::user()->name,
                    'note' => $request->note,
                    'payment_date' => $request->date ?? now(),
                ]);

                // Cập nhật tổng số tiền đã thanh toán
                $booking->paid += $request->paid;
                $booking->remaining = max(0, $booking->remaining - $request->paid);

                // Cập nhật trạng thái thanh toán                
                $status = match (true) {
                    $booking->remaining <= 0     => 'Đã thanh toán',
                    $booking->paid > 0           => 'Đã cọc',
                    default                      => 'Chưa thanh toán',
                };
                $booking->payment_status = $status;

                $source_business_type = $booking->payment_type === 'Hotel Collect' ? 'Booking' : 'Partner';
                $source_business_code = $source_business_type . '-' . $booking->id;
                $property = Property::find($booking->property_id);
                $partnerGroupId = $property?->partner_group_id;

                $expense = IncomeExpense::create([
                    'date' => $request->date ?? now(),
                    'type' => 'income',
                    'room_payment_method' => $booking->payment_type,
                    'payment_method' => $request->payment_method,
                    'payment_source' => '-',
                    'payment_object' => Customer::findOrFail($booking->customer_id)->full_name,
                    'booking_id' => $booking->id,
                    'business_type' => 'Đặt phòng',
                    'amount' => $request->paid,
                    'payment_status'       => 'Đã thanh toán',
                    'source_business_type' => $source_business_type,
                    'source_business_code' => $source_business_code,
                    'created_by' => Auth::user()->name,
                    'note' => $request->note,
                    'partner_group_id' => $partnerGroupId,

                ]);

                $actionType = $expense->payment_status === 'Đã thanh toán'
                    ? 'confirm_payment'
                    : 'create';
                if ($expense->payment_status === 'Đã thanh toán') {
                    $relatedExpenseIds = IncomeExpense::where('booking_id', $booking->id)->pluck('id');
                    IncomeExpense::whereIn('id', $relatedExpenseIds)->update([
                        'payment_status' => 'Đã thanh toán',
                    ]);

                    AuditLog::whereIn('income_expense_id', $relatedExpenseIds)
                        ->where('action_type', 'create')
                        ->update([
                            'action_type' => 'confirm_payment',
                            'updated_at' => $request->date ?? now(),
                        ]);
                }
                AuditLog::create([
                    'income_expense_id' => $expense->id,
                    'action_type'       => $actionType,
                    'performed_by'      => $booking->payment_type === 'Hotel Collect' ? Auth::user()->name : 'Hệ thống',
                    'performed_at'      => $request->date ?? now(),
                    'source_type'       => 'auto',
                ]);
            }

            // ====== TRƯỜNG HỢP 2 & 3: Trả phòng ======
            if ($isCheckout) {
                $booking->status = 'Hoàn thành';

                $booking->bookingRooms()->update([
                    'room_status' => 'Đã trả phòng',
                ]);
            }

            // Lưu thay đổi booking
            $booking->save();
        });

        return redirect()->back()->with('success', 'Thanh toán thành công');
    }

    public function getHistoriesData($id)
    {
        $histories = PaymentHistory::where('booking_id', $id)->get();
        $booking = Booking::with('customer')->findOrFail($id);
        $user = $booking->customer;

        return response()->json([
            'histories' => $histories,
            'user' => $user,
            'booking' => $booking,
        ]);
    }

    public function getPaymentDetailById(Booking $booking)
    {
        // $histories = PaymentHistory::where('booking_id', $booking->id)->get();       
        $booking->load('customer.partner');
        $histories = $booking->incomeExpenses()->orderBy('created_at', 'desc')->get();
        $paymentHistoriesPartner =  $booking->partnerIncomeExpenses()->orderBy('created_at', 'desc')->get();
        $user = $booking->customer;

        return Inertia::render('Payment/Detail', [
            'histories' => $histories,
            'paymentHistoriesPartner' => $paymentHistoriesPartner,
            'user' =>  $user,
            'booking' =>  $booking,
        ]);
    }

    public function exportBookings(Request $request)
    {
        $filters = $request->only([
            'range_date',
            'date_type',
            'ota_name',
            'payment_method',
            'created_by'
        ]);

        $fileName = 'hotel-collect-bookings-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new HotelExportBooking($filters), $fileName, ExcelExcel::XLSX);
    }

    public function exportBookingsSelected(Request $request)
    {
        $selectedIds = $request->input('selected_bookings', []);

        $fileName = 'hotel-collect-bookings-selected-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new HotelExportBooking($selectedIds), $fileName, ExcelExcel::XLSX);
    }
}
