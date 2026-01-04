<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
use App\Models\Partner;
use App\Models\PartnerGroup;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PartnerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Partner::class, 'partner');
    }

    public function index(Request $request)
    {
        $partnerGroup = PartnerGroup::select(['id', 'name'])->get();
        return Inertia::render('Partner/Index', [
            'partnerGroup' => $partnerGroup
        ]);
    }

    public function loadDataSale(Request $request)
    {
        $type = $request->type ?? 'Sale';
        $property_id = $request->property_id;

        // Nếu có property_id, lấy partner_group_id từ property
        $partner_group_id = null;
        if ($property_id) {
            $partner_group_id = Property::where('id', $property_id)->value('partner_group_id');
        }

        Log::info('Partner Group ID sale: ' . ($partner_group_id ?? 'null'));

        $query = Partner::query()
            ->when(
                $request->search,
                fn($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
            )
            ->where('type', $type);

        // Áp dụng filter theo partner_group_id nếu có property_id được chọn
        if ($property_id && $partner_group_id) {
            $query->where('partner_group_id', $partner_group_id);
        }

        // Áp dụng scope user nếu cần
        $query->when(RoleHelper::isPartnerScopedUser(), function ($q) {
            $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
        });

        return response()->json($query->paginate($request->paginate ?? 10));
    }
    public function loadDataSaleTA(Request $request)
    {
        $type = $request->type ?? 'Sale TA';
        $property_id = $request->property_id;

        // Nếu có property_id, lấy partner_group_id từ property
        $partner_group_id = null;
        if ($property_id) {
            $partner_group_id = Property::where('id', $property_id)->value('partner_group_id');
        }

        Log::info('Partner Group ID sale ta: ' . ($partner_group_id ?? 'null'));

        $query = Partner::query()
            ->when(
                $request->search,
                fn($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
            )
            ->where('type', $type);

        // Áp dụng filter theo partner_group_id nếu có property_id được chọn
        if ($property_id && $partner_group_id) {
            $query->where('partner_group_id', $partner_group_id);
        }

        // Áp dụng scope user nếu cần
        $query->when(RoleHelper::isPartnerScopedUser(), function ($q) {
            $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
        });

        return response()->json($query->paginate($request->paginate ?? 10));
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
        $rules = [
            'name'             => 'required|string',
            'email'            => 'nullable|email',
            'phone'            => 'nullable|string',
            'type'             => ['required', Rule::in(['Sale', 'Sale TA', 'OTA'])],
            'commission'       => 'required|numeric',
            'payment_method'   => 'required|string',
            'internal_code'    => 'required|string|unique:partners,internal_code',
            'status'           => ['required', Rule::in(['Hoạt động', 'Không hoạt động'])],
            'address'          => 'nullable|string',
            'city'             => 'nullable|string',
            'country'          => 'nullable|string',
            'internal_note'    => 'nullable|string',
        ];

        if (!RoleHelper::isPartnerScopedUser()) {
            $rules['partner_group_id'] = 'required|exists:partner_groups,id';
        }

        $validated = $request->validate($rules);

        if (RoleHelper::isPartnerScopedUser()) {
            $validated['partner_group_id'] = RoleHelper::getScopedPartnerGroupId();
        }

        Partner::create($validated);

        return redirect()->back()->with('success', 'Tạo đối tác thành công');
    }

    public function update(Request $request, Partner $partner)
    {
        $rules = [
            'name'           => 'required|string',
            'email'          => 'nullable|email',
            'phone'          => 'nullable|string',
            'type'           => ['required', Rule::in(['Sale', 'Sale TA', 'OTA'])],
            'commission'     => 'required|numeric',
            'payment_method' => 'required|string',
            'internal_code'  => [
                'required',
                'string',
                Rule::unique('partners', 'internal_code')->ignore($partner->id),
            ],
            'status'         => ['required', Rule::in(['Hoạt động', 'Không hoạt động'])],
            'address'        => 'nullable|string',
            'city'           => 'nullable|string',
            'country'        => 'nullable|string',
            'internal_note'  => 'nullable|string',
        ];

        if (!RoleHelper::isPartnerScopedUser()) {
            $rules['partner_group_id'] = 'required|exists:partner_groups,id';
        }

        $validated = $request->validate($rules);

        if (RoleHelper::isPartnerScopedUser()) {
            $validated['partner_group_id'] = RoleHelper::getScopedPartnerGroupId();
        }

        $partner->update($validated);

        return redirect()->back()->with('updated', 'Cập nhật đối tác thành công');
    }


    public function destroy(Partner $partner)
    {
        $partner->delete();
        return redirect()->back()->with('success', 'Xóa đối tác thành công');
    }

    public function getPartnerById(Request $request)
    {
        $partner = Partner::findOrFail($request->id);
        return response()->json($partner);
    }

    public function getPartnerByType(Request $request)
    {
        $type = $request->get('type');
        $query = Partner::query()
            ->where('type', $type)
            ->when(RoleHelper::isPartnerScopedUser(), function ($q) {
                $q->where('partner_group_id', RoleHelper::getScopedPartnerGroupId());
            });

        return response()->json($query->select('id', 'name')->get());
    }

    public function show(Request $request, Partner $partner)
    {
        $partner->load('partnerGroup');

        // Lấy bookings của các customer thuộc về partner này
        $bookings = $partner->customers()
            ->with([
                'bookings' => function ($q) {
                    $q->with([
                        'property',
                        'bookingRooms.room',
                        'bookingRooms.roomUnit',
                        'partnerIncomeExpenses',

                    ])->orderBy('created_at', 'desc');
                }
            ])
            ->get()
            ->pluck('bookings')
            ->flatten()
            ->sortByDesc('created_at')
            ->values();
        return Inertia::render('Partner/Detail', [
            'partner' => $partner,
            'bookings' => $bookings,
        ]);
    }
}
