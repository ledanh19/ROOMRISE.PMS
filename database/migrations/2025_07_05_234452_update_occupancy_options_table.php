<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occupancy_options', function (Blueprint $table) {
            if (Schema::hasColumn('occupancy_options', 'rate_plan_id')) {
                try {
                    $table->dropForeign(['rate_plan_id']);
                } catch (\Throwable $e) {
                    // ignore if foreign key doesn't exist
                }

                $table->dropColumn('rate_plan_id');
            }

            if (!Schema::hasColumn('occupancy_options', 'rate_plan_ota_id')) {
                $table->foreignId('rate_plan_ota_id')
                    ->after('external_id')
                    ->constrained('rate_plan_booking_source')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('occupancy_options', function (Blueprint $table) {
            if (Schema::hasColumn('occupancy_options', 'rate_plan_ota_id')) {
                $table->dropForeign(['rate_plan_ota_id']);
                $table->dropColumn('rate_plan_ota_id');
            }

            if (!Schema::hasColumn('occupancy_options', 'rate_plan_id')) {
                $table->foreignId('rate_plan_id')
                    ->constrained()
                    ->cascadeOnDelete();
            }
        });
    }
};
