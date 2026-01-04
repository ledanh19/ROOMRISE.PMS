<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $table = config('permission.table_names.permissions', 'permissions');
        $now   = Carbon::now();

        $rows = [
            [
                'name'       => 'view-daily-activity-report',
                'module'     => 'daily-activity-report',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'view-management-report',
                'module'     => 'management-report',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'view-payment-report',
                'module'     => 'payment-report',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name'       => 'view-revenue-report',
                'module'     => 'revenue-report',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table($table)->upsert(
            $rows,
            ['name', 'guard_name'],
            ['module', 'updated_at']
        );
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $table = config('permission.table_names.permissions', 'permissions');

        DB::table($table)
            ->whereIn('name', [
                'view-daily-activity-report',
                'view-management-report',
                'view-payment-report',
                'view-revenue-report',
            ])
            ->where('guard_name', 'web')
            ->delete();
    }
};
