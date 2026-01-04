<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    protected function loadRoleNames(): array
    {
        $path = public_path('roles.json');

        if (!file_exists($path)) {
            throw new \RuntimeException("Không tìm thấy file roles.json tại: {$path}");
        }

        $raw = file_get_contents($path);
        $json = json_decode($raw, true);

        if (!is_array($json)) {
            throw new \RuntimeException("File roles.json không hợp lệ (cần JSON object).");
        }

        return array_values(array_filter(array_keys($json), fn($k) => is_string($k) && $k !== ''));
    }

    public function up(): void
    {
        DB::transaction(function () {
            $now = now();

            $menus = [
                [
                    "key" => "report",
                    "name" => "Báo cáo",
                    "link" => "report",
                    "order" => 20,
                    "parent_key" => null,
                    "is_heading" => 0,
                ],
                [
                    "key" => "daily-activity-report",
                    "name" => "Báo cáo hoạt động hằng ngày",
                    "link" => "daily-activity-report",
                    "order" => 10,
                    "parent_key" => "report",
                    "is_heading" => 0,
                ],
                [
                    "key" => "management-report",
                    "name" => "Báo cáo quản lý",
                    "link" => "management-report",
                    "order" => 20,
                    "parent_key" => "report",
                    "is_heading" => 0,
                ],
                [
                    "key" => "payment-report",
                    "name" => "Báo cáo thanh toán",
                    "link" => "payment-report",
                    "order" => 30,
                    "parent_key" => "report",
                    "is_heading" => 0,
                ],
                [
                    "key" => "revenue-report",
                    "name" => "Báo cáo doanh thu",
                    "link" => "revenue-report",
                    "order" => 40,
                    "parent_key" => "report",
                    "is_heading" => 0,
                ],
            ];

            foreach ($menus as $m) {
                DB::table('menus')->updateOrInsert(
                    ['menu_key' => $m['key']],
                    [
                        'name'         => $m['name'],
                        'link'         => $m['link'],
                        'order'        => (int) $m['order'],
                        'parent_id'    => null,
                        'image'        => $m['image'] ?? null,
                        'image_active' => $m['image_active'] ?? null,
                        'is_heading'   => (int) ($m['is_heading'] ?? 0),
                        'updated_at'   => $now,
                        'created_at'   => DB::raw("COALESCE(created_at, '{$now}')"),
                    ]
                );
            }

            foreach ($menus as $m) {
                if (!empty($m['parent_key'])) {
                    $parentId = DB::table('menus')->where('menu_key', $m['parent_key'])->value('id');
                    if ($parentId) {
                        DB::table('menus')
                            ->where('menu_key', $m['key'])
                            ->update(['parent_id' => $parentId, 'updated_at' => $now]);
                    }
                }
            }

            $roleNames = $this->loadRoleNames();
            if ($roleNames) {
                $roleIds = DB::table('roles')->whereIn('name', $roleNames)->pluck('id', 'name')->all();

                foreach ($menus as $m) {
                    $menuId = DB::table('menus')->where('menu_key', $m['key'])->value('id');
                    if (!$menuId) continue;

                    foreach ($roleNames as $rname) {
                        $rid = $roleIds[$rname] ?? null;
                        if (!$rid) continue;

                        DB::table('menu_roles')->updateOrInsert(
                            ['menu_id' => $menuId, 'role_id' => $rid],
                            []
                        );
                    }
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $keys = [
                'report',
                'daily-activity-report',
                'management-report',
                'payment-report',
                'revenue-report',
            ];

            $menuIds = DB::table('menus')->whereIn('menu_key', $keys)->pluck('id')->all();
            if (!empty($menuIds)) {
                DB::table('menu_roles')->whereIn('menu_id', $menuIds)->delete();
                DB::table('menus')->whereIn('id', $menuIds)->delete();
            }
        });
    }
};
