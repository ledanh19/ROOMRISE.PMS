<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use App\Models\PartnerGroup;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Customer::class, 'customer');
    }
    public function index(Request $request)
    {
        $search = $request->search;
        $type = $request->type;
        $partner_group_id = $request->partner_group_id;
        $country = $request->country;
        $city = $request->city;
        $property_id = $request->property_id;
        $pg_id = Property::find($property_id)->partner_group_id ?? null;

        $paginate = is_numeric($request->paginate) && $request->paginate > 0 ? $request->paginate : 10;

        $filters = $request->only(['search', 'type', 'partner_group_id', 'paginate']);

        $data = Customer::with('partnerGroup')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            })
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($partner_group_id, function ($query) use ($partner_group_id) {
                $query->where('partner_group_id', $partner_group_id);
            })
            ->when($pg_id, function ($query) use ($pg_id) {
                $query->where('partner_group_id', $pg_id);
            })
            ->when($country, function ($query) use ($country) {
                $query->where('country', $country);
            })
            ->when($city, function ($query) use ($city) {
                $query->where('city', $city);
            })
            ->when(RoleHelper::isPartnerScopedUser(), function ($query) {
                $query->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
            })
            ->paginate($paginate)
            ->appends($filters);

        $partnerGroup = PartnerGroup::select(['id', 'name'])->get();
        $countries = Customer::select('country')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');
        $cities = Customer::select('city')
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return Inertia::render('Customers/Index', [
            'data' => $data,
            'filters' => $filters,
            'partnerGroup' => $partnerGroup,
            'countries' => $countries,
            'cities' => $cities,
        ]);
    }


    public function getByType($type)
    {
        $customers = Customer::where('type', $type)->get(['id', 'full_name']);
        return response()->json($customers);
    }

    public function store(StoreCustomerRequest $request)
    {
        $data = $request->validated();

        if (!RoleHelper::isPartnerScopedUser()) {
            $request->validate([
                'partner_group_id' => 'required|exists:partner_groups,id'
            ]);
            $data['partner_group_id'] = $request->input('partner_group_id');
        }

        if (RoleHelper::isPartnerScopedUser()) {
            $data['partner_group_id'] = RoleHelper::getScopedPartnerGroupId();
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('uploads/ids', 'public');
        }

        Customer::create($data);

        return back()->with('success', 'Khách hàng đã được tạo');
    }


    public function update(StoreCustomerRequest $request, Customer $customer)
    {
        $data = $request->validated();

        if (!RoleHelper::isPartnerScopedUser()) {
            $request->validate([
                'partner_group_id' => 'required|exists:partner_groups,id'
            ]);
            $data['partner_group_id'] = $request->input('partner_group_id');
        }

        if (RoleHelper::isPartnerScopedUser()) {
            $data['partner_group_id'] = RoleHelper::getScopedPartnerGroupId();
        }

        $oldImage = $customer->image;
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('uploads/ids', 'public');

            if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                Storage::disk('public')->delete($oldImage);
            }
        } else {
            $data['image'] = $oldImage;
        }

        $customer->update($data);

        return back()->with('success', 'Khách hàng đã được cập nhật');
    }


    public function destroy(Customer $customer)
    {
        $customer->delete();
        return back()->with('success', 'Khách hàng đã được xóa!');
    }

    public function getBySearch(Request $request)
    {
        $partnerGroup = Property::findOrFail($request->property_id);
        if (!$request->input('customer')) {
            return response()->json([
                'found' => false,
                'customer' => null,
            ]);
        }

        $query = Customer::query()
            ->when($partnerGroup, function ($q) use ($partnerGroup) {
                $q->where('partner_group_id', $partnerGroup->partner_group_id);
            })
            ->where(function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->input('customer')}%")
                    ->orWhere('phone', 'like', "%{$request->input('customer')}%")
                    ->orWhere('email', 'like', "%{$request->input('customer')}%");
            });

        if (RoleHelper::isPartnerScopedUser()) {
            $query->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
        }

        $user = $query->first();

        return response()->json([
            'found' => (bool) $user,
            'customer' => $user,
        ]);
    }

    public function show(Request $request, Customer $customer)
    {
        $customer->load('partnerGroup');

        $bookings = $customer->bookings()
            ->with([
                'bookingRooms',
                'property',
                'bookingRooms.room',
                'bookingRooms.roomUnit',
                'incomeExpenses',
            ])
            ->orderBy('created_at', 'desc')->get();
        return Inertia::render('Customers/Detail', [
            'customer' => $customer,
            'bookings' => $bookings,
        ]);
    }
}
