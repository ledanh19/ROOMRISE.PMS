<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Helpers\RoleHelper;
use App\Http\Requests\BookingSourceRequest;
use App\Http\Resources\BookingSourceResource;
use App\Models\BookingSource;
use App\Models\PartnerGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingSourceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $paginate = is_numeric($request->paginate) && $request->paginate > 0 ? $request->paginate : 10;

        $filters = $request->only(['search', 'paginate']);

        $data = BookingSource::when($search, function ($query) use ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        })
            ->paginate($paginate)
            ->appends($filters);

        return Inertia::render('Settings/BookingSource/Index', [
            'data' => ResponseHelper::dataTable($data, BookingSourceResource::class),
            'filters' => $filters,
        ]);
    }

    public function store(BookingSourceRequest $request)
    {
        $validated = $request->validated();

        // Set default price_percentage if not provided
        if (!isset($validated['price_percentage'])) {
            $validated['price_percentage'] = 0;
        }

        BookingSource::create($validated);

        return redirect()->back()->with('created', 'Thêm mới nguồn đặt phòng thành công');
    }

    public function update(BookingSourceRequest $request, BookingSource $bookingSource)
    {
        $validated = $request->validated();

        // If this is set as default, unset other defaults in the same partner group
        if ($validated['is_default'] ?? false) {
            BookingSource::where('partner_group_id', $bookingSource->partner_group_id)
                ->where('id', '!=', $bookingSource->id)
                ->update(['is_default' => false]);
        }

        $bookingSource->update($validated);

        return redirect()->back()->with('updated', 'Cập nhật nguồn đặt phòng thành công');
    }

    public function destroy(BookingSource $bookingSource)
    {
        $bookingSource->delete();
        return back()->with('deleted', 'Nguồn đặt phòng đã được xóa');
    }
}
