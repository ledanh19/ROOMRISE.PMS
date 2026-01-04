<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            // Add rate_plan_ota_id column to store rates for specific OTAs
            $table->foreignId('rate_plan_ota_id')->nullable()->after('rate_plan_id')->constrained('rate_plan_booking_source')->onDelete('cascade');
            
            // Update unique constraint to include rate_plan_ota_id
            $table->dropUnique(['date', 'property_id', 'room_type_id', 'rate_plan_id']);
            $table->unique(['date', 'property_id', 'room_type_id', 'rate_plan_id', 'rate_plan_ota_id'], 'inventories_unique_constraint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropForeign(['rate_plan_ota_id']);
            $table->dropColumn('rate_plan_ota_id');
            
            // Restore original unique constraint
            $table->dropUnique('inventories_unique_constraint');
            $table->unique(['date', 'property_id', 'room_type_id', 'rate_plan_id']);
        });
    }
};
