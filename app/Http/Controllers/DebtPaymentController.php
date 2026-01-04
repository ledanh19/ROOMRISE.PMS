<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingIncomeExpense;
use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\Invoice;
use App\Models\InvoiceHistories;
use App\Models\Partner;
use App\Models\PaymentHistory;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DebtPaymentController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewCongNo', IncomeExpense::class);
        $filters = $request->only(['range_date', 'partner_id', 'type', 'debtor_partner', 'status', 'paginate', 'search', 'property_id']);
        $partner_id = $filters['partner_id'] ?? null;
        $range_date = $filters['range_date'] ?? null;
        $type = $filters['type'] ?? null;
        $status = $filters['status'] ?? null;
        $debtor_partner = $filters['debtor_partner'] ?? null;
        $property_id = $filters['property_id'] ?? null;
        $partner_group_id = Property::where('id', $property_id)->value('partner_group_id');

        $paginate = is_numeric($filters['paginate'] ?? null) && $filters['paginate'] > 0 ? $filters['paginate'] : 10;

        $startDate = $endDate = null;
        if ($range_date && str_contains($range_date, ' to ')) {
            [$startDate, $endDate] = explode(' to ', $range_date);
        }

        $query = Partner::where('status', 'Hoạt động')
            ->when($partner_id, fn($q) => $q->where('id', $partner_id))
            ->when(RoleHelper::isPartnerScopedUser(), function ($q) {
                $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
            })
            ->when($partner_group_id && $property_id, function ($q) use ($partner_group_id) {
                $q->where('partner_group_id', $partner_group_id);
            })
            ->when($type, fn($q) => $q->where('type', $type))
            ->with([
                'partnerGroup',
                'customers.bookings' => function ($q) use ($startDate, $endDate) {
                    if ($startDate && $endDate) {
                        $q->whereBetween('check_in_date', [$startDate, $endDate]);
                    }
                    $q->where('payment_type', '!=', 'OTA Collect') // Loại hoàn toàn OTA Collect
                        ->with([
                            'property',
                            'bookingRooms.room',
                            'bookingRooms.roomUnit',
                            'partnerIncomeExpenses',
                            'incomeExpenses'
                        ]);
                },
            ])
            ->orderBy('type', 'asc');

        $partners = $query->get()->map(function ($partner) use ($status, $debtor_partner) {
            $totalRevenue = $this->totalRevenue($partner);
            $totalCommission = $this->totalCommission($partner);
            $totalProcessed = $this->totalProcessed($partner);
            $netDebtData = $this->calculateNetDebt($partner);
            $netDebt = $netDebtData['netDebt'];

            $partnerBookings = $partner->customers->flatMap(fn($customer) => $customer->bookings);
            $totalBookings = $partnerBookings->count();
            $bookingsWithExpense = $partnerBookings->filter(fn($b) => !is_null($b->income_expense_id))->count();

            $debtStatus = $this->getDebtStatus($netDebt, $totalRevenue, $totalBookings, $bookingsWithExpense);

            if ($status && $status !== "Tất cả trạng thái" && $debtStatus !== $status) {
                return null;
            }

            if ($debtor_partner === 'true' && $netDebt <= 0) {
                return null;
            }

            $filtered = 0;
            $total = 0;

            foreach ($partner->customers as $customer) {
                $filtered += $customer->bookings->count(); // Đã loại OTA Collect từ trước
                $total += $customer->bookings->count();    // Tổng = filtered
            }

            $partner->total_revenue = $totalRevenue;
            $partner->total_commission = $totalCommission;
            $partner->total_processed = $totalProcessed;
            $partner->total_net_debt = $netDebt;
            $partner->debt_status = $debtStatus;
            $partner->total_receivable = $netDebt;
            $partner->filtered_bookings = $filtered;
            $partner->total_bookings = $total;

            return $partner;
        })->filter()->values();


        $debtorsCount = $partners->filter(function ($partner) {
            return $partner->total_net_debt > 0;
        })->count();

        $totalPartners = Partner::where('status', 'Hoạt động')
            ->when($partner_group_id && $property_id, function ($q) use ($partner_group_id) {
                $q->where('partner_group_id', $partner_group_id);
            })
            ->when(RoleHelper::isPartnerScopedUser(), function ($q) {
                $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
            })->count();
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $partners->forPage($request->page ?? 1, $paginate),
            $partners->count(),
            $paginate,
            $request->page ?? 1,
            ['path' => url()->current()]
        );


        return Inertia::render('DebtPayment/Index', [
            'filters' => $filters,
            'data' => $paginated,
            'totalPartners' => $totalPartners,
            'debtorsCount' => $debtorsCount,
        ]);
    }

    // Tương đương totalRevenue
    private function totalRevenue($partner)
    {
        return $partner->customers->reduce(function ($sum, $customer) {
            return $sum + $customer->bookings->reduce(function ($s, $booking) {
                return $s + (float)($booking->customer_payment_amount ?? 0);
            }, 0);
        }, 0) ?? 0;
    }

    // Tương đương totalCommission
    private function totalCommission($partner)
    {
        return $partner->customers->reduce(function ($sum, $customer) {
            return $sum + $customer->bookings->reduce(function ($s, $booking) {
                return $s + (float)($booking->commission_fee ?? 0);
            }, 0);
        }, 0) ?? 0;
    }

    // Tương đương totalProcessed    
    private function totalProcessed($partner)
    {
        $total = 0;
        foreach ($partner->customers as $customer) {
            foreach ($customer->bookings as $booking) {
                // Thu/chi trực tiếp từ booking
                foreach ($booking->incomeExpenses as $ie) {
                    $total += (float)($ie->amount ?? 0);
                }
                // Thu/chi qua bảng trung gian
                foreach ($booking->partnerIncomeExpenses as $ie) {
                    $total += (float)($ie->amount ?? 0);
                }
            }
        }
        return $total;
    }


    // Tương đương calculateNetDebt
    private function calculateNetDebt($partner)
    {
        $partnerCollected = $partner->customers->reduce(function ($sum, $customer) {
            return $sum + $customer->bookings->reduce(function ($s, $booking) {
                return $booking->payment_type === 'Partner Collect'
                    ? $s + (float)($booking->customer_payment_amount ?? 0)
                    : $s;
            }, 0);
        }, 0) ?? 0;
        $totalRevenue = $this->totalRevenue($partner);
        $totalCommission = $this->totalCommission($partner);
        $totalProcessed = $this->totalProcessed($partner);
        return [
            'partnerCollected' => $partnerCollected,
            'totalCommission' => $totalCommission,
            'netDebt' => $totalRevenue - $totalCommission - $totalProcessed,
        ];
    }

    // Tương đương totalReceivableAfterCommission
    private function totalReceivableAfterCommission($partner)
    {
        return $this->calculateNetDebt($partner)['netDebt'];
    }
    private function getDebtStatus($netDebt, $totalRevenue, $totalBookings = 0, $bookingsWithExpense = 0)
    {
        if ($totalRevenue === 0) return "Không có công nợ";

        // Nếu tất cả bookings đã có income_expense_id => Đã thanh toán
        if ($totalBookings > 0 && $totalBookings === $bookingsWithExpense) {
            return "Đã thanh toán";
        }
        Log::info("Net Debt: $netDebt");
        if ($netDebt == 0) return "Đã thanh toán";
        if ($netDebt < 0) return "Cần trả";
        if ($netDebt > 0) return "Còn nợ";

        return "Không xác định";
    }

    public function getPartners()
    {
        $partners = Partner::where('status', 'Hoạt động')
            ->when(RoleHelper::isPartnerScopedUser(), function ($q) {
                $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
            })
            ->get()
            ->map(function ($partner) {
                return [
                    'name' => $partner->name,
                    'id' => $partner->id,
                ];
            });
        return response()->json($partners);
    }

    public function getRemainings(Request $request)
    {
        $ids = $request->input('ids');

        if (!is_array($ids) || empty($ids)) {
            return response()->json([]);
        }

        $bookings = Booking::whereIn('id', $ids)->get();

        $totalRemaining = $bookings->sum('remaining');

        $remainingList = $bookings->map(function ($b) {
            return [
                'id' => $b->id,
                'remaining' => $b->remaining,
            ];
        });

        return response()->json($totalRemaining);
    }

    public function storeRemainings(Request $request)
    {

        $request->validate([
            'partner_id' => 'required|not_in:0|exists:partners,id',
            'total_paid' => 'required|numeric|min:1',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:bookings,id',
            'payment_method' => 'required|string',
            'issued_date' => 'required|string',
            'note' => 'nullable|string',
        ], [
            'partner_id.required' => 'Vui lòng chọn đối tác',
            'partner_id.not_in' => 'Vui lòng chọn đối tác hợp lệ',
        ]);
        $partnerId = $request->input('partner_id');
        $issuedDate = $request->input('issued_date');
        $note = $request->input('note');
        $paymentMethod = $request->input('payment_method');
        $totalPaid = $request->input('total_paid');
        $bookingIds = $request->input('ids');
        $bookings = Booking::whereIn('id', $bookingIds)->get();
        $totalRemaining = $bookings->sum('remaining');
        if ($totalRemaining == 0) {
            return redirect()->back()->withErrors([
                'total_paid' => 'Tất cả các đơn đã thanh toán đầy đủ.',
            ]);
        }
        if ($totalPaid != $totalRemaining) {
            return redirect()->back()->withErrors([
                'total_paid' => 'Số tiền thanh toán phải đúng bằng tổng số tiền còn lại: ' . number_format($totalRemaining) . 'đ',
            ]);
        }
        DB::transaction(function () use ($bookings, $totalPaid, $partnerId, $paymentMethod, $issuedDate, $note) {
            $invoice = Invoice::create([
                'total_amount' => $totalPaid,
                'partner_id' => $partnerId,
                'issued_date' => $issuedDate,
                'note' => $note,
            ]);
            foreach ($bookings as $booking) {
                if ($totalPaid <= 0 || $booking->remaining <= 0) continue;
                $remaining = $booking->remaining;
                $paidNow = min($remaining, $totalPaid);
                $booking->paymentHistories()->create([
                    'paid' => $paidNow,
                    'payment_method' => $paymentMethod,
                    'created_by' => Auth::user()->name,
                ]);
                $booking->paid += $paidNow;
                $booking->remaining -= $paidNow;
                $booking->payment_status = $booking->paid >= $booking->total_amount
                    ? 'Đã thanh toán'
                    : ($booking->paid > 0 ? 'Chờ thanh toán' : 'Chưa thanh toán');
                $booking->save();
                InvoiceHistories::create([
                    'invoice_id' => $invoice->id,
                    'booking_id' => $booking->id,
                    'total_amount' => $paidNow,
                ]);
                $totalPaid -= $paidNow;

                IncomeExpense::create([
                    'type' => 'income',
                    'payment_method' => $paymentMethod,
                    'booking_id' => $booking->id,
                    'date' => Carbon::now(),
                    'amount' => $paidNow,
                    'created_by' => Auth::user()->name,
                ]);
            }
        });
        return redirect()->back()->with('success', 'Thanh toán hàng loạt thành công.');
    }

    public function getInvoiceHistories(Request $request)
    {
        $partnerId = $request->id;
        $invoiceHistories = Invoice::with('partner.customers', 'histories.booking',)
            ->where('partner_id', $partnerId)
            ->get();
        return response()->json($invoiceHistories);
    }

    public function getDebtPaymentDetailById($id)
    {
        $histories = PaymentHistory::where('booking_id', $id)->get();
        $booking = Booking::with('customer')->findOrFail($id);
        $user = $booking->customer;
        return Inertia::render('DebtPayment/Detail', [
            'histories' => $histories,
            'user' =>  $user,
            'booking' =>  $booking,
        ]);
    }

    public function storeIncomeExpenses(Request $request)
    {
        $this->authorize('createCongNo', IncomeExpense::class);
        $request->validate([
            'partner_id'      => 'required|exists:partners,id',
            'booking_ids'     => 'required|array|min:1',
            'booking_ids.*'   => 'required|exists:bookings,id',
            'amount'          => 'required|numeric|min:0',
            'type'            => 'required|in:income,expense',
            'payment_method'  => 'required|string',
            'note'            => 'nullable|string',
        ]);

        $existingBookings = Booking::whereIn('id', $request->booking_ids)
            ->whereNotNull('income_expense_id')
            ->pluck('id');

        if ($existingBookings->isNotEmpty()) {
            return redirect()->back()->with('error', 'Không thể tạo phiếu vì các booking sau đã được đối soát: ' . $existingBookings->implode(', '));
        }

        DB::transaction(function () use ($request) {
            $partner = Partner::findOrFail($request->partner_id);

            // 1️⃣ Tạo IncomeExpense
            $incomeExpense = IncomeExpense::create([
                'date' => now(),
                'type' => $request->type,
                'room_payment_method' => 'Partner Collect',
                'payment_method' => $request->payment_method,
                'payment_source' => $partner->name,
                'payment_object' => $partner->name,
                'business_type' => 'Đối soát đối tác',
                'amount' => $request->amount,
                'payment_status' => 'Đã thanh toán',
                'source_business_type' => 'Partner',
                'created_by' => 'Hệ thống',
                'note' => $request->note ?? null,
                'partner_group_id' => $partner->partner_group_id,
            ]);

            // Tạo code phiếu
            $year = now()->year;
            $prefix = strtoupper(substr($request->type, 0, 2));
            $source = 'Partner';
            $code = sprintf('%s-%s-%s-%05d', $prefix, $source, $year, $incomeExpense->id);
            $incomeExpense->update(['source_business_code' => $code]);

            // Audit log cho phiếu thu/chi
            AuditLog::create([
                'income_expense_id' => $incomeExpense->id,
                'action_type'       => 'confirm_payment',
                'performed_by'      => 'Hệ thống',
                'performed_at'      => now(),
                'source_type'       => 'auto',
            ]);

            // 2️⃣ Gán income_expense_id cho các booking
            $allBookings = Booking::whereIn('id', $request->booking_ids)->get();
            Booking::whereIn('id', $allBookings->pluck('id'))
                ->update(['income_expense_id' => $incomeExpense->id]);

            $partnerBookings = $allBookings->where('payment_type', 'Partner Collect');

            // 3️⃣ Lưu pivot trước khi reset remaining
            $pivotData = $allBookings->map(function ($booking) use ($incomeExpense) {
                return [
                    'income_expense_id' => $incomeExpense->id,
                    'booking_id'        => $booking->id,
                    'amount'            => max(0, $booking->remaining ?? 0),
                    'type'              => 'partner',
                ];
            })->toArray();
            BookingIncomeExpense::insert($pivotData);

            // 4️⃣ AuditLog cho từng booking
            foreach ($allBookings as $booking) {
                AuditLog::create([
                    'income_expense_id' => $incomeExpense->id,
                    'booking_id'        => $booking->id,
                    'action_type'       => 'settlement',
                    'performed_by'      => 'Hệ thống',
                    'performed_at'      => now(),
                    'source_type'       => 'auto',
                ]);
            }

            // 5️⃣ Reset paid/remaining cho Partner Collect bookings
            foreach ($partnerBookings as $booking) {
                $booking->update([
                    'payment_status' => 'Đã thanh toán',
                    'paid' => $booking->customer_payment_amount,
                    'remaining' => 0,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Phiếu thu/chi đã được tạo thành công.');
    }
}
