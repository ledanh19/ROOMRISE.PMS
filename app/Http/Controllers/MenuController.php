<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Menu::class, 'menu');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->search;

        $order = $request->order ?? 'name';
        $by = $request->by ?? 'asc';
        $paginate = is_numeric($request->paginate) && $request->paginate > 0 ? $request->paginate : 10;

        $filters = $request->only(['search', 'order', 'by', 'paginate']);

        $menuData = Menu::with('parent')->when($search, function ($query) use ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        })
            ->orderBy($order, $by)
            ->paginate($paginate)
            ->appends($filters);

        return Inertia::render('Menu/Index', [
            'menuData' => $menuData,
            'filters' => $filters,
        ]);
    }
    public function getData()
    {
        $menus = Menu::with('parent:id,name')->get();

        return response()->json(['data' => $menus]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Menu/Form', [
            'menus' => Menu::all(),
            'roles' => Role::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'menu_key' => 'required|string|max:255|unique:menus,menu_key',
            'link' => 'nullable|string',
            'order' => 'required|integer',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'is_heading'   => 'nullable|boolean',
        ]);

        $parentMenu = $request->parent ? Menu::find($request->parent) : null;
        $parentKey = $parentMenu ? $parentMenu->menu_key : null;
        $image = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = $file->getClientOriginalName();
            $destinationPath = public_path('menus');
            $file->move($destinationPath, $fileName);
            $image = "menus/$fileName";
        }

        $image_active = null;

        if ($request->hasFile('image_active')) {
            $fileActive = $request->file('image_active');
            $fileNameActive = $fileActive->getClientOriginalName();
            $destinationPathActive = public_path('menus');
            $fileActive->move($destinationPathActive, $fileNameActive);
            $image_active = "menus/$fileNameActive";
        }

        $menu = Menu::create([
            'name' => $validated['name'],
            'link' => $validated['link'],
            'order' => $validated['order'],
            'parent_id' => $parentMenu ? $parentMenu->id : null,
            'image' => $image,
            'image_active' => $image_active,
            'menu_key' => $validated['menu_key'],
            'is_heading' => $validated['is_heading'] ?? false,
        ]);

        $roleNames = [];
        foreach ($request->roles as $roleId) {
            $role = Role::find($roleId);
            if ($role) {
                MenuRole::create([
                    'menu_id' => $menu->id,
                    'role_id' => $role->id,
                ]);
                $roleNames[] = $role->name;
            }
        }

        $jsonPath = public_path('menus.json');
        $json = file_get_contents($jsonPath);
        $menus = json_decode($json, true) ?? [];

        $menus[] = [
            'key' => $request->menu_key,
            'name' => $menu->name,
            'link' => $menu->link,
            'order' => $menu->order,
            'parent_key' => $parentKey,
            'image' => $menu->image,
            'image_active' => $menu->image_active,
            'is_heading' => $menu->is_heading,
            'roles' => $roleNames
        ];

        file_put_contents($jsonPath, json_encode($menus, JSON_PRETTY_PRINT));

        return redirect()->route('menu.index')->with('created', "Created menu successfully");
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
    public function edit(Menu $menu)
    {
        return Inertia::render('Menu/Form', [
            'menu_detail' => $menu->load('roles', 'parent'),
            'menus' => Menu::all(),
            'roles' => Role::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Menu $menu)
    {
        // 1. Validate
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'menu_key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('menus', 'menu_key')->ignore($menu->id),
            ],
            'order' => 'required|numeric',
            'link' => 'nullable|string',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'is_heading' => 'nullable|boolean',
        ]);

        $oldMenuKey = $menu->menu_key;

        // 2. Get parent menu
        $parentMenu = $request->parent ? Menu::find($request->parent) : null;
        $parentKey = $parentMenu->menu_key ?? null;

        // 3. Handle image upload
        $menu->image = $this->handleImageUpload($request, 'image', $menu->image);
        $menu->image_active = $this->handleImageUpload($request, 'image_active', $menu->image_active);

        // 4. Update menu
        $menu->update([
            'name' => $validated['name'],
            'order' => $validated['order'],
            'link' => $validated['link'],
            'parent_id' => $parentMenu->id ?? null,
            'image' => $menu->image,
            'image_active' => $menu->image_active,
            'menu_key' => $validated['menu_key'],
            'is_heading' => $validated['is_heading'] ?? false,
        ]);

        // 5. Sync roles
        $this->syncRoles($menu, $request->roles);

        // 6. Update related JSON files
        $this->updateMenusJsonUpdate($oldMenuKey, $menu, $parentKey);
        $this->updatePermissionsJsonUpdate($oldMenuKey, $menu);
        $this->updateRolesJsonUpdate($oldMenuKey, $menu);
        $this->updatePermissionsTable($oldMenuKey, $menu);

        return redirect()->route('menu.index')->with('updated', "Updated menu successfully");
    }

    protected function handleImageUpload(Request $request, $field, $currentPath)
    {
        if ($request->hasFile($field)) {
            $file = $request->file($field);
            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('menus');
            $file->move($destinationPath, $fileName);
           return "menus/$fileName";
        }
        return $currentPath;
    }

    protected function syncRoles(Menu $menu, array $roleIds)
    {
        MenuRole::where('menu_id', $menu->id)->delete();

        foreach ($roleIds as $roleId) {
            MenuRole::create([
                'menu_id' => $menu->id,
                'role_id' => $roleId,
            ]);
        }
    }

    protected function updateMenusJsonUpdate($oldKey, $menu, $parentKey)
    {
        $path = public_path('menus.json');
        $menus = file_exists($path) ? json_decode(file_get_contents($path), true) : [];

        foreach ($menus as &$item) {
            if ($item['key'] === $oldKey) {
                $item['key'] = $menu->menu_key;
                $item['name'] = $menu->name;
                $item['link'] = $menu->link;
                $item['order'] = $menu->order;
                $item['parent_key'] = $parentKey;
                $item['image'] = $menu->image;
                $item['image_active'] = $menu->image_active;
                $item['is_heading'] = $menu->is_heading;
                $item['roles'] = $menu->roles->pluck('name')->toArray();
                break;
            }
        }

        file_put_contents($path, json_encode($menus, JSON_PRETTY_PRINT));
    }


    protected function updatePermissionsJsonUpdate($oldKey, $menu)
    {
        $path = public_path('permissions.json');
        $json = file_exists($path) ? json_decode(file_get_contents($path), true) : [];

        if ($oldKey !== $menu->menu_key && isset($json[$oldKey])) {
            $json[$menu->menu_key] = $json[$oldKey];

            foreach ($json[$menu->menu_key] as &$perm) {
                if (str_ends_with($perm, '-' . $oldKey)) {
                    $perm = rtrim(substr($perm, 0, -strlen('-' . $oldKey)), '-') . '-' . $menu->menu_key;
                }
            }

            unset($json[$oldKey]);
            file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT));
        }
    }

    protected function updateRolesJsonUpdate($oldKey, $menu)
    {
        $path = public_path('roles.json');
        if (!file_exists($path)) return;

        $roles = json_decode(file_get_contents($path), true) ?? [];

        foreach ($roles as &$permissions) {
            foreach ($permissions as &$perm) {
                if (str_ends_with($perm, '-' . $oldKey)) {
                    $perm = rtrim(substr($perm, 0, -strlen('-' . $oldKey)), '-') . '-' . $menu->menu_key;
                }
            }
        }

        file_put_contents($path, json_encode($roles, JSON_PRETTY_PRINT));
    }

    protected function updatePermissionsTable($oldKey, $menu)
    {
        Permission::where('module', $oldKey)->update(['module' => $menu->menu_key]);

        $permissions = Permission::where('module', $menu->menu_key)->get();
        foreach ($permissions as $permission) {
            if (str_ends_with($permission->name, '-' . $oldKey)) {
                $prefix = rtrim(substr($permission->name, 0, -strlen('-' . $oldKey)), '-');
                $newName = $prefix ? $prefix . '-' . $menu->menu_key : $menu->menu_key;
                $permission->update(['name' => $newName]);
            }
        }
    }


    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Request $request, Menu $menu)
    {
        if ($menu->children()->exists()) {
            return back()->withErrors(['message' => 'Cannot delete menu with sub-menus. Please delete sub-menus first']);
        }
        $moduleKey = $menu->menu_key;

        DB::transaction(function () use ($menu, $moduleKey) {
            $menu->delete();
            $this->updateMenusJson($moduleKey);
            $this->updatePermissionsJson($moduleKey);
            $this->updateRolesJson($moduleKey);
            Permission::where('module', $moduleKey)->delete();
        });

        return back()->with('deleted', 'Menu deleted successfully');
    }

    private function updateMenusJson($moduleKey)
    {
        $filePath = public_path('menus.json');
        if (!file_exists($filePath)) {
            return;
        }
        $json = file_get_contents($filePath);
        $menus = json_decode($json, true) ?? [];

        $updatedMenus = array_filter($menus, function ($menuItem) use ($moduleKey) {
            return $menuItem['key'] !== $moduleKey;
        });
        file_put_contents($filePath, json_encode(array_values($updatedMenus), JSON_PRETTY_PRINT));
    }

    private function updatePermissionsJson($moduleKey)
    {
        $filePath = public_path('permissions.json');
        if (!file_exists($filePath)) {
            return;
        }
        $jsonPermissions = file_get_contents($filePath);
        $permissions = json_decode($jsonPermissions, true) ?? [];
        if (isset($permissions[$moduleKey])) {
            unset($permissions[$moduleKey]);
            file_put_contents($filePath, json_encode($permissions, JSON_PRETTY_PRINT));
        }
    }

    private function updateRolesJson($moduleKey)
    {
        $filePath = public_path('roles.json');
        if (!file_exists($filePath)) {
            return;
        }

        $permissionsToDelete = Permission::where('module', $moduleKey)->pluck('name')->toArray();
        if (empty($permissionsToDelete)) {
            return;
        }

        $jsonRoles = file_get_contents($filePath);
        $roles = json_decode($jsonRoles, true) ?? [];

        foreach ($roles as $roleName => &$permissions) {
            $permissions = array_values(array_diff($permissions, $permissionsToDelete));
        }

        file_put_contents($filePath, json_encode($roles, JSON_PRETTY_PRINT));
    }
}
