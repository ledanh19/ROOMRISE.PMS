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
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('ota_fee_percent', 10, 2)->nullable()->after('payment_status');
            $table->decimal('ota_fee', 10, 2)->nullable()->after('ota_fee_percent');
            $table->decimal('net_estimate', 10, 2)->nullable()->after('ota_fee');
            $table->decimal('payout_received', 10, 2)->nullable()->after('net_estimate');
            $table->decimal('difference_amount', 10, 2)->nullable()->after('payout_received');
            $table->string('ota_channel')->nullable()->after('difference_amount');
            $table->string('reconciliation_status')->nullable()->after('ota_channel');
            $table->string('note')->nullable()->after('reconciliation_status');
            $table->unsignedBigInteger('settlement_id')->nullable()->after('note');
            $table->foreign('settlement_id')->references('id')->on('settlements')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            //
        });
    }
};
