<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingIncomeExpense;
use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\Partner;
use App\Models\Property;
use App\Models\Settlement;
use App\Models\SettlementBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OTAReceiptController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewOtaCollect', IncomeExpense::class);
        $partnerGroupId = RoleHelper::getScopedPartnerGroupId();

        $filters = $request->only([
            'range_date',
            'ota_name',
            'property_id',
            'status_booking',
            'customer_ids',
        ]);

        $range_date     = $filters['range_date'] ?? null;
        $ota_name    = $filters['ota_name'] ?? null;
        $property_id    = $filters['property_id'] ?? null;
        $status_booking = $filters['status_booking'] ?? null;
        $customer_ids   = $filters['customer_ids'] ?? null;

        $baseQuery = Booking::query()
            ->where('payment_type', 'OTA Collect')
            ->where('status', '!=', 'Hủy')
            ->whereNull('settlement_id')
            ->when($partnerGroupId, function ($query) use ($partnerGroupId) {
                $query->whereHas('property', fn($q) => $q->where('partner_group_id', $partnerGroupId));
            })
            ->when($range_date && str_contains($range_date, ' to '), function ($q) use ($range_date) {
                [$start, $end] = explode(' to ', $range_date);
                $q->whereDate('check_in_date', '<=', $end)
                    ->whereDate('check_out_date', '>=', $start);
            })
            ->when($customer_ids, fn($q) => $q->whereIn('customer_id', $customer_ids))
            ->when($property_id, fn($q) => $q->where('property_id', $property_id))
            ->when($ota_name, fn($q) => $q->where('ota_name', $ota_name))
            ->when($status_booking, fn($q) => $q->where('reconciliation_status', $status_booking))
            ->orderBy('created_at', 'desc');

        $bookings = $baseQuery->with(['property', 'room', 'roomUnit', 'customer.partner'])->get();

        $totalBookings     = $bookings->count();
        $totalGross        = $bookings->sum('total_amount');
        $totalNet          = $bookings->sum('net_estimate');
        $totalDifference   = $bookings->sum('difference_amount');

        $totalSettledBookings = Booking::query()
            ->where('payment_type', 'OTA Collect')
            ->whereNotNull('settlement_id')
            ->when($partnerGroupId, function ($query) use ($partnerGroupId) {
                $query->whereHas('property', fn($q) => $q->where('partner_group_id', $partnerGroupId));
            })
            ->count();

        $totalPendingBookings = $baseQuery->count();

        $properties = Property::select('id', 'name')
            ->where('is_active', 1)
            ->when($partnerGroupId, fn($q) => $q->where('partner_group_id', $partnerGroupId))
            ->get();

        return Inertia::render('OTAReceipt/Index', [
            'data'                  => $bookings,
            'filters'               => $filters,
            'properties'            => $properties,
            'totalBookings'         => $totalBookings,
            'totalGross'            => $totalGross,
            'totalNet'              => $totalNet,
            'totalDifference'       => $totalDifference,
            'totalSettledBookings'  => $totalSettledBookings,
            'totalPendingBookings'  => $totalPendingBookings,
        ]);
    }


    public function getByProperty($property)
    {
        $customers = Booking::with(['customer:id,full_name'])
            ->where('property_id', $property)
            ->get()
            ->map(function ($booking) {
                return [
                    'name' => $booking->customer?->full_name,
                    'id' => $booking->customer?->id,
                ];
            })
            ->unique('id')
            ->values();

        return response()->json($customers);
    }
    public function updatePayout(Request $request)
    {
        $booking = Booking::findOrFail($request->id);

        $payout = floatval($request->input('payout_received'));
        $estimate = floatval($booking->net_estimate);

        $booking->payout_received = $payout;
        $booking->difference_amount = $payout - $estimate;

        if ($payout == $estimate) {
            $booking->reconciliation_status = 'Khớp';
        } else {
            $booking->reconciliation_status = 'Lệch';
        }

        $booking->save();
    }

    public function updatePayoutNote(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:bookings,id',
            'note' => 'nullable|string|max:1000',
        ]);

        $booking = Booking::findOrFail($validated['id']);

        $booking->note = $validated['note'];
        $booking->save();

        return response()->json([
            'message' => 'Ghi chú đã được cập nhật.',
        ]);
    }

    public function storeSettlements(Request $request)
    {
        $this->authorize('createOtaCollect', IncomeExpense::class);
        $validated = $request->validate([
            'selected_bookings' => 'required|array|min:1',
            'selected_bookings.*' => 'exists:bookings,id',
            'expected_date' => 'required|date',
            'total_net_estimate' => 'required|numeric|min:0',
            'total_payout_received' => 'required|numeric|min:0',
            'difference_amount' => 'required|numeric',
            'ota_name' => 'required|string|max:255',
        ]);

        $bookingIds = $validated['selected_bookings'];
        $expectedDate = $validated['expected_date'];
        $totalNetFromRequest = $validated['total_net_estimate'];
        $totalPayoutFromRequest = $validated['total_payout_received'];
        $differenceFromRequest = $validated['difference_amount'];

        DB::beginTransaction();

        try {
            // Lấy bookings và validate
            $bookings = Booking::whereIn('id', $bookingIds)
                ->where('payment_type', 'OTA Collect')
                ->where('status', '!=', 'Hủy')
                ->whereNull('settlement_id')
                ->get();

            if ($bookings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có booking hợp lệ để tạo phiếu quyết toán.'
                ], 422);
            }

            if ($bookings->count() !== count($bookingIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Một số booking không hợp lệ hoặc đã được quyết toán.'
                ], 422);
            }

            // Tính toán từ database để double-check
            $totalNetFromDB = $bookings->sum('net_estimate');
            $totalPayoutFromDB = $bookings->sum('payout_received');
            $totalDifferenceFromDB = $totalPayoutFromDB - $totalNetFromDB;

            // Validate tính toán (cho phép sai số nhỏ do floating point)
            if (abs($totalNetFromDB - $totalNetFromRequest) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tổng net estimate không khớp với dữ liệu hiện tại.'
                ], 422);
            }

            // Tạo mã phiếu quyết toán
            $code = 'SET-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            // Tạo settlement record
            $settlement = Settlement::create([
                'code' => $code,
                'total_booking' => $bookings->count(),
                'total_net_estimate' => $totalNetFromRequest,
                'total_payout' => $totalPayoutFromRequest,
                'total_difference' => $differenceFromRequest,
                'status' => 'Chờ thanh toán',
                'settlement_date' => $expectedDate,
                'ota_name' => $validated['ota_name'],
                'settlement_officer' => Auth::user()->name,
                'reconciliation_status' => $differenceFromRequest == 0 ? 'Khớp' : 'Lệch',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Cập nhật settlement_id cho các bookings và tạo pivot records
            $settlementBookingData = [];
            foreach ($bookings as $booking) {
                // Cập nhật booking
                $booking->update([
                    'settlement_id' => $settlement->id,
                    'payment_status' => 'Chờ thanh toán'
                ]);

                // Chuẩn bị data cho SettlementBooking
                $settlementBookingData[] = [
                    'settlement_id' => $settlement->id,
                    'booking_id' => $booking->id,
                    'net_estimate' => $booking->net_estimate ?? 0,
                    'payout_received' => $booking->payout_received ?? 0,
                    'difference_amount' => ($booking->payout_received ?? 0) - ($booking->net_estimate ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk insert settlement_bookings
            SettlementBooking::insert($settlementBookingData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tạo phiếu quyết toán thành công.',
                'data' => [
                    'settlement_id' => $settlement->id,
                    'settlement_code' => $settlement->code,
                    'total_bookings' => $bookings->count(),
                    'total_net_estimate' => $totalNetFromRequest,
                    'total_payout' => $totalPayoutFromRequest,
                    'difference_amount' => $differenceFromRequest,
                    'status' => $settlement->status,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log error cho debugging
            Log::error('Settlement creation failed', [
                'error' => $e->getMessage(),
                'booking_ids' => $bookingIds,
                'user' => Auth::user()->name ?? 'Unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi tạo phiếu quyết toán: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function loadSettlements()
    {
        $settlements = Settlement::with('bookings')->orderBy('created_at', 'desc')->get();
        return response()->json($settlements);
    }

    public function loadSettlementBookings(Request $request)
    {
        $query = Booking::with(['settlement', 'property', 'customer.partner'])
            ->whereNotNull('settlement_id')
            ->whereHas('settlement', function ($query) {
                $query->where('status', 'Đã quyết toán');
            })
            ->orderBy('created_at', 'desc');

        if ($request->input('booking_id')) {
            $query->where('id', $request->input('booking_id'));
        }

        if ($request->input('ota_channel')) {
            $query->where('ota_channel', $request->input('ota_channel'));
        }

        if ($request->input('partner_id')) {
            $query->whereHas('customer.partner', function ($q) use ($request) {
                $q->where('id', $request->input('partner_id'));
            });
        }

        if ($request->input('start_date') && $request->input('end_date')) {
            $query->whereHas('settlement', function ($q) use ($request) {
                $q->whereBetween('created_at', [
                    $request->input('start_date'),
                    $request->input('end_date'),
                ]);
            });
        }

        $data = $query->get();

        return response()->json($data);
    }


    public function confirmSettlements(Request $request)
    {
        $this->authorize('createOtaCollect', IncomeExpense::class);
        $validated = $request->validate([
            'id' => 'required|exists:settlements,id',
            'total_payout' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
        ]);

        $settlementId = $validated['id'];
        $totalPayout = $validated['total_payout'];
        $paymentMethod = $validated['payment_method'];

        DB::beginTransaction();

        try {
            // Lấy settlement và bookings liên quan
            $settlement = Settlement::with('bookings')->findOrFail($settlementId);

            if ($settlement->status === 'Đã quyết toán') {
                return response()->json([
                    'success' => false,
                    'message' => 'Phiếu quyết toán này đã được xử lý.'
                ], 422);
            }

            $bookings = $settlement->bookings;

            if ($bookings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy booking nào trong phiếu quyết toán này.'
                ], 422);
            }

            $totalNetEstimate = $settlement->total_net_estimate;
            $difference = $totalPayout - $totalNetEstimate;
            $reconciliationStatus = $difference == 0 ? 'Khớp' : 'Lệch';

            $settlement->update([
                'status' => 'Đã quyết toán',
                'payment_method' => $paymentMethod,
                'total_payout' => $totalPayout,
                'total_difference' => $difference,
                'reconciliation_status' => $reconciliationStatus,
                'settlement_officer' => Auth::user()->name,
            ]);

            // Lấy partner_group_id từ user hiện tại
            $partnerGroupId = Auth::user()->partner_group_id;
            // Tạo IncomeExpense record
            $incomeExpense = IncomeExpense::create([
                'date' => now()->toDateString(),
                'type' => 'income',
                'room_payment_method' => 'OTA Collect',
                'payment_method' => $paymentMethod,
                'payment_source' => $settlement->ota_name,
                'payment_object' => $settlement->ota_name,
                'business_type' => 'Quyết toán OTA',
                'amount' => $totalPayout,
                'payment_status' => 'Đã thanh toán',
                'source_business_type' => 'OTA',
                'source_business_code' => $settlement->code,
                'created_by' => Auth::user()->name,
                'note' => $note ?? "Quyết toán {$settlement->ota_name} - {$bookings->count()} booking",
                'partner_group_id' => $partnerGroupId,
                'settlement_id' => $settlement->id,
            ]);

            // Tạo mã code cho IncomeExpense
            $year = now()->year;
            $month = now()->format('m');
            $code = "OTA-{$year}{$month}-" . str_pad($incomeExpense->id, 4, '0', STR_PAD_LEFT);
            $incomeExpense->update(['source_business_code' => $code]);

            // Cập nhật bookings           
            foreach ($bookings as $booking) {
                // Cập nhật booking
                $booking->update([
                    'payment_status' => 'Đã thanh toán',
                    'paid' => $booking->payout_received,
                    'remaining' => 0,
                ]);
            }
            // 3️⃣ Lưu pivot trước khi reset remaining
            $pivotData = $bookings->map(function ($booking) use ($incomeExpense) {
                return [
                    'income_expense_id' => $incomeExpense->id,
                    'booking_id'        => $booking->id,
                    'amount'            => max(0, $booking->payout_received ?? 0),
                    'type'              => 'ota',
                ];
            })->toArray();
            BookingIncomeExpense::insert($pivotData);

            // Tạo audit logs
            AuditLog::create([
                'income_expense_id' => $incomeExpense->id,
                'action_type' => 'confirm_payment',
                'performed_by' => 'Hệ thống',
                'performed_at' => now(),
                'source_type' => 'auto',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xác nhận quyết toán thành công.',

            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Settlement confirmation failed', [
                'error' => $e->getMessage(),
                'settlement_id' => $settlementId,
                'user' => Auth::user()->name ?? 'Unknown',
                'partner_group_id' => Auth::user()->partner_group_id ?? 'Unknown',
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xác nhận quyết toán: ' . $e->getMessage(),
            ], 500);
        }
    }
}
