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
        // Cập nhật bảng bookings - giữ nguyên trạng thái nullable
        Schema::table('bookings', function (Blueprint $table) {
            // Các cột NOT NULL với default(0)
            $table->decimal('total_amount', 15, 2)->default(0)->change();
            $table->decimal('paid', 15, 2)->default(0)->change();
            $table->decimal('remaining', 15, 2)->default(0)->change();
            $table->decimal('room_price_at_booking', 15, 2)->default(0)->change();

            // Các cột NULLABLE
            $table->decimal('ota_fee', 15, 2)->nullable()->change();
            $table->decimal('net_estimate', 15, 2)->nullable()->change();
            $table->decimal('payout_received', 15, 2)->nullable()->change();
            $table->decimal('difference_amount', 15, 2)->nullable()->change();
            $table->decimal('customer_payment_amount', 15, 2)->nullable()->change();
            $table->decimal('commission_fee', 15, 2)->nullable()->change();
        });

        // Cập nhật bảng booking_rooms - giữ nguyên trạng thái nullable
        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->decimal('room_price_at_booking', 15, 2)->nullable()->change();
            $table->decimal('total', 15, 2)->nullable()->change();
            $table->decimal('discount', 15, 2)->nullable()->change();
        });

        // Cập nhật bảng payment_histories
        Schema::table('payment_histories', function (Blueprint $table) {
            $table->decimal('paid', 15, 2)->default(0)->change();
        });

        // Cập nhật bảng rate_plans
        Schema::table('rate_plans', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->change();
        });

        // Cập nhật bảng invoice_histories
        Schema::table('invoice_histories', function (Blueprint $table) {
            $table->decimal('total_amount', 15, 2)->default(0)->change();
        });

        // Cập nhật bảng inventories
        Schema::table('inventories', function (Blueprint $table) {
            $table->decimal('rate', 15, 2)->nullable()->change();
        });

        // Cập nhật bảng occupancy_options
        Schema::table('occupancy_options', function (Blueprint $table) {
            $table->decimal('rate', 15, 2)->default(0)->change();
        });

        // Cập nhật bảng booking_income_expenses
        Schema::table('booking_income_expenses', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
