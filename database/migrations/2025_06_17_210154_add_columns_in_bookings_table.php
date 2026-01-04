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
            $table->enum('payment_type', ['OTA Collect', 'Hotel Collect'])->default('Hotel Collect')->after('customer_id');
            $table->decimal('total_amount', 10, 2)->default(0)->after('payment_type');
            $table->decimal('paid', 10, 2)->default(0)->after('total_amount');
            $table->decimal('remaining', 10, 2)->default(0)->after('paid');
            $table->string('payment_status')->nullable()->after('remaining');
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
