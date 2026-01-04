<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            // Add occupancy_option_id column to track per-person inventory
            $table->unsignedBigInteger('occupancy_option_id')->nullable()->after('rate_plan_ota_id');
            $table->foreign('occupancy_option_id')->references('id')->on('occupancy_options');

            // Update unique constraint to include occupancy_option_id
            $table->dropUnique('inventories_unique_constraint');
            $table->unique(['date', 'property_id', 'room_type_id', 'rate_plan_id', 'rate_plan_ota_id', 'occupancy_option_id'], 'inventories_unique_constraint');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropUnique('inventories_unique_constraint');
            $table->dropForeign(['occupancy_option_id']);
            $table->dropColumn('occupancy_option_id');
            $table->unique(['date', 'property_id', 'room_type_id', 'rate_plan_id', 'rate_plan_ota_id'], 'inventories_unique_constraint');
        });
    }
};
