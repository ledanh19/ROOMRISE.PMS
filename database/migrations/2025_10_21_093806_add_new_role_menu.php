<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            $now = now();

            $parentId = DB::table('menus')
                ->where('menu_key', 'home')
                ->value('id');

            DB::table('menus')->updateOrInsert(
                ['menu_key' => 'control-panel'],
                [
                    'name'          => 'Bảng điều khiển',
                    'order'         => 2,
                    'link'          => 'control-panel',
                    'parent_id'     => $parentId,
                    'image'         => null,
                    'image_active'  => null,
                    'is_heading'    => 0,
                    'updated_at'    => $now,
                    'created_at'    => DB::raw("COALESCE(created_at, '{$now}')"),
                ]
            );

            $menuId = DB::table('menus')
                ->where('menu_key', 'control-panel')
                ->value('id');

            if (!$menuId) return;

            $roleNames = [
                'Admin',
                'Super Admin',
                'Partner Admin',
                'OTA Staff',
                'CSKH Staff',
                'OPS Staff',
                'Kế Toán',
                'OTA Admin',
                'ADMIN - ANGIA',
                'Developer',
                'View Only',
            ];

            $roleIds = DB::table('roles')
                ->whereIn('name', $roleNames)
                ->pluck('id')
                ->all();

            foreach ($roleIds as $rid) {
                DB::table('menu_roles')->updateOrInsert(
                    ['menu_id' => $menuId, 'role_id' => $rid],
                    []
                );
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $menuId = DB::table('menus')
                ->where('menu_key', 'control-panel')
                ->value('id');

            if ($menuId) {
                DB::table('menu_roles')->where('menu_id', $menuId)->delete();
                DB::table('menus')->where('id', $menuId)->delete();
            }
        });
    }
};
