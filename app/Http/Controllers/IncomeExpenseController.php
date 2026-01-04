<?php

namespace App\Http\Controllers;

use App\Exports\ExportIncomeExpense;
use App\Helpers\RoleHelper;
use App\Models\AuditLog;
use App\Models\IncomeExpense;
use App\Models\PartnerGroup;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

class IncomeExpenseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(IncomeExpense::class, 'incomeandexpense');
    }

    public function index(Request $request)
    {
        $type = $request->type ?? null;
        $range_date = $request->range_date;
        $category = $request->category;
        $payment = $request->payment;
        $created_by = $request->created_by;
        $paginate = is_numeric($request->paginate) ? (int) $request->paginate : 10;
        $partnerGroup = PartnerGroup::select(['id', 'name'])->get();
        $filters = $request->only([
            'search',
            'paginate',
            'range_date',
            'category',
            'payment',
            'created_by',
            'type'
        ]);

        $baseQuery = IncomeExpense::query()
            ->with(['booking.property', 'auditLogs', 'booking', 'partnerBookings', 'settlement'])
            ->where(function ($query) {
                $query->whereHas('booking', function ($q) {
                    $q->where('payment_type', '!=', 'Hotel Collect');
                })
                    ->orWhere(function ($q) {
                        $q->whereHas('booking', function ($sub) {
                            $sub->where('payment_type', 'Hotel Collect');
                        })->where('type', 'expense');
                    })
                    ->orDoesntHave('booking');
            })
            ->when($range_date && str_contains($range_date, ' to '), function ($q) use ($range_date) {
                [$start, $end] = explode(' to ', $range_date);
                $q->whereBetween('date', [$start, $end]);
            })
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($payment, fn($q) => $q->where('payment_method', $payment))
            ->when($created_by, fn($q) => $q->where('created_by', 'LIKE', "%{$created_by}%"))
            ->when(RoleHelper::isPartnerScopedUser(), function ($q) {
                $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
            })
            ->orderBy('created_at', 'desc');


        $totalIncome = (clone $baseQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $baseQuery)->where('type', 'expense')->sum('amount');
        $netAmount = $totalIncome - $totalExpense;
        $count = (clone $baseQuery)->count();
        $totalStaffs = (clone $baseQuery)->whereNotNull('staff_name')->distinct('staff_name')->count('staff_name');

        $incomeExpense = (clone $baseQuery)
            ->orderByDesc('id')
            ->when($type, fn($q) => $q->where('type', $type));

        if ($paginate === -1) {
            $incomeExpense = $incomeExpense->get();
            $incomeExpense = [
                'data' => $incomeExpense,
                'total' => $incomeExpense->count(),
                'current_page' => 1,
                'per_page' => $incomeExpense->count(),
            ];
        } else {
            $incomeExpense = $incomeExpense->paginate($paginate)->appends($filters);
        }


        return Inertia::render('IncomeExpense/Index', [
            'filters' => $filters,
            'data' => $incomeExpense,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netAmount' => $netAmount,
            'partnerGroup' => $partnerGroup,
            'count' => $count,

        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'            => 'required|in:income,expense',
            'date'            => 'required|date',
            'amount'          => 'required|numeric|min:0',
            'payment_status'  => 'required|in:Chờ thanh toán,Đã thanh toán',
            'category'        => 'required|string|max:255',
            'subcategory'     => 'required|string|max:255',
            'payment_method'  => 'required|string|max:255',
            'payment_source'  => 'required|string',
            'payment_object'  => 'required|string',
            'note'            => 'nullable|string',
            'file'            => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            'partner_group_id' => 'nullable|exists:partner_groups,id',
        ]);

        if ($request->hasFile('file')) {
            $data['file'] = $request->file('file')
                ->store('uploads/expenses', 'public');
        }

        if (!RoleHelper::isPartnerScopedUser()) {
            if (!$request->filled('partner_group_id')) {
                return back()->withErrors(['partner_group_id' => 'Vui lòng chọn nhóm đối tác']);
            }
            $data['partner_group_id'] = $request->input('partner_group_id');
        } else {
            $data['partner_group_id'] = RoleHelper::getScopedPartnerGroupId();
        }

        $data['created_by']          = Auth::user()->name;
        $data['business_type']       = 'Nhập tay';
        $data['source_business_type'] = 'MANUAL';

        DB::transaction(function () use ($data) {
            $expense = IncomeExpense::create($data);
            $expense->update([
                'source_business_code' => 'MANUAL-' . $expense->id,
            ]);
            $actionType = $expense->payment_status === 'Đã thanh toán'
                ? 'confirm_payment'
                : 'create';
            AuditLog::create([
                'income_expense_id' => $expense->id,
                'action_type'       => $actionType,
                'performed_by'      => Auth::user()->name,
                'performed_at'      => now(),
                'source_type'       => 'manual',
            ]);
        });

        return back()->with('success', 'Tạo phiếu chi thành công.');
    }


    public function export(Request $request)
    {
        $type = $request->selectedType;
        $ids = $request->selectedIds;
        $range_date = $request->range_date;
        $category = $request->selectedCategory;
        $payment_method = $request->selectedPayment;
        $created_by = $request->selectedCreatedBy;

        return Excel::download(
            new ExportIncomeExpense($type, $range_date, $category, $payment_method, $created_by, $ids),
            'IncomeExpense.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }


    public function getChartData()
    {
        $today = Carbon::today();

        $dates = collect(range(0, 6))->map(function ($i) use ($today) {
            return $today->copy()->subDays(6 - $i)->format('d-m');
        });

        $incomeData = [];
        $expenseData = [];

        foreach ($dates as $dateLabel) {
            $date = Carbon::createFromFormat('d-m', $dateLabel)->setYear($today->year)->format('Y-m-d');

            $income = IncomeExpense::whereDate('date', $date)
                ->where('type', 'income')
                ->sum('amount');

            $expense = IncomeExpense::whereDate('date', $date)
                ->where('type', 'expense')
                ->sum('amount');

            $incomeData[] = round($income / 1000);
            $expenseData[] = round($expense / 1000);
        }

        return response()->json([
            'labels' => $dates,
            'income' => $incomeData,
            'expense' => $expenseData,
        ]);
    }

    public function getPieData()
    {
        $total = IncomeExpense::sum('amount');

        if ($total == 0) {
            return response()->json([]);
        }

        $data = IncomeExpense::select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get()
            ->map(function ($item) use ($total) {
                return [
                    'label' => $item->category,
                    'value' => round(($item->total / $total) * 100, 2),
                    'amount' => $item->total,
                ];
            });

        return response()->json($data);
    }

    public function exportPdf($id)
    {
        $incomeExpense = IncomeExpense::with('auditLogs')->findOrFail($id);
        $pdf = Pdf::loadView('income-expense', compact('incomeExpense'));
        return $pdf->stream("phieu-thu-chi-{$incomeExpense->id}.pdf");
    }

    public function printView($id)
    {
        $incomeExpense = IncomeExpense::with('auditLogs')->findOrFail($id);
        return view('income-expense', [
            'incomeExpense' => $incomeExpense,
        ]);
    }

    public function destroy(IncomeExpense $incomeandexpense)
    {
        $incomeandexpense->delete();
        return redirect()->back()->with('success', 'Xóa phiếu chi thành công');
    }

    public function update(Request $request, IncomeExpense $incomeandexpense)
    {
        $data = $request->validate([
            'type'            => 'required|in:income,expense',
            'date'            => 'required|date',
            'amount'          => 'required|numeric|min:0',
            'payment_status'  => 'required|in:Chờ thanh toán,Đã thanh toán',
            'category'        => 'required|string|max:255',
            'subcategory'     => 'required|string|max:255',
            'payment_method'  => 'required|string|max:255',
            'payment_source'  => 'required|string',
            'payment_object'  => 'required|string',
            'note'            => 'nullable|string',
            'file'            => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        if ($request->hasFile('file')) {
            if ($incomeandexpense->file && Storage::disk('public')->exists($incomeandexpense->file)) {
                Storage::disk('public')->delete($incomeandexpense->file);
            }
            $data['file'] = $request->file('file')->store('uploads/expenses', 'public');
        }

        $data['updated_by'] = Auth::user()->name;

        DB::transaction(function () use ($incomeandexpense, $data) {
            $oldStatus = $incomeandexpense->payment_status;

            $incomeandexpense->update($data);

            if ($oldStatus !== 'Đã thanh toán' && $incomeandexpense->payment_status === 'Đã thanh toán') {

                AuditLog::where('income_expense_id', $incomeandexpense->id)
                    ->where('action_type', 'confirm_payment')
                    ->delete();

                AuditLog::create([
                    'income_expense_id' => $incomeandexpense->id,
                    'action_type'       => 'confirm_payment',
                    'performed_by'      => Auth::user()->name,
                    'performed_at'      => now(),
                    'source_type'       => 'manual',
                ]);
            }
        });

        return back()->with('success', 'Cập nhật phiếu chi thành công.');
    }
}
