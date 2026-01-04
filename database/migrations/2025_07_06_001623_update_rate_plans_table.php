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
        Schema::table('rate_plans', function (Blueprint $table) {
            // Remove external_id column as RatePlan no longer syncs with Channex
            if (Schema::hasColumn('rate_plans', 'external_id')) {
                $table->dropColumn('external_id');
            }

            // Add price column for reference price
            $table->decimal('price', 10, 2)->after('meal_type')->comment('Reference price for webapp and OTA calculations');

            // Change property_id from uuid to foreignId
            if (Schema::hasColumn('rate_plans', 'property_id')) {
                $table->dropColumn('property_id');
            }
            $table->foreignId('property_id')->after('id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rate_plans', function (Blueprint $table) {
            $table->uuid('external_id')->nullable();
            $table->dropColumn('price');
            $table->dropForeign(['property_id']);
            $table->dropColumn('property_id');
            $table->uuid('property_id');
        });
    }
};
