<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $now = now();

            $parentId = DB::table('menus')
                ->where('menu_key', 'home')
                ->value('id');

            $items = [
                [
                    'menu_key' => 'dashboard-executive',
                    'name'     => 'Điều hành',
                    'order'    => 3,
                    'link'     => 'dashboard-executive',
                ],
                [
                    'menu_key' => 'dashboard-booking',
                    'name'     => 'Đặt phòng',
                    'order'    => 4,
                    'link'     => 'dashboard-booking',
                ],
                [
                    'menu_key' => 'dashboard-revenue',
                    'name'     => 'Doanh thu',
                    'order'    => 5,
                    'link'     => 'dashboard-revenue',
                ],
                [
                    'menu_key' => 'dashboard-performance',
                    'name'     => 'Hiệu suất',
                    'order'    => 6,
                    'link'     => 'dashboard-performance',
                ],
            ];

            foreach ($items as $it) {
                DB::table('menus')->updateOrInsert(
                    ['menu_key' => $it['menu_key']],
                    [
                        'name'         => $it['name'],
                        'order'        => $it['order'],
                        'link'         => $it['link'],
                        'parent_id'    => $parentId,
                        'image'        => null,
                        'image_active' => null,
                        'is_heading'   => 0,
                        'updated_at'   => $now,
                        'created_at'   => DB::raw("COALESCE(created_at, '{$now}')"),
                    ]
                );
            }

            $menuKeys = [
                'dashboard-overview',
                'dashboard-booking',
                'dashboard-revenue',
                'dashboard-performance',
            ];

            $menuIds = DB::table('menus')
                ->whereIn('menu_key', $menuKeys)
                ->pluck('id')
                ->all();

            if (empty($menuIds)) return;

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

            foreach ($menuIds as $mid) {
                foreach ($roleIds as $rid) {
                    DB::table('menu_roles')->updateOrInsert(
                        ['menu_id' => $mid, 'role_id' => $rid],
                        []
                    );
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $menuKeys = [
                'dashboard-overview',
                'dashboard-booking',
                'dashboard-revenue',
                'dashboard-performance',
            ];

            $menuIds = DB::table('menus')
                ->whereIn('menu_key', $menuKeys)
                ->pluck('id')
                ->all();

            if (!empty($menuIds)) {
                DB::table('menu_roles')->whereIn('menu_id', $menuIds)->delete();
                DB::table('menus')->whereIn('id', $menuIds)->delete();
            }
        });
    }
};
