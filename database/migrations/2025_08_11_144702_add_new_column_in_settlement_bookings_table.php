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
        Schema::table('settlement_bookings', function (Blueprint $table) {
            $table->decimal('net_estimate', 15, 2)->default(0)->after('booking_id');
            $table->decimal('payout_received', 15, 2)->default(0)->after('net_estimate');
            $table->decimal('difference_amount', 15, 2)->default(0)->after('payout_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlement_bookings', function (Blueprint $table) {
            //
        });
    }
};
