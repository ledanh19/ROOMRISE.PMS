<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $paginate = is_numeric($request->paginate) && $request->paginate > 0 ? $request->paginate : 10;

        $filters = $request->only(['search', 'paginate']);

        $invoices = Invoice::with(['customer'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('customer', function ($q) use ($search) {
                    $q->whereRaw('LOWER(full_name) LIKE ?', ['%' . strtolower($search) . '%']);
                });
            })
            ->paginate($paginate)
            ->appends($filters);


        return Inertia::render('Invoices/Index', [
            'filters' => $filters,
            'data' => $invoices,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'total_amount' => 'required|numeric',
            'payment_type' => 'required',
            'note' => 'nullable|string',
            'selected_bookings' => 'required|array',
            'selected_bookings.*' => 'exists:bookings,id',
        ]);

        DB::beginTransaction();

        try {

            $invoice = Invoice::create([
                'customer_id' => $validated['customer_id'],
                'total_amount' => $validated['total_amount'],
                'status' => "paid",
                'note' => $validated['note'] ?? null,
                'issued_date' => now(),
                'created_by' => Auth::id(),
            ]);

            $bookings = Booking::with(['property', 'room', 'roomUnit'])
                ->whereIn('id', $validated['selected_bookings'])
                ->get();

            foreach ($bookings as $booking) {
                $invoice->items()->create([
                    'property_id'    => $booking->property_id,
                    'room_id'        => $booking->room_id,
                    'room_unit_id'   => $booking->room_unit_id,
                    'payment_type'   => null,
                    'check_in_date'  => $booking->check_in_date,
                    'check_out_date' => $booking->check_out_date,
                    'price'          => $booking->room_price_at_booking,
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Hóa đơn đã được tạo thành công!.');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors(['db_error' => 'Có lỗi xảy ra khi tạo hóa đơn: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getAllCustomer()
    {
        $customers = Customer::get(['id', 'full_name']);
        return response()->json($customers);
    }

    public function getBookingByCustomerId($customer_id)
    {
        $bookings = Booking::with('property')->where("customer_id", $customer_id)->get();
        return response()->json($bookings);
    }

    public function loadPropertiesData()
    {
        $properties = Property::get(['id', 'name']);
        return response()->json($properties);
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

    public function loadBookingsData(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $property = $request->property;
        $customer_ids = $request->customer_ids;
        $ota_channel = $request->ota_channel;
        $status_booking = $request->status_booking;

        $bookings = Booking::query()
            ->when($start_date && $end_date, function ($q) use ($start_date, $end_date) {
                $q->whereDate('check_in_date', '>=', $start_date)
                    ->whereDate('check_out_date', '<=', $end_date);
            })
            ->when($start_date && !$end_date, function ($q) use ($start_date) {
                $q->whereDate('check_in_date', '=', $start_date);
            })
            ->when($end_date && !$start_date, function ($q) use ($end_date) {
                $q->whereDate('check_out_date', '=', $end_date);
            })
            ->when($property, fn($q) => $q->where('property_id', $property))
            ->when(!empty($customer_ids), fn($q) => $q->whereIn('customer_id', $customer_ids))
            ->when($ota_channel && $ota_channel !== 'Tất cả các kênh', fn($q) => $q->where('ota_channel', $ota_channel))
            ->when($status_booking && $status_booking !== 'Tất cả trạng thái', fn($q) => $q->where('status', $status_booking))
            ->with('property')
            ->get();


        return response()->json($bookings);
    }
}
