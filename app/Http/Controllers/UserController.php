<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
use App\Models\Menu;
use App\Models\PartnerGroup;
use App\Models\Property;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search   = $request->search;
        $paginate = is_numeric($request->paginate) && $request->paginate > 0 ? $request->paginate : 10;
        $property_id = $request->property_id;
        $filters = $request->only(['search', 'paginate']);
        $partner_group_id = Property::where('id', $property_id)->value('partner_group_id');
        $partnerGroupId = RoleHelper::getScopedPartnerGroupId();
        $usersData = User::when($search, function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        })
            ->when($partner_group_id, function ($q) use ($partner_group_id) {
                $q->where('partner_group_id', $partner_group_id);
            })
            ->when($partnerGroupId, function ($query) use ($partnerGroupId) {
                $query->where('partner_group_id', $partnerGroupId);
            })
            ->paginate($paginate)->appends($filters);

        $users = $usersData->getCollection()->map(function ($user) {
            $roles = $user->getRoleNames();
            $user->role = $roles->first() ?? '';
            return $user;
        });
        $usersData->setCollection($users);
        $partnerGroup = PartnerGroup::select(['id', 'name'])->get();
        $currentUser = Auth::user();

        if ($currentUser->hasRole('Super Admin')) {
            $roles = Role::all();
        } elseif ($currentUser->hasRole('Partner Admin')) {
            $roles = Role::whereNotIn('name', ['Admin', 'Partner Admin', 'Super Admin'])->get();
        } else {
            $roles = Role::all(); // hoặc bạn có thể giới hạn theo nhu cầu
        }

        return Inertia::render('User/Index', [
            'userData' => $usersData,
            'filters'  => $filters,
            'roles'        => $roles,
            'qty_user' => User::count(),
            'partnerGroup' => $partnerGroup,
        ]);
    }


    public function getData(Request $request)
    {
        $search       = $request->input('search', '');
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $page         = $request->input('page', 1);

        $query = User::when($search, function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        })->latest();

        $users = $itemsPerPage != -1
            ? $query->paginate($itemsPerPage, ['*'], 'page', $page)
            : $query->get();

        return response()->json([
            'data'  => $users->map(function ($user) {
                return [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ];
            }),
            'total' => $itemsPerPage != -1 ? $users->total() : $users->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::get();

        return Inertia::render('User/Form', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $request->validate([
        //     'first_name'         => 'required',
        //     'last_name'          => 'required',
        //     'username'           => 'required|unique:users',
        //     'phone'              => 'required|numeric|unique:users',
        //     'role'               => 'required',
        //     'password'           => 'required',
        //     'profile_photo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2000',
        // ]);
        $request->validate([
            'first_name'         => 'required',
            'last_name'          => 'required',
            'username'           => 'required|unique:users,username',
            'email'              => 'required|email|unique:users,email',
            'phone'              => 'required|numeric|unique:users,phone',
            'role'               => 'required',
            'password'           => 'required',
            'profile_photo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2000',
        ]);



        $avatarPath = null;
        if ($request->hasFile('profile_photo_path')) {
            $avatarPath = $request->file('profile_photo_path')->store('avatars', 'public');
        }
        $roleNames = Auth::user()->getRoleNames()->first();
        if ($roleNames == 'Partner Admin') {
            $partner_group_id = Auth::user()->partner_group_id;
        } else {
            $partner_group_id = $request->partner_group_id;
        }

        $user = User::create([
            'name'               => $request->first_name . ' ' . $request->last_name,
            'first_name'         => $request->first_name,
            'last_name'          => $request->last_name,
            'username'           => $request->username,
            'email'              => $request->email,
            'phone'              => $request->phone,
            'password'           => bcrypt($request->password),
            'profile_photo_path' => $avatarPath,
            'partner_group_id'   => $partner_group_id,
        ]);
        $role = Role::find($request->role);
        if (! empty($role)) {
            $user->assignRole($role->name);
        }

        if ($role && $role->name === 'Partner Admin') {
            $group = PartnerGroup::create([
                'name' => $request->username . ' - ' . $request->email,
                'partner_admin_id' => $user->id,
            ]);
            $user->update(['partner_group_id' => $group->id]);
        }
        $properties = json_decode($request->properties, true);
        if (is_array($properties)) {
            foreach ($properties as $propertyData) {
                Property::create([
                    'name'            => $propertyData['name'],
                    'type'            => $propertyData['type'], // TODO: update it
                    'max_room_types'  => $propertyData['max_room_types'],
                    'max_rooms'       => $propertyData['max_rooms'],
                    'checkin_from_time' => '14:00',
                    'checkout_to_time' => '12:00',
                    'currency' => 'VND',
                    'owner_id'        => $user->id,
                    'partner_group_id'   => $user->partner_group_id,
                ]);
            }
        }
        return response()->json([
            'success' => true,
            'message' => 'Đã tạo người dùng thành công.',
            'data'    => $user,
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, User $user)
    {
        if (! empty($user->profile_photo_path)) {
            $user->profile_photo_path = asset('storage/' . $user->profile_photo_path);
        }

        $user->usermeta = $user->usermeta->toArray();
        $user->role     = $user->roles()->first() ? $user->roles()->first()->name : '';

        return Inertia::render('User/View', [
            'user' => $user,
        ]);
    }

    public function profile()
    {
        $user          = User::findOrFail(Auth::user()->id);
        $user->address = $user->getMetaValue('address');
        $user->zipcode = $user->getMetaValue('zipcode');
        $user->role    = $user->roles()->first() ? $user->roles()->first()->name : '';
        return Inertia::render('Profile/Profile', ['user' => $user]);
    }

    public function profileEdit()
    {
        $user = User::findOrFail(Auth::user()->id);
        if (! empty($user->profile_photo_path)) {
            $user->profile_photo_path = asset('storage/' . $user->profile_photo_path);
        }

        $user->address = $user->getMetaValue('address');
        $user->zipcode = $user->getMetaValue('zipcode');

        return Inertia::render('Profile/ProfileEdit', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, User $user)
    {

        // dd($user->profile_photo_path);
        // if (! empty($user->profile_photo_path)) {
        //     $user->profile_photo_path = asset('storage/' . $user->profile_photo_path);
        // }

        $currentRole = $user->roles()->first();
        $properties = [];
        if ($currentRole && $currentRole->name === 'Partner Admin') {
            $properties = Property::where('owner_id', $user->id)->get();
        }

        return Inertia::render('User/Edit', [
            'user'         => $user,
            'roles'        => Role::all(),
            'role_current' => $user->roles()->first(),
            'properties'   => $properties,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        // dd($request->all());
        $request->validate([
            'first_name' => 'sometimes|required',
            'last_name'  => 'sometimes|required',
            'username'   => 'sometimes|required|unique:users,username,' . $user->id . ',id',
            'phone'      => 'sometimes|required|numeric|unique:users,phone,' . $user->id . ',id',
            'role'       => 'sometimes|required',
            'password'   => 'sometimes',
            'properties' => 'sometimes|json', // Thêm validation cho properties
        ]);

        $data = $request->except('role', 'password', 'properties');

        if ($request->password) {
            $data['password'] = $request->password;
        }

        if ($request->hasFile('profile_photo_path')) {
            if ($user->profile_photo_path) {
                Storage::delete($user->profile_photo_path);
            }

            $filePath                   = $request->file('profile_photo_path')->store('avatars', 'public');
            $data['profile_photo_path'] = $filePath;
        }

        $user->update($data);

        $metaKeys = ['address', 'zipcode'];
        foreach ($metaKeys as $key) {
            if (! empty($request->input($key))) {
                UserMeta::updateOrCreate(
                    ['user_id' => $user->id, 'meta_key' => $key],
                    ['meta_value' => $request->input($key)]
                );
            }
        }

        if (! empty($request->role)) {
            $user->roles()->detach();

            $role = Role::find($request->role);
            if ($role) {
                $user->assignRole($role->name);
                if (strtolower($role->name) === 'Partner Admin' && !$user->partner_group_id) {
                    $group = PartnerGroup::create([
                        'name' => 'Nhóm của ' . $user->name,
                        'partner_admin_id' => $user->id,
                    ]);
                    $user->update(['partner_group_id' => $group->id]);
                }
            } else {
                return redirect()->back()->with('error', 'Invalid role selected.');
            }
        }


        // Xử lý cập nhật properties cho Partner Admin
        if ($request->has('properties') && $role && $role->name === 'Partner Admin') {
            $properties = json_decode($request->properties, true);
            if (is_array($properties)) {
                $existingProperties = Property::where('owner_id', $user->id)->get()->keyBy('id');

                logger($properties);
                foreach ($properties as $index => $propertyData) {
                    if (isset($propertyData['id']) && $existingProperties->has($propertyData['id'])) {
                        logger(1);
                        // Cập nhật property hiện tại
                        $existingProperty = $existingProperties->get($propertyData['id']);
                        $existingProperty->update([
                            'name' => $propertyData['name'],
                            'type' => $propertyData['type'],
                            'max_room_types' => $propertyData['max_room_types'],
                            'max_rooms' => $propertyData['max_rooms'],
                        ]);
                    } else {
                        logger('create new');
                        // Tạo property mới
                        Property::create([
                            'name'            => $propertyData['name'],
                            'type'            => $propertyData['type'],
                            'max_room_types'  => $propertyData['max_room_types'],
                            'max_rooms'       => $propertyData['max_rooms'],
                            'checkin_from_time' => '14:00',
                            'checkout_to_time' => '12:00',
                            'currency' => 'VND',
                            'owner_id'        => $user->id,
                            'partner_group_id'   => $user->partner_group_id,
                        ]);
                    }
                }
            }
        }

        return redirect()->back();
    }

    public function updatePassword(Request $request, $id)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $user = User::findOrFail($id);

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return redirect()->back()->with([
                'error' => 'Incorrect current password.',
            ]);
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        return redirect()->back()->with('success', 'Password updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        if (empty($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Xóa người dùng thất bại!',
            ]);
        }
        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'Đã xóa người dùng thành công.',
        ]);
    }
}
